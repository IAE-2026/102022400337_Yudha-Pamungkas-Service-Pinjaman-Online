<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service'        => 'Loan Service',
        'nim'            => '102022400337',
        'version'        => 'v1',
        'documentation'  => url('/api/documentation'),
        'graphql'        => url('/graphql'),
        'graphiql'       => url('/graphiql'),
        'health'         => url('/api/v1/loans/health'),
    ]);
});
