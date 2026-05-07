<?php

namespace CamInv\EInvoice\Tests\Unit;

use CamInv\EInvoice\CamInvManager;
use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Auth\OAuthService;
use CamInv\EInvoice\Token\TokenManager;
use CamInv\EInvoice\Document\DocumentClient;
use CamInv\EInvoice\Webhook\WebhookClient;
use CamInv\EInvoice\Member\MemberClient;
use CamInv\EInvoice\Polling\PollingClient;
use CamInv\EInvoice\Tests\TestCase;
use Mockery;

class CamInvManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_service_accessors_return_injected_instances(): void
    {
        $client = Mockery::mock(CamInvClient::class);
        $oauth = Mockery::mock(OAuthService::class);
        $token = Mockery::mock(TokenManager::class);
        $documents = Mockery::mock(DocumentClient::class);
        $webhooks = Mockery::mock(WebhookClient::class);
        $members = Mockery::mock(MemberClient::class);
        $polling = Mockery::mock(PollingClient::class);

        $manager = new CamInvManager($client, $oauth, $token, $documents, $webhooks, $members, $polling);

        $this->assertSame($client, $manager->client());
        $this->assertSame($oauth, $manager->oauth());
        $this->assertSame($token, $manager->token());
        $this->assertSame($documents, $manager->documents());
        $this->assertSame($webhooks, $manager->webhooks());
        $this->assertSame($members, $manager->members());
        $this->assertSame($polling, $manager->polling());
    }

    public function test_ubl_returns_builder_instance(): void
    {
        $manager = new CamInvManager(
            Mockery::mock(CamInvClient::class),
            Mockery::mock(OAuthService::class),
            Mockery::mock(TokenManager::class),
            Mockery::mock(DocumentClient::class),
            Mockery::mock(WebhookClient::class),
            Mockery::mock(MemberClient::class),
            Mockery::mock(PollingClient::class),
        );

        $builder = $manager->ubl();

        $this->assertInstanceOf(\CamInv\EInvoice\UBL\UBLBuilder::class, $builder);
    }

    public function test_parse_webhook_returns_event(): void
    {
        $manager = new CamInvManager(
            Mockery::mock(CamInvClient::class),
            Mockery::mock(OAuthService::class),
            Mockery::mock(TokenManager::class),
            Mockery::mock(DocumentClient::class),
            Mockery::mock(WebhookClient::class),
            Mockery::mock(MemberClient::class),
            Mockery::mock(PollingClient::class),
        );

        $event = $manager->parseWebhook([
            'type' => 'DOCUMENT.DELIVERED',
            'document_id' => 'uuid-123',
            'endpoint_id' => 'KHUID00001234',
        ]);

        $this->assertInstanceOf(\CamInv\EInvoice\Webhook\WebhookEvent::class, $event);
        $this->assertTrue($event->isDocumentDelivered());
    }
}
