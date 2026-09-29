<?php

// Auxiliary messages for API error codes (App\Enums\ErrorCode).
// Clients should translate `error.code` themselves; these are fallbacks.
return [
    'BAD_REQUEST' => 'The request is invalid.',
    'UNAUTHENTICATED' => 'Please sign in to continue.',
    'INVALID_CREDENTIALS' => 'The e-mail or password is incorrect.',
    'FORBIDDEN' => 'You do not have permission to do this.',
    'ACCOUNT_DISABLED' => 'Your account has been disabled.',
    'RESOURCE_NOT_FOUND' => 'The requested item was not found.',
    'ROUTE_NOT_FOUND' => 'This endpoint does not exist.',
    'METHOD_NOT_ALLOWED' => 'This method is not allowed.',
    'CONFLICT' => 'This action conflicts with the current state.',
    'VALIDATION_FAILED' => 'Please check the information you entered.',
    'OTP_INVALID' => 'The verification code is incorrect.',
    'OTP_EXPIRED' => 'The verification code has expired. Please request a new one.',
    'LANGUAGE_NOT_SUPPORTED' => 'This language is not supported.',
    'STORE_NOT_AVAILABLE' => 'This store is not accepting orders right now.',
    'OUT_OF_DELIVERY_AREA' => 'This address is outside the store\'s delivery area.',
    'PRODUCT_NOT_AVAILABLE' => 'One or more items are no longer available.',
    'INVALID_STATUS_TRANSITION' => 'This action is not possible for the order\'s current status.',
    'PAYMENT_REQUIRED' => 'The order cannot be accepted until it has been paid.',
    'DELIVERY_PIN_INVALID' => 'The delivery PIN is incorrect.',
    'DELIVERY_PIN_LOCKED' => 'Too many wrong PINs. Please contact the store.',
    'OFFER_NOT_AVAILABLE' => 'This delivery request is no longer available.',
    'PAYMENT_FAILED' => 'The payment could not be completed.',
    'PAYMENT_NOT_REQUIRED' => 'This order does not need an online payment.',
    'INVALID_SIGNATURE' => 'Invalid signature.',
    'TOO_MANY_REQUESTS' => 'Too many attempts. Please wait a moment and try again.',
    'SERVER_ERROR' => 'Something went wrong. Please try again.',
];
