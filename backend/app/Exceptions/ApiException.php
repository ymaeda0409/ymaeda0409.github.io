<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use RuntimeException;

/**
 * Domain exception rendered as the unified error envelope.
 */
class ApiException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>|null  $fields
     */
    public function __construct(
        public readonly ErrorCode $errorCode,
        public readonly ?array $fields = null,
        public readonly array $replace = [],
    ) {
        parent::__construct($errorCode->value);
    }

    public static function of(ErrorCode $code, array $replace = []): self
    {
        return new self($code, null, $replace);
    }
}
