<?php

namespace Aloware\HorizonWildcardConsumer;

use Illuminate\Support\ServiceProvider;

class HorizonWildcardConsumerServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/horizon-wildcard-consumer.php',
            'horizon-wildcard-consumer'
        );
    }

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/HorizonWildcardConsumerServiceProvider.php' => app_path('Providers/HorizonWildcardConsumerServiceProvider.php'),
            ], 'horizon-wildcard-consumer-provider');

            $this->publishes([
                __DIR__.'/../config/horizon-wildcard-consumer.php' => config_path('horizon-wildcard-consumer.php'),
            ], 'horizon-wildcard-consumer-config');

            $this->commands([
                Commands\HorizonWildcardConsumeCommand::class,
            ]);
        }
    }
}
