<?php

namespace App\Enums;

/**
 * Machine-readable error codes returned in `error.code`.
 * Clients translate `error.<code in lower case>`; the backend only adds an auxiliary
 * localized message from lang/<locale>/errors.php.
 */
enum ErrorCode: string
{
    case BAD_REQUEST = 'BAD_REQUEST';
    case UNAUTHENTICATED = 'UNAUTHENTICATED';
    case INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    case FORBIDDEN = 'FORBIDDEN';
    case ACCOUNT_DISABLED = 'ACCOUNT_DISABLED';
    case RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    case ROUTE_NOT_FOUND = 'ROUTE_NOT_FOUND';
    case METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
    case CONFLICT = 'CONFLICT';
    case VALIDATION_FAILED = 'VALIDATION_FAILED';
    case OTP_INVALID = 'OTP_INVALID';
    case OTP_EXPIRED = 'OTP_EXPIRED';
    case LANGUAGE_NOT_SUPPORTED = 'LANGUAGE_NOT_SUPPORTED';
    case STORE_NOT_AVAILABLE = 'STORE_NOT_AVAILABLE';
    case OUT_OF_DELIVERY_AREA = 'OUT_OF_DELIVERY_AREA';
    case PRODUCT_NOT_AVAILABLE = 'PRODUCT_NOT_AVAILABLE';
    case INVALID_STATUS_TRANSITION = 'INVALID_STATUS_TRANSITION';
    case PAYMENT_REQUIRED = 'PAYMENT_REQUIRED';
    case DELIVERY_PIN_INVALID = 'DELIVERY_PIN_INVALID';
    case DELIVERY_PIN_LOCKED = 'DELIVERY_PIN_LOCKED';
    case OFFER_NOT_AVAILABLE = 'OFFER_NOT_AVAILABLE';
    case TOO_MANY_REQUESTS = 'TOO_MANY_REQUESTS';
    case SERVER_ERROR = 'SERVER_ERROR';

    public function httpStatus(): int
    {
        return match ($this) {
            self::BAD_REQUEST => 400,
            self::UNAUTHENTICATED, self::INVALID_CREDENTIALS => 401,
            self::FORBIDDEN, self::ACCOUNT_DISABLED => 403,
            self::RESOURCE_NOT_FOUND, self::ROUTE_NOT_FOUND => 404,
            self::METHOD_NOT_ALLOWED => 405,
            self::CONFLICT, self::OFFER_NOT_AVAILABLE => 409,
            self::VALIDATION_FAILED, self::OTP_INVALID, self::OTP_EXPIRED,
            self::LANGUAGE_NOT_SUPPORTED, self::STORE_NOT_AVAILABLE, self::OUT_OF_DELIVERY_AREA,
            self::PRODUCT_NOT_AVAILABLE, self::INVALID_STATUS_TRANSITION, self::PAYMENT_REQUIRED,
            self::DELIVERY_PIN_INVALID, self::DELIVERY_PIN_LOCKED => 422,
            self::TOO_MANY_REQUESTS => 429,
            self::SERVER_ERROR => 500,
        };
    }

    public function message(array $replace = []): string
    {
        return __('errors.'.$this->value, $replace);
    }
}
