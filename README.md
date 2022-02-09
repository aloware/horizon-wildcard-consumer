# Horizon Wildcard Consumer

This package allows you to listen queues added dynamically by providing wildcarded name in Horizon config. For example: 'company_*_calls'.

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
