<?php

namespace YasserElgammal\Tamara;

use Illuminate\Support\ServiceProvider;
use YasserElgammal\Tamara\Http\TamaraClient;

final class TamaraServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tamara.php','tamara');
        $this->app->singleton(TamaraClient::class);
        $this->app->singleton(TamaraManager::class);
    }
    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/tamara.php'=>config_path('tamara.php')],'tamara-config');
        if ((bool)config('tamara.webhook.enabled',true)) $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');
    }
}
