<?php

namespace App\GraphQL\Queries;

use App\Models\Loan;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;

class LoanQuery extends Query
{
    protected $attributes = [
        'name'        => 'loan',
        'description' => 'Get a single loan application by its UUID.',
    ];

    public function type(): Type
    {
        return \GraphQL::type('Loan');
    }

    public function args(): array
    {
        return [
            'id' => [
                'type'        => Type::nonNull(Type::string()),
                'description' => 'UUID of the loan',
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields = null): ?Loan
    {
        return Loan::find($args['id']);
    }
}
