<?php

return [
    'route' => [
        'prefix'     => 'graphql',
        'middleware' => [],
        'controller' => \Rebing\GraphQL\GraphQLController::class . '@query',
    ],

    'graphiql' => [
        'prefix'     => 'graphiql',
        'middleware' => [],
        'controller' => \Rebing\GraphQL\GraphQLController::class . '@graphiql',
        'view'       => 'graphql.graphiql',
        'enabled'    => env('GRAPHIQL_ENABLED', true),
    ],

    'default_schema' => 'default',

    'schemas' => [
        'default' => [
            'query' => [
                'loans' => \App\GraphQL\Queries\LoansQuery::class,
                'loan'  => \App\GraphQL\Queries\LoanQuery::class,
            ],
            'mutation'   => [],
            'middleware' => [],
            'method'     => ['GET', 'POST'],
        ],
    ],

    'types' => [
        'Loan' => \App\GraphQL\Types\LoanType::class,
    ],

    'error_formatter'    => [\Rebing\GraphQL\GraphQL::class, 'formatError'],
    'errors_handler'     => [\Rebing\GraphQL\GraphQL::class, 'handleErrors'],
    'security'           => [
        'query_max_complexity'  => 500,
        'query_max_depth'       => 10,
        'disable_introspection' => false,
    ],
    'pagination_type'         => \Rebing\GraphQL\Support\PaginationType::class,
    'simple_pagination_type'  => \Rebing\GraphQL\Support\SimplePaginationType::class,
    'defaultFieldResolver'    => null,
    'headers'                 => [],
    'json_encoding_options'   => 0,
    'apq'                     => false,
];
