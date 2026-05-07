<?php

namespace CamInv\EInvoice;

use CamInv\EInvoice\Auth\OAuthService;
use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Document\DocumentClient;
use CamInv\EInvoice\Member\MemberClient;
use CamInv\EInvoice\Polling\PollingClient;
use CamInv\EInvoice\Token\TokenManager;
use CamInv\EInvoice\UBL\UBLBuilder;
use CamInv\EInvoice\Webhook\WebhookClient;
use CamInv\EInvoice\Webhook\WebhookEvent;

class CamInvManager
{
    public function __construct(
        protected CamInvClient $client,
        protected OAuthService $oauth,
        protected TokenManager $token,
        protected DocumentClient $documents,
        protected WebhookClient $webhooks,
        protected MemberClient $members,
        protected PollingClient $polling,
    ) {}

    public function client(): CamInvClient
    {
        return $this->client;
    }

    public function oauth(): OAuthService
    {
        return $this->oauth;
    }

    public function token(): TokenManager
    {
        return $this->token;
    }

    public function documents(): DocumentClient
    {
        return $this->documents;
    }

    public function ubl(): UBLBuilder
    {
        return new UBLBuilder;
    }

    public function webhooks(): WebhookClient
    {
        return $this->webhooks;
    }

    public function members(): MemberClient
    {
        return $this->members;
    }

    public function parseWebhook(array $payload): WebhookEvent
    {
        return new WebhookEvent($payload);
    }

    public function polling(): PollingClient
    {
        return $this->polling;
    }
}
