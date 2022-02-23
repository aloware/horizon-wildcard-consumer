<?php

return [
    'observer' => [
        'timeout' => env('QUEUE_OBSERVER_TIMEOUT', 60), // in seconds
    ],
    'rabbitmq_api_url' => env('RABBITMQ_API_URL', 'http://localhost:15672')
];
