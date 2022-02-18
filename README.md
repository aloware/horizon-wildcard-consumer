# Horizon Wildcard Consumer

This package allows you to listen queues added dynamically by having wildcard `*` in queue names in Horizon config file.

## Installation
You can install the package via composer using the following command:

```sh
composer require aloware/horizon-wildcard-consumer
```
## Usage
This package provides single command to consume dynamically added queues:

```sh
php artisan horizon:wildcard-consume
```

Before running this command make sure you added wildcards to your supervisor in **Horizon** config file.
This command works just like standard **php artisan horizon** command if you don't have any wildcards in config.

### Example horizon config

`config/horizon.php` environments section (please notice `*` in queue names):

`connection` can be set to **rabbitmq**

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'a_*', 'user_*_notes', '*-bills'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
        ],
        'supervisor-2' => [
            'connection' => 'redis',
            'queue' => ['b_*', 'agent_*_calls'],
            'balance' => 'auto',
            'processes' => 5,
            'tries' => 3,
        ],
        'supervisor-3' => [
            'connection' => 'redis',
            'queue' => ['c_*', 'company_*_messages'],
            'balance' => 'auto',
            'processes' => 5,
            'tries' => 3,
        ],
    ],
]
```

### Example config for RabbitMQ
Add following config to connections section in `config/queue.php`

```php
/*
 * Connection key (rabbitmq in this example) can be anything
 * It also should be used in horizon supervisor connection
 */
'rabbitmq' => [

    'driver'     => 'rabbitmq',
    'queue'      => env('RABBITMQ_QUEUE', 'default'),
    'connection' => env('RABBITMQ_SSL', false) ?
        PhpAmqpLib\Connection\AMQPSSLConnection::class :
        PhpAmqpLib\Connection\AMQPLazyConnection::class,

    'hosts' => [
        [
            'host'     => env('RABBITMQ_HOST', '127.0.0.1'),
            'port'     => env('RABBITMQ_PORT', 5672),
            'user'     => env('RABBITMQ_USER', 'guest'),
            'password' => env('RABBITMQ_PASSWORD', 'guest'),
            'vhost'    => env('RABBITMQ_VHOST', '/'),
        ],
    ],

    'options' => [
        'ssl_options' => [
            'cafile'      => env('RABBITMQ_SSL_CAFILE', null),
            'local_cert'  => env('RABBITMQ_SSL_LOCALCERT', null),
            'local_key'   => env('RABBITMQ_SSL_LOCALKEY', null),
            'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
            'passphrase'  => env('RABBITMQ_SSL_PASSPHRASE', null),
        ],
        'queue'       => [
            'job'                  => VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob::class,
            //'exchange'             => 'application-x',
            //'exchange_type'        => 'direct',
            'exchange_routing_key' => '%s',
            //'reroute_failed'       => true,
            //'failed_exchange'      => 'failed-exchange',
            //'failed_routing_key'   => 'application-x.%s',
        ],
    ],

    /*
     * Set to "horizon" if you wish to use Laravel Horizon.
     */
    'worker'  => env('RABBITMQ_WORKER', 'default'),
],
```

### Example .env for Redis
```ini
QUEUE_CONNECTION=redis
QUEUE_OBSERVER_TIMEOUT=25
```

### Example .env for RabbitMQ
```ini
QUEUE_CONNECTION=rabbitmq
QUEUE_OBSERVER_TIMEOUT=25
RABBITMQ_WORKER=horizon
#RABBITMQ_HOST=b-b4fa5b43-3b8e-4f2b-9ebe-14b05b0740e2.mq.us-west-2.amazonaws.com
#RABBITMQ_PORT=5671
#RABBITMQ_USER=dynamic_queue_test
#RABBITMQ_PASSWORD=dynamic_queue_test
#RABBITMQ_SSL=1
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=admin
RABBITMQ_PASSWORD=admin
RABBITMQ_SSL=0
RABBITMQ_QUEUE=default
RABBITMQ_VHOST=/
```

For additional info refer https://github.com/vyuldashev/laravel-queue-rabbitmq

