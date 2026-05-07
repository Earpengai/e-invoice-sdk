<?php

namespace CamInv\EInvoice\Client;

use CamInv\EInvoice\Exceptions\CamInvException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Low-level HTTP client for the CamInv e-Invoice API.
 *
 * Supports two authentication modes switched via fluent setters:
 *  - Basic Auth ({@see withBasicAuth()})      — for OAuth, webhooks, revoke
 *  - Bearer Token ({@see withBearerToken()})  — for all business API calls
 *
 * All responses are JSON-decoded arrays. Non-successful responses
 * (HTTP ≥400) are thrown as {@see CamInvException} with the parsed
 * error message extracted from the response body.
 *
 * @see https://developer.e-invoice.gov.kh/getting-started/oauth2
 */
class CamInvClient
{
    /** @var string Current authentication type: 'basic' or 'bearer'. */
    protected string $authType = 'bearer';

    /** @var string|null Bearer token set by {@see withBearerToken()}. */
    protected ?string $accessToken = null;

    /** @var string|null Pre-computed Basic auth header value. */
    protected ?string $basicAuthHeader = null;

    /** @var int HTTP request timeout in seconds. */
    protected int $timeout;

    /** @var int Number of retry attempts on failure. */
    protected int $retries;

    /** @var int Delay between retries in milliseconds. */
    protected int $retryDelay;

    /**
     * @param  string  $baseUrl  API base URL (e.g. https://api-sandbox.e-invoice.gov.kh).
     *                           Trailing slash is stripped automatically.
     */
    public function __construct(
        protected string $baseUrl,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = config('e-invoice.http.timeout', 30);
        $this->retries = config('e-invoice.http.retries', 3);
        $this->retryDelay = config('e-invoice.http.retry_delay', 100);
    }

    /**
     * Switch to Basic authentication for the next request.
     *
     * The Authorization header is built from ``CAMINV_CLIENT_ID`` and
     * ``CAMINV_CLIENT_SECRET`` in the application config.
     *
     * @return $this
     */
    public function withBasicAuth(): self
    {
        $this->authType = 'basic';
        $this->basicAuthHeader = base64_encode(
            config('e-invoice.client_id') . ':' . config('e-invoice.client_secret')
        );

        return $this;
    }

    /**
     * Switch to Bearer token authentication for the next request.
     *
     * @param  string  $token  A valid CamInv access token.
     * @return $this
     */
    public function withBearerToken(string $token): self
    {
        $this->authType = 'bearer';
        $this->accessToken = $token;

        return $this;
    }

    /**
     * Send a GET request.
     *
     * @param  string  $endpoint  API path relative to the base URL (e.g. ``/api/v1/document``).
     * @param  array   $query     Query string parameters.
     * @return array              JSON-decoded response body.
     *
     * @throws CamInvException
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->send('get', $endpoint, ['query' => $query]);
    }

    /**
     * Send a POST request with a JSON body.
     *
     * @param  string  $endpoint  API path relative to the base URL.
     * @param  array   $data      Request body (encoded as JSON).
     * @return array              JSON-decoded response body.
     *
     * @throws CamInvException
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->send('post', $endpoint, ['json' => $data]);
    }

    /**
     * Send a PUT request with a JSON body.
     *
     * @param  string  $endpoint  API path relative to the base URL.
     * @param  array   $data      Request body (encoded as JSON).
     * @return array              JSON-decoded response body.
     *
     * @throws CamInvException
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->send('put', $endpoint, ['json' => $data]);
    }

    /**
     * Send a PATCH request with a JSON body.
     *
     * @param  string  $endpoint  API path relative to the base URL.
     * @param  array   $data      Request body (encoded as JSON).
     * @return array              JSON-decoded response body.
     *
     * @throws CamInvException
     */
    public function patch(string $endpoint, array $data = []): array
    {
        return $this->send('patch', $endpoint, ['json' => $data]);
    }

    /**
     * Send a DELETE request with a JSON body.
     *
     * @param  string  $endpoint  API path relative to the base URL.
     * @param  array   $data      Request body (encoded as JSON).
     * @return array              JSON-decoded response body.
     *
     * @throws CamInvException
     */
    public function delete(string $endpoint, array $data = []): array
    {
        return $this->send('delete', $endpoint, ['json' => $data]);
    }

    /**
     * Send a GET request and return the raw response body.
     *
     * Used for downloading binary or XML content where JSON decoding
     * is not appropriate (e.g. PDF downloads, UBL XML retrieval).
     *
     * @param  string  $endpoint  API path relative to the base URL.
     * @param  array   $query     Query string parameters.
     * @return string             Raw response body.
     *
     * @throws CamInvException
     */
    public function getRaw(string $endpoint, array $query = []): string
    {
        $request = $this->buildRequest();
        $url = "{$this->baseUrl}{$endpoint}";

        $response = $request->get($url, $query);
        $this->handleResponse($response);

        return $response->body();
    }

    /**
     * Execute an HTTP request and return the JSON-decoded response.
     *
     * Wraps the actual HTTP call in a try/catch that converts Laravel's
     * {@see RequestException} into a SDK {@see CamInvException} with a
     * parsed error message.
     *
     * @param  string  $method    HTTP method: get, post, put, patch, delete.
     * @param  string  $endpoint  API path relative to the base URL.
     * @param  array   $options   Either ``['query' => [...]]`` for GET or
     *                            ``['json' => [...]]`` for body-bearing methods.
     * @return array              JSON-decoded response body (empty array if null).
     *
     * @throws CamInvException
     */
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

    /**
     * Build a configured HTTP pending request with auth, timeouts and retries.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
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

    /**
     * Validate the response or throw a {@see CamInvException}.
     *
     * Non-successful responses (HTTP ≥400) are converted to exceptions
     * with a human-readable message extracted from the response body.
     *
     * @param  \Illuminate\Http\Client\Response  $response
     * @return void
     *
     * @throws CamInvException
     */
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

    /**
     * Extract a human-readable error message from the CamInv API response.
     *
     * Checks known error fields in priority order:
     * ``error_description`` → ``message`` → ``error``.
     * Falls back to a generic message with the HTTP status code.
     *
     * @param  \Illuminate\Http\Client\Response|null  $response
     * @return string
     */
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
