<?php

namespace CamInv\EInvoice\Webhook;

use CamInv\EInvoice\Client\CamInvClient;

class WebhookClient
{
    public function __construct(
        protected CamInvClient $client,
    ) {}

    public function configure(string $endpointId, string $webhookUrl): array
    {
        return $this->client->withBasicAuth()->post('/api/v1/configure/configure-webhook', [
            'endpoint_id' => $endpointId,
            'webhook_url' => $webhookUrl,
        ]);
    }
}
