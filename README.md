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
            'queue' => ['default', 'user_*_notes', '*-bills'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
        ],
        'supervisor-2' => [
            'connection' => 'redis',
            'queue' => ['agent_*_calls', 'company_*_messages'],
            'balance' => 'auto',
            'processes' => 5,
            'tries' => 3,
        ]
    ]
]
```

### Example config for RabbitMQ
Add RabbitMQ connection config to `config/queue.php`
You can get example config from a package maintainer's repo https://github.com/vyuldashev/laravel-queue-rabbitmq/tree/v8.0


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
RABBITMQ_API_URL=http://localhost:15672
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_LOGIN=admin
RABBITMQ_PASSWORD=admin
RABBITMQ_SSL=0
RABBITMQ_QUEUE=default
RABBITMQ_VHOST=/
```

For additional info refer https://github.com/vyuldashev/laravel-queue-rabbitmq/tree/v8.0

