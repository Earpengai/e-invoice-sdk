<?php

namespace CamInv\EInvoice\Polling;

use CamInv\EInvoice\Client\CamInvClient;

class PollingClient
{
    public function __construct(
        protected CamInvClient $client,
    ) {}

    public function poll(?string $lastSyncedAt, string $accessToken): array
    {
        $query = array_filter([
            'last_synced_at' => $lastSyncedAt,
        ], fn ($v) => $v !== null);

        $results = $this->client->withBearerToken($accessToken)->get('/api/v1/document/poll', $query);

        return array_map(fn (array $item) => PollEvent::fromArray($item), $results);
    }
}
