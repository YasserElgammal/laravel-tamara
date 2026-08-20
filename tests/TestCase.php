<?php

namespace YasserElgammal\Tamara\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use YasserElgammal\Tamara\TamaraServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array{return [TamaraServiceProvider::class];}
    protected function defineEnvironment($app): void
    {
        $app['config']->set('tamara.api_url','https://api-sandbox.tamara.co');
        $app['config']->set('tamara.api_token','test-api-token');
        $app['config']->set('tamara.notification_token','notification-secret');
        $app['config']->set('tamara.webhook.middleware',[]);
        $app['config']->set('cache.default','array');
    }
}
