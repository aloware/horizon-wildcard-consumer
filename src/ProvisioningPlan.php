<?php

namespace Aloware\HorizonWildcardConsumer;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Horizon\ProvisioningPlan as BaseProvisioningPlan;
use Laravel\Horizon\SupervisorOptions;

class ProvisioningPlan extends BaseProvisioningPlan
{
    /**
     * Current supervisors
     *
     * @var array
     */
    protected $supervisors;

    /*
     * Last run of observer
     *
     * @var bool
     */

    private $lastRun;

    /**
     * Create a new provisioning plan instance.
     *
     * @param  string  $master
     * @param  array  $plan
     * @return void
     */
    public function __construct($master, array $plan)
    {
        $this->plan = $plan;
        $this->master = $master;
        $this->updatedSupervisors(config('horizon.env') ?? config('app.env'));
    }

    /**
     * Convert the given array of options into a SupervisorOptions instance.
     *
     * @param  string  $supervisor
     * @param  array  $options
     * @return \Laravel\Horizon\SupervisorOptions
     */
    protected function convert($supervisor, $options)
    {
        $options = collect($options)->mapWithKeys(function ($value, $key) {
            $key = $key === 'tries' ? 'max_tries' : $key;
            $key = $key === 'processes' ? 'max_processes' : $key;

            if ($key === 'queue' && is_array($value)) {
                $value = $this->getQueues($value);
            }

            return [Str::camel($key) => $value];
        })->all();

        return SupervisorOptions::fromArray(
            Arr::add($options, 'name', $this->master.":{$supervisor}")
        );
    }

    public function shouldRun(): bool
    {
        if (!$this->lastRun) {
            $this->lastRun = now();
        }
        $timeout = config('horizon-wildcard-consumer.observer.timeout', 60);

        return $this->lastRun->diffInSeconds(now(), true) > $timeout;
    }

    public function updatedSupervisors($env): array
    {
        $updatedSupervisors = [];
        $supervisors = [];
        $this->parsed = $this->toSupervisorOptions();

        foreach ($this->parsed[$env] as $key => $supervisor) {
            if (!blank($supervisor->queue)) {
                $queues = explode(',', $supervisor->queue);

                $diff = [];
                if (count(data_get($this->supervisors, $supervisor->name, [])) > 0) {
                    $diff = array_diff(
                        $queues,
                        data_get($this->supervisors, $supervisor->name, [])
                    );
                }

                dump('diff', $queues, data_get($this->supervisors, $supervisor->name, []));

                if (count($diff) > 0) {
                    $supervisors[$supervisor->name] = $queues;
                    $updatedSupervisors[] = $supervisor->name;
                } else {
                    unset($this->parsed[$env][$key]);
                }
            }
        }

        dump('updated supervisors', $updatedSupervisors);

        $this->supervisors = $supervisors;

        $this->lastRun = now();

        return $updatedSupervisors;
    }

    protected function getQueues(array $queuePatterns = []): string
    {
        $prefix = config(
            'horizon-wildcard-consumer.queue_name_prefix',
            'laravel_database_queues'
        );
        $keys = app('redis')->keys('*queues:*');
        dump('keys', $keys);
        $queues = collect($keys)
            // remove prefix
            ->map(function ($item) use ($prefix) {
                return Str::after($item, $prefix . ':');
            })
            // exclude :notify :reserved etc.
            ->filter(function ($item) {
                return !Str::contains($item, ':');
            })
            ->all();

        dump('found queues', $queues);

        $matched = [];

        if (count($queues) > 0) {
            if (count($queuePatterns) > 0) {
                foreach ($queues as $queue) {
                    foreach ($queuePatterns as $wildcard) {
                        if ($this->wildcardMatches($wildcard, $queue)) {
                            $matched[] = $queue;
                        }
                    }
                }
            } else {
                $matched = $queues;
            }
        }

        dump('matched queues', $matched);

        return !empty($matched) ? implode(',', $matched) : 'default';
    }

    private function wildcardMatches($pattern, $haystack): bool
    {
        if ($pattern === $haystack && !Str::contains($pattern, '*')) {
            return true;
        }

        $regex = str_replace(
            ["\*"], // wildcard chars
            ['.*', '.'],  // regexp chars
            preg_quote($pattern)
        );

        preg_match('/^' . $regex . '$/is', $haystack, $matches);

        return count($matches) > 0;
    }
}
