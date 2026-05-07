<?php

namespace CamInv\EInvoice\Member;

use CamInv\EInvoice\Client\CamInvClient;

/**
 * Manages CamInvoice member operations — listing, detail lookup, and taxpayer validation.
 *
 * @see https://developer.e-invoice.gov.kh/api-reference/list-caminvoice-member
 * @see https://developer.e-invoice.gov.kh/api-reference/get-member-detail
 * @see https://developer.e-invoice.gov.kh/api-reference/validate-taxpayer
 */
class MemberClient
{
    public function __construct(
        protected CamInvClient $client,
    ) {}

    /**
     * List CamInvoice members by keyword search.
     *
     * Searches businesses by name, TIN, or other identifiers.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/list-caminvoice-member
     *
     * @param  string  $accessToken  Bearer access token for authorization.
     * @param  string  $keyword      Search keyword (name, TIN, etc.). Defaults to empty string.
     * @param  int     $limit        Maximum number of results. Defaults to 10.
     * @return array                 Array of business objects with fields: endpoint_id, entity_id,
     *                               tin, company_name_en, company_name_kh, entity_type.
     */
    public function list(string $accessToken, string $keyword = '', int $limit = 10): array
    {
        return $this->client->withBearerToken($accessToken)->get('/api/v1/business', [
            'keyword' => $keyword,
            'limit'   => $limit,
        ]);
    }

    /**
     * Get detailed information for a specific member by their endpoint ID.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/get-member-detail
     *
     * @param  string  $accessToken    Bearer access token for authorization.
     * @param  string  $endpointId     The CamInvoice ID of the business (e.g. KHUID00001234).
     * @return array                   Business detail with fields: endpoint_id, company_name_en,
     *                                 company_name_kh, entity_type, entity_id, tin, country.
     */
    public function get(string $accessToken, string $endpointId): array
    {
        return $this->client->withBearerToken($accessToken)->get("/api/v1/business/{$endpointId}");
    }

    /**
     * Validate taxpayer information against the registry.
     *
     * Verifies the taxpayer's single ID, TIN, and company names
     * match the official registration records.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/validate-taxpayer
     *
     * @param  string  $tin             Tax Identification Number.
     * @param  string  $accessToken     Bearer access token for authorization.
     * @param  string  $singleId        The single ID of the taxpayer.
     * @param  string  $companyNameEn   Company name in English.
     * @param  string  $companyNameKh   Company name in Khmer.
     * @return array{is_valid: bool}
     */
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
