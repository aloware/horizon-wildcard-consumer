<?php

return [
    'observer' => [
        // in seconds
        'timeout' => env('QUEUE_OBSERVER_TIMEOUT', 60),
    ],
    'redis_queue_name_prefix' => env('REDIS_QUEUE_NAME_PREFIX', 'laravel_database_queues'),
    'rabbitmq_api_url' => env('RABBITMQ_API_URL', 'http://localhost:15672')
];
