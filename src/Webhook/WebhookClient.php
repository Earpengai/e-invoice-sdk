<?php

namespace CamInv\EInvoice\Webhook;

use CamInv\EInvoice\Client\CamInvClient;

/**
 * Manages webhook configuration — setup and teardown of webhook URLs.
 *
 * @see https://developer.e-invoice.gov.kh/receive-update-event/webhook/setting-up
 * @see https://developer.e-invoice.gov.kh/receive-update-event/webhook/unset-webhook
 */
class WebhookClient
{
    public function __construct(
        protected CamInvClient $client,
    ) {}

    /**
     * Configure a webhook URL for receiving event notifications.
     *
     * @see https://developer.e-invoice.gov.kh/receive-update-event/webhook/setting-up
     *
     * @param  string  $endpointId  CamInvoice ID of the entity (e.g. KHUID00001234).
     * @param  string  $webhookUrl  The URL that will receive webhook POST requests.
     * @return array{message: string}
     */
    public function configure(string $endpointId, string $webhookUrl): array
    {
        return $this->client->withBasicAuth()->post('/api/v1/configure/configure-webhook', [
            'endpoint_id' => $endpointId,
            'webhook_url' => $webhookUrl,
        ]);
    }

    /**
     * Remove a previously configured webhook for a specific entity.
     *
     * @see https://developer.e-invoice.gov.kh/receive-update-event/webhook/unset-webhook
     *
     * @param  string  $endpointId  CamInvoice ID of the entity.
     * @return array{message: string}
     */
    public function unset(string $endpointId): array
    {
        return $this->client->withBasicAuth()->delete("/api/v1/configure/configure-webhook/{$endpointId}");
    }
}
