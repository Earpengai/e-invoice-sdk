<?php

namespace CamInv\EInvoice\Member;

use CamInv\EInvoice\Client\CamInvClient;

class MemberClient
{
    public function __construct(
        protected CamInvClient $client,
    ) {}

    public function list(string $accessToken): array
    {
        return $this->client->withBearerToken($accessToken)->get('/api/v1/member');
    }

    public function validateTaxpayer(string $tin, string $accessToken): array
    {
        return $this->client->withBearerToken($accessToken)->post('/api/v1/validate-taxpayer', [
            'tin' => $tin,
        ]);
    }
}
