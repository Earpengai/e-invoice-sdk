<?php

namespace CamInv\EInvoice\Member;

use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Support\HasTokenRefresh;
use CamInv\EInvoice\Token\TokenManager;

/**
 * Manages CamInvoice member operations — listing, detail lookup, and taxpayer validation.
 *
 * When a merchant ID is set via {@see forMerchant()}, access tokens are
 * resolved automatically through {@see TokenManager}. Passing a raw
 * `$accessToken` directly to any method bypasses this auto‑resolution,
 * preserving backward compatibility.
 *
 * @see https://developer.e-invoice.gov.kh/api-reference/list-caminvoice-member
 * @see https://developer.e-invoice.gov.kh/api-reference/get-member-detail
 * @see https://developer.e-invoice.gov.kh/api-reference/validate-taxpayer
 */
class MemberClient
{
    use HasTokenRefresh;

    protected ?string $merchantId = null;

    public function __construct(
        protected CamInvClient $client,
        protected TokenManager $tokenManager,
    ) {}

    /**
     * Scope this client to a specific merchant for automatic token resolution.
     *
     * @param  string  $merchantId  The merchant identifier used as the storage key.
     * @return $this
     */
    public function forMerchant(string $merchantId): static
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return TokenManager
     */
    protected function tokenManager(): TokenManager
    {
        return $this->tokenManager;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function merchantId(): string
    {
        if ($this->merchantId === null) {
            throw new \RuntimeException(
                'No merchant ID set on MemberClient. Call forMerchant() first or pass an access token explicitly.'
            );
        }

        return $this->merchantId;
    }

    /**
     * Resolve the access token to use: explicit token takes priority,
     * otherwise auto‑resolve through the TokenManager.
     *
     * @param  string|null  $accessToken  Explicit token (if any).
     * @return string
     */
    protected function resolveToken(?string $accessToken): string
    {
        return $accessToken ?? $this->tokenManager->getValidAccessToken($this->merchantId());
    }

    /**
     * List CamInvoice members by keyword search.
     *
     * Searches businesses by name, TIN, or other identifiers.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/list-caminvoice-member
     *
     * @param  string|null  $accessToken  Bearer access token. Auto‑resolved when null and forMerchant() was called.
     * @param  string       $keyword      Search keyword (name, TIN, etc.). Defaults to empty string.
     * @param  int          $limit        Maximum number of results. Defaults to 10.
     * @return array                      Array of business objects with fields: endpoint_id, entity_id,
     *                                    tin, company_name_en, company_name_kh, entity_type.
     */
    public function list(?string $accessToken = null, string $keyword = '', int $limit = 10): array
    {
        return $this->withTokenRefresh(function () use ($accessToken, $keyword, $limit) {
            $token = $this->resolveToken($accessToken);

            return $this->client->withBearerToken($token)->get('/api/v1/business', [
                'keyword' => $keyword,
                'limit'   => $limit,
            ]);
        });
    }

    /**
     * Get detailed information for a specific member by their endpoint ID.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/get-member-detail
     *
     * @param  string       $endpointId     The CamInvoice ID of the business (e.g. KHUID00001234).
     * @param  string|null  $accessToken    Bearer access token. Auto‑resolved when null and forMerchant() was called.
     * @return array                        Business detail with fields: endpoint_id, company_name_en,
     *                                      company_name_kh, entity_type, entity_id, tin, country.
     */
    public function get(string $endpointId, ?string $accessToken = null): array
    {
        return $this->withTokenRefresh(function () use ($endpointId, $accessToken) {
            $token = $this->resolveToken($accessToken);

            return $this->client->withBearerToken($token)->get("/api/v1/business/{$endpointId}");
        });
    }

    /**
     * Validate taxpayer information against the registry.
     *
     * Verifies the taxpayer's single ID, TIN, and company names
     * match the official registration records.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/validate-taxpayer
     *
     * @param  string       $tin             Tax Identification Number.
     * @param  string       $singleId        The single ID of the taxpayer.
     * @param  string       $companyNameEn   Company name in English.
     * @param  string       $companyNameKh   Company name in Khmer.
     * @param  string|null  $accessToken     Bearer access token. Auto‑resolved when null and forMerchant() was called.
     * @return array{is_valid: bool}
     */
    public function validateTaxpayer(string $tin, string $singleId, string $companyNameEn, string $companyNameKh, ?string $accessToken = null): array
    {
        return $this->withTokenRefresh(function () use ($tin, $singleId, $companyNameEn, $companyNameKh, $accessToken) {
            $token = $this->resolveToken($accessToken);

            return $this->client->withBearerToken($token)->post('/api/v1/business/validate', [
                'tin'             => $tin,
                'single_id'       => $singleId,
                'company_name_en' => $companyNameEn,
                'company_name_kh' => $companyNameKh,
            ]);
        });
    }
}
