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

## Example config

`config/horizon.php` environments section (please notice `*` in queue names):

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
