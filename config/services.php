<?php

return [
 /*
    |--------------------------------------------------------------------------
    | User/Auth Service
    |--------------------------------------------------------------------------
    | Used by UserAuthService to validate applicant_user_id on loan creation.
    | Set USER_AUTH_SERVICE_URL in .env to the actual IP of that teammate's laptop.
    */ 

    'user_auth' => [
        'url' => env('USER_AUTH_SERVICE_URL', ''),
    ],
    /*
    |--------------------------------------------------------------------------
    | Payment Service
    |--------------------------------------------------------------------------
    | Used by PaymentService to create a repayment schedule when a loan
    | status is changed to "disbursed".
    | Set PAYMENT_SERVICE_URL in .env to the actual IP of that teammate's laptop.
    */
    'payment' => [
        'url' => env('PAYMENT_SERVICE_URL', ''),
    ],

    'iae' => [
        'base_url' => env('IAE_BASE_URL'),
        'api_key' => env('IAE_API_KEY'),
        'team_id' => env('IAE_TEAM_ID'),
        'warga_email' => env('IAE_WARGA_EMAIL'),
        'warga_password' => env('IAE_WARGA_PASSWORD'),
        'service_name' => env('IAE_SERVICE_NAME'),
    ],

];

    
