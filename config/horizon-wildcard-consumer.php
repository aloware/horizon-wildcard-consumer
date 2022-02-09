<?php

return [
    'observer' => [
        // in seconds
        'timeout' => env('QUEUE_OBSERVER_TIMEOUT', 60),
    ],
    'queue_name_prefix' => 'laravel_database_queues',
];
