<?php

namespace Aloware\HorizonWildcardConsumer\Storages;

use Aloware\HorizonWildcardConsumer\Contracts\StorageContract;

class Redis implements StorageContract
{
    /**
     * Gets queues from Redis
     *
     * @return array
     */
    public function queues(): array
    {
            $prefix = config(
                'horizon-wildcard-consumer.queue_name_prefix',
                'laravel_database_queues'
            );

            $keys = app('redis')
                ->connection(config('horizon.use'))
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
