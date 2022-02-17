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
     * TODO: use $this->parsed instead
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
                $parsedQueues = [];
                if (!array_key_exists($supervisor->name, $this->supervisors)) {
                    $parsedQueues = $queues;
                } else {
                    $parsedQueues = array_merge($this->supervisors[$supervisor->name], $queues);
                }
                $parsedQueues = array_unique($parsedQueues);
                $this->supervisors[$supervisor->name] = $parsedQueues;
                $this->parsed[$env][$key]->queue = implode(',', $parsedQueues);
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
     * @param array|string $queuePatterns
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getQueues($queues): string
    {
        $queues = collect(is_array($queues) ? $queues : explode(',', $queues));

        $normalQueues = $queues->filter(function ($queue) {
            return !Str::contains($queue, '*');
        })->all();

        $wildcards = $queues->filter(function ($queue) {
            return Str::contains($queue, '*');
        })->all();

        $matched = [];

        if (count($wildcards) > 0) {
            $matched = (new WildcardMatcher($wildcards))->handle();
        }

        $matched = array_unique(
            array_merge($normalQueues, $matched)
        );

        return implode(',', $matched);
    }
}
