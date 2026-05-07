<?php

namespace CamInv\EInvoice\Member;

use CamInv\EInvoice\Client\CamInvClient;

class MemberClient
{
    public function __construct(
        protected CamInvClient $client,
    ) {}

    public function list(string $accessToken, string $keyword = '', int $limit = 10): array
    {
        return $this->client->withBearerToken($accessToken)->get('/api/v1/business', [
            'keyword' => $keyword,
            'limit'   => $limit,
        ]);
    }

    public function get(string $accessToken, string $endpointId): array
    {
        return $this->client->withBearerToken($accessToken)->get("/api/v1/business/{$endpointId}");
    }

    public function validateTaxpayer(string $tin, string $accessToken, string $singleId, string $companyNameEn, string $companyNameKh): array
    {
        return $this->client->withBearerToken($accessToken)->post('/api/v1/business/validate', [
            'tin'             => $tin,
            'single_id'       => $singleId,
            'company_name_en' => $companyNameEn,
            'company_name_kh' => $companyNameKh,
        ]);
    }
}
