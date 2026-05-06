<?php

namespace CamInv\EInvoice\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            \CamInv\EInvoice\CamInvServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('e-invoice', require __DIR__ . '/../config/e-invoice.php');
        $app['config']->set('e-invoice.client_id', 'test-client-id');
        $app['config']->set('e-invoice.client_secret', 'test-client-secret');
        $app['config']->set('e-invoice.default_environment', 'sandbox');
        $app['config']->set('e-invoice.environments.sandbox.base_url', 'https://api-sandbox.e-invoice.gov.kh');
    }
}
