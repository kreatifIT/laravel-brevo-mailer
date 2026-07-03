<?php

namespace Kreatif\BrevoMailer\Exceptions;

use RuntimeException;
use Throwable;

class BrevoApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly string $responseBody,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
