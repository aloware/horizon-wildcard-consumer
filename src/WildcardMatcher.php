<?php

namespace Aloware\HorizonWildcardConsumer;

use Aloware\HorizonWildcardConsumer\Storages\Redis;
use Aloware\HorizonWildcardConsumer\Storages\RabbitMQ;

class WildcardMatcher
{
    /**
     * Queues that matched with given pattern
     * @var array
     */
    public $matched = [];

    /**
     * Wildcards that needs to be processed
     *
     * @var array
     */
    protected $wildcards = [];

    /**
     * Currently selected storage for jobs
     *
     * @var string
     */
    protected $driver;

    /**
     * Storage instance
     *
     * @var Aloware\HorizonWildcardConsumer\Contracts\StorageContract
     */
    protected $storage;

    public function __construct(array $wildcards = [])
    {
        $this->wildcards = $wildcards;
        $this->driver = config('queue.default', 'redis');
    }

    /**
     * Gets queues from currently selected storage
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle(): array
    {
        if ($this->driver === 'redis') {
            $this->storage = new Redis;
        }

        if ($this->driver === 'rabbitmq') {
            $this->storage = new RabbitMQ;
        }

        $queues = $this->storage->queues();

        if (count($queues) > 0) {
            foreach ($queues as $queue) {
                foreach ($this->wildcards as $wildcard) {
                    if ($this->wildcardMatches($wildcard, $queue)) {
                        $this->matched[] = $queue;
                    }
                }
            }
        }

        return $this->matched;
    }

    /**
     * Match queues with given wildcard
     *
     * @param  string  $pattern
     * @param  string  $haystack
     * @return bool
     */
    protected function wildcardMatches($pattern, $haystack): bool
    {
        $regex = str_replace(
            ["\*"], // wildcard chars
            ['.*', '.'],  // regexp chars
            preg_quote($pattern)
        );

        preg_match('/^' . $regex . '$/is', $haystack, $matches);

        return count($matches) > 0;
    }
}
