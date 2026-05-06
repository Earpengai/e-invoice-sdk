<?php

namespace CamInv\EInvoice\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \CamInv\EInvoice\Client\CamInvClient client()
 * @method static \CamInv\EInvoice\Auth\OAuthService oauth()
 * @method static \CamInv\EInvoice\Token\TokenManager token()
 * @method static \CamInv\EInvoice\Document\DocumentClient documents()
 * @method static \CamInv\EInvoice\UBL\UBLBuilder ubl()
 * @method static \CamInv\EInvoice\Webhook\WebhookClient webhooks()
 * @method static \CamInv\EInvoice\Member\MemberClient members()
 * @method static \CamInv\EInvoice\Webhook\WebhookEvent parseWebhook(array $payload)
 *
 * @see \CamInv\EInvoice\CamInvManager
 */
class CamInv extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'caminv';
    }
}
