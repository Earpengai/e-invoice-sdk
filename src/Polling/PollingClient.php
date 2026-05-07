<?php

namespace CamInv\EInvoice\Polling;

use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Support\HasTokenRefresh;
use CamInv\EInvoice\Token\TokenManager;

/**
 * Polls for document update events as an alternative to webhooks.
 *
 * Periodically query this endpoint to retrieve documents that have been
 * updated since the last sync timestamp.
 *
 * When a merchant ID is set via {@see forMerchant()}, access tokens are
 * resolved automatically through {@see TokenManager}. Passing a raw
 * `$accessToken` directly to any method bypasses this auto‑resolution,
 * preserving backward compatibility.
 *
 * @see https://developer.e-invoice.gov.kh/receive-update-event/polling/getting-started
 */
class PollingClient
{
    use HasTokenRefresh;

    protected ?string $merchantId = null;

    public function __construct(
        protected CamInvClient $client,
        protected TokenManager $tokenManager,
    ) {}

    /**
     * Scope this client to a specific merchant for automatic token resolution.
     *
     * @param  string  $merchantId  The merchant identifier used as the storage key.
     * @return $this
     */
    public function forMerchant(string $merchantId): static
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return TokenManager
     */
    protected function tokenManager(): TokenManager
    {
        return $this->tokenManager;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function merchantId(): string
    {
        if ($this->merchantId === null) {
            throw new \RuntimeException(
                'No merchant ID set on PollingClient. Call forMerchant() first or pass an access token explicitly.'
            );
        }

        return $this->merchantId;
    }

    /**
     * Resolve the access token to use: explicit token takes priority,
     * otherwise auto‑resolve through the TokenManager.
     *
     * @param  string|null  $accessToken  Explicit token (if any).
     * @return string
     */
    protected function resolveToken(?string $accessToken): string
    {
        return $accessToken ?? $this->tokenManager->getValidAccessToken($this->merchantId());
    }

    /**
     * Poll for new or updated documents since the last sync.
     *
     * Returns an array of PollEvent objects. Each event includes a document_id,
     * which can be used with DocumentClient::getDetail() or DocumentClient::getXml().
     *
     * @see https://developer.e-invoice.gov.kh/receive-update-event/polling/getting-started
     *
     * @param  string|null  $lastSyncedAt  ISO 8601 timestamp of the last sync (e.g. 2024-06-01T01:00:00).
     * @param  string|null  $accessToken   Bearer access token. Auto‑resolved when null and forMerchant() was called.
     * @return PollEvent[]                 Array of polled document update events.
     */
    public function poll(?string $lastSyncedAt, ?string $accessToken = null): array
    {
        return $this->withTokenRefresh(function () use ($lastSyncedAt, $accessToken) {
            $token = $this->resolveToken($accessToken);

            $query = array_filter([
                'last_synced_at' => $lastSyncedAt,
            ], fn ($v) => $v !== null);

            $results = $this->client->withBearerToken($token)->get('/api/v1/document/poll', $query);

            return array_map(fn (array $item) => PollEvent::fromArray($item), $results);
        });
    }
}
