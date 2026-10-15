<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PremierPayFast API Configuration
    |--------------------------------------------------------------------------
    |
    | QR Pay integration credentials and settings.
    | Get your MERCHANT_ID and SECURED_KEY from PremierPayFast onboarding.
    |
    */

    'merchant_id'  => env('PAYFAST_MERCHANT_ID', ''),
    'secured_key'  => env('PAYFAST_SECURED_KEY', ''),
    'hash_key'     => env('PAYFAST_HASH_KEY', ''),    // HMAC-SHA256 signing key

    // API Base URL (change for production)
    'base_url'     => env('PAYFAST_BASE_URL', 'https://uat.premierpayfast.com/api'),

    // QR Page Customization
    'brand_name'   => env('PAYFAST_BRAND_NAME', 'My Store'),
    'brand_color'  => env('PAYFAST_BRAND_COLOR', '#000e8f'),
    'accent_color' => env('PAYFAST_ACCENT_COLOR', '#6bc197'),
    'logo_url'     => env('PAYFAST_LOGO_URL', ''),

    // Callback URL after 3DS (if needed)
    'callback_url' => env('PAYFAST_CALLBACK_URL', '/payfast/callback'),

];
