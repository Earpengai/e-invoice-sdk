<?php

namespace CamInv\EInvoice\Document;

use CamInv\EInvoice\Client\CamInvClient;
use CamInv\EInvoice\Enums\DocumentType;

class DocumentClient
{
    public function __construct(
        protected CamInvClient $client,
    ) {}

    public function submit(DocumentType $documentType, string $xml, string $accessToken): array
    {
        $base64 = base64_encode($xml);

        return $this->client->withBearerToken($accessToken)->post('/api/v1/document', [
            'documents' => [
                [
                    'document_type' => $documentType->value,
                    'document' => $base64,
                ],
            ],
        ]);
    }

    public function send(array $documentIds, string $accessToken): array
    {
        return $this->client->withBearerToken($accessToken)->post('/api/v1/document/send', [
            'documents' => $documentIds,
        ]);
    }

    public function accept(array $documentIds, string $accessToken): array
    {
        return $this->client->withBearerToken($accessToken)->post('/api/v1/document/accept', [
            'documents' => $documentIds,
        ]);
    }

    public function reject(array $documentIds, string $accessToken, ?string $reason = null): array
    {
        return $this->client->withBearerToken($accessToken)->post('/api/v1/document/reject', array_filter([
            'documents' => $documentIds,
            'reason' => $reason,
        ], fn ($v) => $v !== null));
    }

    public function updateStatus(array $documentIds, string $status, string $accessToken): array
    {
        return $this->client->withBearerToken($accessToken)->put('/api/v1/document/status', [
            'documents' => $documentIds,
            'status' => $status,
        ]);
    }

    public function getXml(string $documentId, string $accessToken): string
    {
        return $this->client->withBearerToken($accessToken)->getRaw("/api/v1/document/{$documentId}/xml");
    }

    public function getPdf(string $documentId, string $accessToken): string
    {
        return $this->client->withBearerToken($accessToken)->getRaw("/api/v1/document/{$documentId}/pdf");
    }

    public function getDetail(string $documentId, string $accessToken): array
    {
        return $this->client->withBearerToken($accessToken)->get("/api/v1/document/{$documentId}");
    }

    public function list(string $accessToken, string $type = 'send', int $page = 1, int $size = 20, ?string $documentType = null): array
    {
        return $this->client->withBearerToken($accessToken)->get('/api/v1/document', array_filter([
            'type' => $type,
            'page' => $page,
            'size' => $size,
            'document_type' => $documentType,
        ], fn ($v) => $v !== null));
    }
}
