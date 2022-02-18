<?php

namespace Aloware\HorizonWildcardConsumer\Storages;

use Aloware\HorizonWildcardConsumer\Contracts\StorageContract;
use GuzzleHttp\Client;

class RabbitMQ implements StorageContract
{
    /**
     * Gets queues from RabbitMQ
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function queues(): array
    {
        $http = new Client([
            'base_uri' => config('horizon-wildcard-consumer.rabbitmq_api_url', 'http://localhost:15672')
        ]);

        $login = config('queue.connections.rabbitmq.login');
        $password = config('queue.connections.rabbitmq.password');

        $params = [
            'auth' => [$login, $password],
            'headers' => [
                'Accept' => 'application/json',
                'ContentType' => 'application/json',
            ],
        ];

        $response = $http->get('/api/queues', $params);

        return collect(json_decode($response->getBody(), true))
            ->pluck('name')
            ->all();
    }
}
