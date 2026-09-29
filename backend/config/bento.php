<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    | Active languages live in the `languages` table. `default_locale` is the
    | final fallback and is used when the table is empty (e.g. before seeding).
    */
    'default_locale' => env('DEFAULT_LOCALE', 'en'),
    'languages_cache_ttl' => (int) env('LANGUAGES_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Phone numbers
    |--------------------------------------------------------------------------
    */
    'phone' => [
        'default_country_code' => env('PHONE_DEFAULT_COUNTRY_CODE', '265'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP
    |--------------------------------------------------------------------------
    | Test mode uses a fixed code and is always ignored when APP_ENV=production.
    */
    'otp' => [
        'test_mode' => (bool) env('OTP_TEST_MODE', false),
        'test_code' => env('OTP_TEST_CODE', '123456'),
        'length' => 6,
        'ttl_seconds' => (int) env('OTP_TTL_SECONDS', 300),
        'resend_seconds' => (int) env('OTP_RESEND_SECONDS', 60),
        'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'sender_id' => env('SMS_SENDER_ID', 'MWBENTO'),
        'max_segments' => (int) env('SMS_MAX_SEGMENTS', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    | Money is stored as integer minor units. `exponent` = number of minor digits.
    */
    'default_currency' => env('DEFAULT_CURRENCY', 'MWK'),
    'currencies' => [
        'MWK' => ['exponent' => 2],
    ],

    /*
    |--------------------------------------------------------------------------
    | Order pricing (minor units)
    |--------------------------------------------------------------------------
    | Flat service fee per order; moves to per-store settings in PHASE 6.
    */
    'pricing' => [
        'service_fee' => (int) env('SERVICE_FEE', 0),
        'max_item_quantity' => 20,
        'schedule_max_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Driver dispatch
    |--------------------------------------------------------------------------
    */
    'dispatch' => [
        // Seconds a driver has to accept an offer before it goes to the next driver.
        'offer_ttl_seconds' => (int) env('DISPATCH_OFFER_TTL', 60),
        // Drivers whose last GPS point is older than this are not offered deliveries.
        'location_max_age_minutes' => (int) env('DISPATCH_LOCATION_MAX_AGE', 10),
        'max_pin_attempts' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Geo / delivery
    |--------------------------------------------------------------------------
    | Upper bound used for the SQL bounding-box prefilter in store search.
    */
    'geo' => [
        'max_zone_radius_km' => (float) env('GEO_MAX_ZONE_RADIUS_KM', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third-party integrations (used from later phases)
    |--------------------------------------------------------------------------
    */
    'payment' => [
        'gateway' => env('PAYMENT_GATEWAY', 'fake'),
    ],

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'credentials' => env('FIREBASE_CREDENTIALS'),
    ],
];
