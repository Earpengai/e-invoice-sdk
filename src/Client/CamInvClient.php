<?php

namespace CamInv\EInvoice\Client;

use CamInv\EInvoice\Exceptions\CamInvException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class CamInvClient
{
    protected string $authType = 'bearer';

    protected ?string $accessToken = null;

    protected ?string $basicAuthHeader = null;

    protected int $timeout;

    protected int $retries;

    protected int $retryDelay;

    public function __construct(
        protected string $baseUrl,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = config('e-invoice.http.timeout', 30);
        $this->retries = config('e-invoice.http.retries', 3);
        $this->retryDelay = config('e-invoice.http.retry_delay', 100);
    }

    public function withBasicAuth(): self
    {
        $this->authType = 'basic';
        $this->basicAuthHeader = base64_encode(
            config('e-invoice.client_id') . ':' . config('e-invoice.client_secret')
        );

        return $this;
    }

    public function withBearerToken(string $token): self
    {
        $this->authType = 'bearer';
        $this->accessToken = $token;

        return $this;
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->send('post', $endpoint, ['json' => $data]);
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->send('get', $endpoint, ['query' => $query]);
    }

    public function getRaw(string $endpoint, array $query = []): string
    {
        $request = $this->buildRequest();
        $url = "{$this->baseUrl}{$endpoint}";

        $response = $request->get($url, $query);
        $this->handleResponse($response);

        return $response->body();
    }

    protected function send(string $method, string $endpoint, array $options): array
    {
        $request = $this->buildRequest();
        $url = "{$this->baseUrl}{$endpoint}";

        try {
            $response = $request->$method($url, $options['json'] ?? $options['query'] ?? []);
            $this->handleResponse($response);

            return $response->json() ?: [];
        } catch (RequestException $e) {
            $response = $e->response;

            throw new CamInvException(
                message: $this->extractErrorMessage($response),
                statusCode: $response ? $response->status() : 0,
                responseBody: $response ? $response->json() : null,
            );
        }
    }

    protected function buildRequest(): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::timeout($this->timeout)
            ->retry($this->retries, $this->retryDelay)
            ->accept('application/json')
            ->contentType('application/json');

        if ($this->authType === 'basic' && $this->basicAuthHeader) {
            $request = $request->withHeader('Authorization', "Basic {$this->basicAuthHeader}");
        } elseif ($this->accessToken) {
            $request = $request->withToken($this->accessToken);
        }

        return $request;
    }

    protected function handleResponse($response): void
    {
        if ($response->successful()) {
            return;
        }

        throw new CamInvException(
            message: $this->extractErrorMessage($response),
            statusCode: $response->status(),
            responseBody: $response->json(),
        );
    }

    protected function extractErrorMessage($response): string
    {
        if (! $response) {
            return 'Network error: unable to connect to CamInv API.';
        }

        $body = $response->json();

        if (isset($body['message'])) {
            return $body['message'];
        }

        if (isset($body['error'])) {
            return $body['error'];
        }

        return "CamInv API error (HTTP {$response->status()})";
    }
}
