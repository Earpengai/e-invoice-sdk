<?php

namespace CamInv\EInvoice\Document;

use CamInv\EInvoice\Client\CamInvClient;

class DocumentClient
{
    public function __construct(
        protected CamInvClient $client,
    ) {}

    public function submit(string $xml, string $accessToken, int $ttl = 2592000): array
    {
        $base64 = base64_encode($xml);

        return $this->client->withBearerToken($accessToken)->post('/api/v1/document', [
            'documents' => [
                [
                    'format' => 'UBL',
                    'document' => $base64,
                    'long_time_deliver' => $ttl,
                ],
            ],
        ]);
    }

    public function send(string $documentId, string $endpointId, string $accessToken): array
    {
        return $this->client->withBearerToken($accessToken)->post('/api/v1/document/send', [
            'documents' => [
                [
                    'document_id' => $documentId,
                    'endpoint_id' => $endpointId,
                ],
            ],
        ]);
    }

    public function accept(string $documentId, string $accessToken): array
    {
        return $this->client->withBearerToken($accessToken)->post("/api/v1/document/{$documentId}/accept");
    }

    public function reject(string $documentId, string $accessToken, ?string $reason = null): array
    {
        $data = [];

        if ($reason) {
            $data['reason'] = $reason;
        }

        return $this->client->withBearerToken($accessToken)->post("/api/v1/document/{$documentId}/reject", $data);
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
}
