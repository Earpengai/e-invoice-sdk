<?php

namespace CamInv\EInvoice;

use CamInv\EInvoice\Auth\OAuthService;
use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Contracts\TokenStore;
use CamInv\EInvoice\Document\DocumentClient;
use CamInv\EInvoice\Member\MemberClient;
use CamInv\EInvoice\Polling\PollingClient;
use CamInv\EInvoice\Support\Config;
use CamInv\EInvoice\Token\TokenManager;
use CamInv\EInvoice\Webhook\WebhookClient;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel service provider for the CamInv e-Invoice SDK.
 *
 * Registers all SDK services as singletons in the Laravel container
 * and publishes the configuration file to config/e-invoice.php.
 *
 * Bindings:
 *   - caminv   → CamInvManager (the main facade accessor)
 *   - Config
 *   - CamInvClient
 *   - OAuthService
 *   - TokenManager
 *   - DocumentClient
 *   - WebhookClient
 *   - MemberClient
 *   - PollingClient
 */
class CamInvServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/e-invoice.php', 'e-invoice');

        $this->app->singleton(Config::class, function ($app) {
            return new Config($app['config']);
        });

        $this->app->singleton(CamInvClient::class, function ($app) {
            $config = $app->make(Config::class);

            return new CamInvClient($config->baseUrl());
        });

        $this->app->singleton(OAuthService::class, function ($app) {
            return new OAuthService(
                $app->make(CamInvClient::class),
                $app->make(Config::class),
            );
        });

        $this->app->singleton(TokenManager::class, function ($app) {
            return new TokenManager(
                $app->make(TokenStore::class),
                $app->make(OAuthService::class),
                $app->make(Config::class),
            );
        });

        $this->app->singleton(DocumentClient::class, function ($app) {
            return new DocumentClient(
                $app->make(CamInvClient::class),
                $app->make(TokenManager::class),
            );
        });

        $this->app->singleton(WebhookClient::class, function ($app) {
            return new WebhookClient($app->make(CamInvClient::class));
        });

        $this->app->singleton(MemberClient::class, function ($app) {
            return new MemberClient(
                $app->make(CamInvClient::class),
                $app->make(TokenManager::class),
            );
        });

        $this->app->singleton(PollingClient::class, function ($app) {
            return new PollingClient(
                $app->make(CamInvClient::class),
                $app->make(TokenManager::class),
            );
        });

        $this->app->singleton('caminv', function ($app) {
            return new CamInvManager(
                $app->make(CamInvClient::class),
                $app->make(OAuthService::class),
                $app->make(TokenManager::class),
                $app->make(DocumentClient::class),
                $app->make(WebhookClient::class),
                $app->make(MemberClient::class),
                $app->make(PollingClient::class),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/e-invoice.php' => config_path('e-invoice.php'),
            ], 'e-invoice-config');
        }
    }
}
