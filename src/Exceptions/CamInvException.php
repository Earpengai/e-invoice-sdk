<?php

namespace CamInv\EInvoice\Exceptions;

use RuntimeException;

class CamInvException extends RuntimeException
{
    public function __construct(
        string $message = '',
        protected int $statusCode = 0,
        protected ?array $responseBody = null,
    ) {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}
