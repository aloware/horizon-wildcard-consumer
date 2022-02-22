<?php

namespace Aloware\HorizonWildcardConsumer\Storages;

use Aloware\HorizonWildcardConsumer\Contracts\StorageContract;
use Illuminate\Support\Str;

class Redis implements StorageContract
{

    public $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Gets queues from Redis
     *
     * @return array
     */
    public function queues(): array
    {
            $prefix = config(
                'horizon-wildcard-consumer.redis_queue_name_prefix',
                'laravel_database_queues'
            );

            $keys = app('redis')
                ->connection($this->conn)
                ->keys('*queues:*');

            return collect($keys)
                // remove prefix
                ->map(function ($item) use ($prefix) {
                    return Str::after($item, $prefix . ':');
                })
                // exclude :notify :reserved etc.
                ->filter(function ($item) {
                    return !Str::contains($item, ':');
                })
                ->all();
    }
}
