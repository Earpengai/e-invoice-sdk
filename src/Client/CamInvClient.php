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

    public function get(string $endpoint, array $query = []): array
    {
        return $this->send('get', $endpoint, ['query' => $query]);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->send('post', $endpoint, ['json' => $data]);
    }

    public function put(string $endpoint, array $data = []): array
    {
        return $this->send('put', $endpoint, ['json' => $data]);
    }

    public function patch(string $endpoint, array $data = []): array
    {
        return $this->send('patch', $endpoint, ['json' => $data]);
    }

    public function delete(string $endpoint, array $data = []): array
    {
        return $this->send('delete', $endpoint, ['json' => $data]);
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
            if (isset($options['json'])) {
                $response = $request->asJson()->$method($url, $options['json']);
            } else {
                $response = $request->$method($url, $options['query'] ?? []);
            }

            $this->handleResponse($response);

            return $response->json() ?: [];
        } catch (RequestException $e) {
            $response = $e->response;

            try {
                $responseBody = $response ? $response->json() : null;
            } catch (\Throwable) {
                $responseBody = null;
            }

            throw new CamInvException(
                message: $this->extractErrorMessage($response),
                statusCode: $response ? $response->status() : 0,
                responseBody: $responseBody,
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

        try {
            $responseBody = $response->json();
        } catch (\Throwable) {
            $responseBody = null;
        }

        throw new CamInvException(
            message: $this->extractErrorMessage($response),
            statusCode: $response->status(),
            responseBody: $responseBody,
        );
    }

    protected function extractErrorMessage($response): string
    {
        if (! $response) {
            return 'Network error: unable to connect to CamInv API.';
        }

        try {
            $body = $response->json();

            if (! is_array($body)) {
                $bodyString = (string) $response->body();

                return $bodyString !== ''
                    ? "CamInv API error: {$bodyString}"
                    : "CamInv API error (HTTP {$response->status()})";
            }

            if (isset($body['error_description'])) {
                return $body['error_description'];
            }

            if (isset($body['message'])) {
                return is_array($body['message'])
                    ? json_encode($body['message'])
                    : $body['message'];
            }

            if (isset($body['error'])) {
                return is_array($body['error'])
                    ? json_encode($body['error'])
                    : $body['error'];
            }

            return "CamInv API error (HTTP {$response->status()})";
        } catch (\Throwable) {
            return "CamInv API error (HTTP {$response->status()})";
        }
    }
}
