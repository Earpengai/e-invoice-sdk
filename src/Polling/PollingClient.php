<?php

namespace CamInv\EInvoice\Polling;

use CamInv\EInvoice\Client\CamInvClient;

/**
 * Polls for document update events as an alternative to webhooks.
 *
 * Periodically query this endpoint to retrieve documents that have been
 * updated since the last sync timestamp.
 *
 * @see https://developer.e-invoice.gov.kh/receive-update-event/polling/getting-started
 */
class PollingClient
{
    public function __construct(
        protected CamInvClient $client,
    ) {}

    /**
     * Poll for new or updated documents since the last sync.
     *
     * Returns an array of PollEvent objects. Each event includes a document_id,
     * which can be used with DocumentClient::getDetail() or DocumentClient::getXml().
     *
     * @see https://developer.e-invoice.gov.kh/receive-update-event/polling/getting-started
     *
     * @param  string|null  $lastSyncedAt  ISO 8601 timestamp of the last sync (e.g. 2024-06-01T01:00:00).
     * @param  string       $accessToken   Bearer access token for authorization.
     * @return PollEvent[]                 Array of polled document update events.
     */
    public function poll(?string $lastSyncedAt, string $accessToken): array
    {
        $query = array_filter([
            'last_synced_at' => $lastSyncedAt,
        ], fn ($v) => $v !== null);

        $results = $this->client->withBearerToken($accessToken)->get('/api/v1/document/poll', $query);

        return array_map(fn (array $item) => PollEvent::fromArray($item), $results);
    }
}
