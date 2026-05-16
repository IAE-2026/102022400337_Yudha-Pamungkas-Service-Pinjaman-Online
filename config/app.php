<?php

return [
    'name'    => env('APP_NAME', 'Loan Service'),
    'env'     => env('APP_ENV', 'production'),
    'debug'   => (bool) env('APP_DEBUG', false),
    'url'     => env('APP_URL', 'http://localhost:8001'),
    'key'     => env('APP_KEY'),
    'cipher'  => 'AES-256-CBC',

    // IAE API key — must match NIM
    'iae_key' => env('IAE_KEY', '102022400337'),

    'timezone'        => 'Asia/Jakarta',
    'locale'          => 'en',
    'fallback_locale' => 'en',

    'providers' => \Illuminate\Support\ServiceProvider::defaultProviders()->merge([
        \Darkaonline\L5Swagger\L5SwaggerServiceProvider::class,
        \Rebing\GraphQL\GraphQLServiceProvider::class,
    ])->toArray(),
];
