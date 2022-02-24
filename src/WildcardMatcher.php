<?php

namespace Aloware\HorizonWildcardConsumer;

class WildcardMatcher
{
    /**
     * Storage instance
     *
     * @var Aloware\HorizonWildcardConsumer\Contracts\StorageContract
     */
    protected $storage;

    public function __construct($storage)
    {
        $this->storage = $storage;
    }

    /**
     * Gets queues from currently selected storage
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle(array $wildcards = []): array
    {
        $queues = $this->storage->queues();

        $matched = [];

        if (count($queues) > 0) {
            foreach ($queues as $queue) {
                foreach ($wildcards as $wildcard) {
                    if (fnmatch($wildcard, $queue)) {
                        $matched[] = $queue;
                    }
                }
            }
        }

        return $matched;
    }
}
