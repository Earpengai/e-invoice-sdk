<?php

namespace CamInv\EInvoice\Auth;

use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Exceptions\AuthenticationException;
use CamInv\EInvoice\Support\Config;
use CamInv\EInvoice\Support\HasState;

/**
 * Handles OAuth 2.0 authentication flow with the CamInv e-Invoice API.
 *
 * @see https://developer.e-invoice.gov.kh/getting-started/oauth2   OAuth 2.0 Integration Guide
 * @see https://developer.e-invoice.gov.kh/api-reference/authentication Get New Access Token
 * @see https://developer.e-invoice.gov.kh/api-reference/revoke-member   Revoke Connected Member
 */
class OAuthService
{
    use HasState;

    public function __construct(
        protected CamInvClient $client,
        protected Config $config,
    ) {}

    /**
     * Configure the whitelist of redirect URLs for the OAuth flow.
     *
     * @see https://developer.e-invoice.gov.kh/getting-started/oauth2#configuration
     *
     * @param  string[]  $redirectUrls  List of allowed redirect URLs.
     *
     * @throws \CamInv\EInvoice\Exceptions\CamInvException
     */
    public function configureRedirectUrl(array $redirectUrls): void
    {
        $this->client->withBasicAuth()->post('/api/v1/configure/configure-redirect-url', [
            'white_list_redirect_urls' => $redirectUrls,
        ]);
    }

    /**
     * Generate the OAuth connect URL (Step 1 of the OAuth flow).
     *
     * Constructs the URL that the user should be redirected to for
     * granting authorization. The URL includes the client ID, the
     * encoded redirect URL, and a CSRF state token.
     *
     * @see https://developer.e-invoice.gov.kh/getting-started/oauth2#step-1-generate-connect-link
     *
     * @param  string       $redirectUrl  The URL to redirect back to after authorization.
     * @param  string|null  $state        Optional state token. Auto-generated if omitted.
     * @return array{url: string, state: string}
     */
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

    /**
     * Exchange the temporary auth token for access and refresh tokens (Step 3).
     *
     * After the user authorizes and is redirected back with an authToken,
     * exchange it for an access_token, refresh_token, and business_info.
     *
     * @see https://developer.e-invoice.gov.kh/getting-started/oauth2#step-3-token-request
     *
     * @param  string  $authToken  The temporary token received from the redirect callback.
     * @return array{access_token: string, refresh_token: string, business_info: array}
     *
     * @throws AuthenticationException  If the auth token is invalid or the response is malformed.
     */
    public function exchangeAuthToken(string $authToken): array
    {
        try {
            $response = $this->client->withBasicAuth()->post('/api/v1/auth/authorize/connect', [
                'auth_token' => $authToken,
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

    /**
     * Refresh an expired access token using a refresh token.
     *
     * Access tokens expire after 900 seconds (15 minutes). Use the
     * refresh token obtained during the initial exchange to get a new one.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/authentication
     *
     * @param  string  $refreshToken  The refresh token from the initial exchange.
     * @return array{access_token: string, token_type: string, expire_in: int}
     *
     * @throws AuthenticationException  If the refresh token is expired or invalid.
     */
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

    /**
     * Revoke (disconnect) a member from the ERP integration.
     *
     * This removes the merchant/business's connection to the system.
     * System partners can listen to the ENTITY.REVOKED webhook event.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/revoke-member
     *
     * @param  string  $endpointId  The endpoint ID of the business to revoke.
     * @return array{message: string}
     *
     * @throws AuthenticationException  If the revoke request fails.
     */
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
