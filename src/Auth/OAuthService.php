<?php

namespace CamInv\EInvoice\Auth;

use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Exceptions\AuthenticationException;
use CamInv\EInvoice\Support\Config;
use CamInv\EInvoice\Support\HasState;

class OAuthService
{
    use HasState;

    public function __construct(
        protected CamInvClient $client,
        protected Config $config,
    ) {}

    public function configureRedirectUrl(array $redirectUrls): void
    {
        $this->client->withBasicAuth()->post('/api/v1/configure/configure-redirect-url', [
            'white_list_redirect_urls' => $redirectUrls,
        ]);
    }

    public function generateConnectUrl(string $redirectUrl, ?string $state = null): array
    {
        $state = $state ?? $this->generateState();
        $clientId = $this->config->clientId();
        $baseUrl = $this->config->baseUrl();

        $url = "{$baseUrl}/connect?client_id={$clientId}&redirect_url=" . urlencode($redirectUrl) . "&state={$state}";

        return [
            'url' => $url,
            'state' => $state,
        ];
    }

    public function exchangeAuthToken(string $authToken): array
    {
        try {
            $response = $this->client->withBasicAuth()->post('/api/v1/auth/authorize/connect', [
                'authToken' => $authToken,
            ]);
        } catch (\CamInv\EInvoice\Exceptions\CamInvException $e) {
            throw AuthenticationException::invalidAuthToken();
        }

        if (empty($response['access_token'])) {
            throw AuthenticationException::invalidAuthToken();
        }

        if (empty($response['business_info']) || ! is_array($response['business_info'])) {
            throw AuthenticationException::invalidAuthToken();
        }

        if (empty($response['business_info']['endpoint_id'])) {
            throw AuthenticationException::invalidAuthToken();
        }

        return $response;
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        try {
            $response = $this->client->withBasicAuth()->post('/api/v1/auth/token', [
                'refresh_token' => $refreshToken,
            ]);
        } catch (\CamInv\EInvoice\Exceptions\CamInvException $e) {
            throw AuthenticationException::tokenExpired();
        }

        if (empty($response['access_token'])) {
            throw AuthenticationException::tokenExpired();
        }

        return $response;
    }

    public function revokeConnectedMember(string $endpointId): array
    {
        try {
            $response = $this->client->withBasicAuth()->post('/api/v1/auth/revoke', [
                'endpoint_id' => $endpointId,
            ]);
        } catch (\CamInv\EInvoice\Exceptions\CamInvException $e) {
            throw AuthenticationException::revokeFailed();
        }

        return $response;
    }
}
