<?php

namespace CamInv\EInvoice\Document;

use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Enums\DocumentType;
use CamInv\EInvoice\Support\HasTokenRefresh;
use CamInv\EInvoice\Token\TokenManager;

/**
 * Manages document operations — submit, send, list, retrieve, accept, reject,
 * update status, and download PDF/XML.
 *
 * When a merchant ID is set via {@see forMerchant()}, access tokens are
 * resolved automatically through {@see TokenManager}. Passing a raw
 * `$accessToken` directly to any method bypasses this auto‑resolution,
 * preserving backward compatibility.
 *
 * @see https://developer.e-invoice.gov.kh/api-reference/submit-document
 * @see https://developer.e-invoice.gov.kh/api-reference/send-document
 * @see https://developer.e-invoice.gov.kh/api-reference/list-documents
 * @see https://developer.e-invoice.gov.kh/api-reference/get-document-detail
 * @see https://developer.e-invoice.gov.kh/api-reference/accept-document
 * @see https://developer.e-invoice.gov.kh/api-reference/reject-document
 * @see https://developer.e-invoice.gov.kh/api-reference/update-document-status
 * @see https://developer.e-invoice.gov.kh/api-reference/download-document-pdf
 * @see https://developer.e-invoice.gov.kh/api-reference/download-document-XML
 */
class DocumentClient
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
                'No merchant ID set on DocumentClient. Call forMerchant() first or pass an access token explicitly.'
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
     * Submit a document for validation.
     *
     * Submits a UBL XML document (base64-encoded) for CamInvoice validation.
     * Processing is asynchronous; results are delivered via webhook.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/submit-document
     *
     * @param  DocumentType  $documentType  The type of document (e.g. INVOICE, CREDIT_NOTE).
     * @param  string        $xml           Raw UBL XML content of the document.
     * @param  string|null   $accessToken   Bearer access token. Auto‑resolved when null and forMerchant() was called.
     * @return array                        Response with documents and failed_documents lists.
     */
    public function submit(DocumentType $documentType, string $xml, ?string $accessToken = null): array
    {
        return $this->withTokenRefresh(function () use ($documentType, $xml, $accessToken) {
            $token = $this->resolveToken($accessToken);
            $base64 = base64_encode($xml);

            return $this->client->withBearerToken($token)->post('/api/v1/document', [
                'documents' => [
                    [
                        'document_type' => $documentType->value,
                        'document' => $base64,
                    ],
                ],
            ]);
        });
    }

    /**
     * Send validated documents to customers on CamInvoice.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/send-document
     *
     * @param  string[]     $documentIds   List of CamInvoice document IDs to send.
     * @param  string|null  $accessToken   Bearer access token. Auto‑resolved when null and forMerchant() was called.
     * @return array                       Response with sent_documents and failed_documents lists.
     */
    public function send(array $documentIds, ?string $accessToken = null): array
    {
        return $this->withTokenRefresh(function () use ($documentIds, $accessToken) {
            $token = $this->resolveToken($accessToken);

            return $this->client->withBearerToken($token)->post('/api/v1/document/send', [
                'documents' => $documentIds,
            ]);
        });
    }

    /**
     * Accept received documents from customers.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/accept-document
     *
     * @param  string[]     $documentIds   List of document IDs to accept.
     * @param  string|null  $accessToken   Bearer access token. Auto‑resolved when null and forMerchant() was called.
     * @return array                       Response with accepted_documents and failed_documents lists.
     */
    public function accept(array $documentIds, ?string $accessToken = null): array
    {
        return $this->withTokenRefresh(function () use ($documentIds, $accessToken) {
            $token = $this->resolveToken($accessToken);

            return $this->client->withBearerToken($token)->post('/api/v1/document/accept', [
                'documents' => $documentIds,
            ]);
        });
    }

    /**
     * Reject received documents from customers.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/reject-document
     *
     * @param  string[]     $documentIds   List of document IDs to reject.
     * @param  string|null  $accessToken   Bearer access token. Auto‑resolved when null and forMerchant() was called.
     * @param  string|null  $reason        Reason for rejection. Optional in the SDK.
     * @return array                       Response with rejected_documents and failed_documents lists.
     */
    public function reject(array $documentIds, ?string $accessToken = null, ?string $reason = null): array
    {
        return $this->withTokenRefresh(function () use ($documentIds, $accessToken, $reason) {
            $token = $this->resolveToken($accessToken);

            return $this->client->withBearerToken($token)->post('/api/v1/document/reject', array_filter([
                'documents' => $documentIds,
                'reason' => $reason,
            ], fn ($v) => $v !== null));
        });
    }

    /**
     * Update the status of one or more documents.
     *
     * Valid statuses: VALID, DELIVERED, ACKNOWLEDGED, IN_PROCESS, UNDER_QUERY,
     * CONDITIONALLY_ACCEPTED, ACCEPTED, REJECTED, PAID.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/update-document-status
     *
     * @param  string[]     $documentIds   List of document IDs to update.
     * @param  string       $status        The new status value.
     * @param  string|null  $accessToken   Bearer access token. Auto‑resolved when null and forMerchant() was called.
     * @return array                       Response with updated and failed_documents lists.
     */
    public function updateStatus(array $documentIds, string $status, ?string $accessToken = null): array
    {
        return $this->withTokenRefresh(function () use ($documentIds, $status, $accessToken) {
            $token = $this->resolveToken($accessToken);

            return $this->client->withBearerToken($token)->put('/api/v1/document/status', [
                'documents' => $documentIds,
                'status' => $status,
            ]);
        });
    }

    /**
     * Download the UBL XML file of a document.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/download-document-XML
     *
     * @param  string       $documentId    The unique document identifier.
     * @param  string|null  $accessToken   Bearer access token. Auto‑resolved when null and forMerchant() was called.
     * @return string                      Raw XML content in UBL format.
     */
    public function getXml(string $documentId, ?string $accessToken = null): string
    {
        return $this->withTokenRefresh(function () use ($documentId, $accessToken) {
            $token = $this->resolveToken($accessToken);

            return $this->client->withBearerToken($token)->getRaw("/api/v1/document/{$documentId}/xml");
        });
    }

    /**
     * Download the PDF file of a document.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/download-document-pdf
     *
     * @param  string       $documentId    The unique document identifier.
     * @param  string|null  $accessToken   Bearer access token. Auto‑resolved when null and forMerchant() was called.
     * @return string                      Raw PDF binary content.
     */
    public function getPdf(string $documentId, ?string $accessToken = null): string
    {
        return $this->withTokenRefresh(function () use ($documentId, $accessToken) {
            $token = $this->resolveToken($accessToken);

            return $this->client->withBearerToken($token)->getRaw("/api/v1/document/{$documentId}/pdf");
        });
    }

    /**
     * Get detailed information about a specific document.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/get-document-detail
     *
     * @param  string       $documentId    The unique document identifier.
     * @param  string|null  $accessToken   Bearer access token. Auto‑resolved when null and forMerchant() was called.
     * @return array                       Document detail with supplier, customer, status, amounts, etc.
     */
    public function getDetail(string $documentId, ?string $accessToken = null): array
    {
        return $this->withTokenRefresh(function () use ($documentId, $accessToken) {
            $token = $this->resolveToken($accessToken);

            return $this->client->withBearerToken($token)->get("/api/v1/document/{$documentId}");
        });
    }

    /**
     * List documents with optional filtering and pagination.
     *
     * @see https://developer.e-invoice.gov.kh/api-reference/list-documents
     *
     * @param  string|null  $accessToken   Bearer access token. Auto‑resolved when null and forMerchant() was called.
     * @param  string       $type          Type of documents: 'send' or 'received'. Default 'send'.
     * @param  int          $page          Page number for pagination. Default 1.
     * @param  int          $size          Number of documents per page. Default 20.
     * @param  string|null  $documentType  Optional filter by document type (e.g. INVOICE).
     * @return array                       Paginated response with data and pagination info.
     */
    public function list(?string $accessToken = null, string $type = 'send', int $page = 1, int $size = 20, ?string $documentType = null): array
    {
        return $this->withTokenRefresh(function () use ($accessToken, $type, $page, $size, $documentType) {
            $token = $this->resolveToken($accessToken);

            return $this->client->withBearerToken($token)->get('/api/v1/document', array_filter([
                'type' => $type,
                'page' => $page,
                'size' => $size,
                'document_type' => $documentType,
            ], fn ($v) => $v !== null));
        });
    }
}
