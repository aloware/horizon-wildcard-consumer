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
    protected $supervisors = [];

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

            if ($key === 'queue') {
                $value = $this->getQueues($value);
            }

            return [Str::camel($key) => $value];
        })->all();

        return SupervisorOptions::fromArray(
            Arr::add($options, 'name', $this->master.":{$supervisor}")
        );
    }

    /**
     * Determine if observing new queus timed out after last run
     *
     * @return bool
     */
    public function shouldRun(): bool
    {
        if (!$this->lastRun) {
            $this->lastRun = now();
        }
        $timeout = config('horizon-wildcard-consumer.observer.timeout', 60);

        return $this->lastRun->diffInSeconds(now(), true) > $timeout;
    }

    /**
     * Parse new queues and return updated supervisors
     *
     * @param  string  $env
     * @return array
     */
    public function updatedSupervisors($env): array
    {
        $updatedSupervisors = [];
        $this->parsed = $this->toSupervisorOptions();

        foreach ($this->parsed[$env] as $key => $supervisor) {
            if (blank($supervisor->queue)) {
                continue;
            }

            $queues = explode(',', $supervisor->queue);

            $diff = array_diff(
                $queues,
                data_get($this->supervisors, $supervisor->name, [])
            );

            if (count($diff) > 0) {
                if (!array_key_exists($supervisor->name, $this->supervisors)) {
                    $this->supervisors[$supervisor->name] = $queues;
                } else {
                    $this->supervisors[$supervisor->name] = array_merge($this->supervisors[$supervisor->name], $queues);
                }
                $updatedSupervisors[] = $supervisor->name;
            } else {
                unset($this->parsed[$env][$key]);
            }
        }

        $this->lastRun = now();

        return $updatedSupervisors;
    }

    /**
     * Observe new queues from redis and return list of comma separated queues
     *
     * @param  array|string  $queuePatterns
     * @return string
     */
    protected function getQueues($queuePatterns): string
    {
        $queuePatterns = is_array($queuePatterns) ? $queuePatterns : explode(',', $queuePatterns);

        $prefix = config(
            'horizon-wildcard-consumer.queue_name_prefix',
            'laravel_database_queues'
        );
        $keys = app('redis')->keys('*queues:*');

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

        return !empty($matched) ? implode(',', $matched) : 'default';
    }

    /**
     * Match queues with given wildcard
     *
     * @param  string  $pattern
     * @param  string  $haystack
     * @return bool
     */
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
