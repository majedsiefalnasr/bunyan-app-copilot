<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ApiErrorCode;
use RuntimeException;

class ApiException extends RuntimeException
{
    public function __construct(
        private readonly ApiErrorCode $errorCode,
        string $message = '',
        private readonly ?array $details = null,
    ) {
        parent::__construct($message ?: $errorCode->defaultMessage(), $errorCode->httpStatus());
    }

    public static function make(ApiErrorCode $code, ?string $message = null, ?array $details = null): self
    {
        return new self($code, $message ?? '', $details);
    }

    public function getErrorCode(): ApiErrorCode
    {
        return $this->errorCode;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function getStatusCode(): int
    {
        return $this->errorCode->httpStatus();
    }
}
