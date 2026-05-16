<?php

namespace App\GraphQL\Queries;

use App\Models\Loan;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;

class LoansQuery extends Query
{
    protected $attributes = [
        'name'        => 'loans',
        'description' => 'List all loan applications. Optionally filter by status.',
    ];

    public function type(): Type
    {
        return Type::listOf(\GraphQL::type('Loan'));
    }

    public function args(): array
    {
        return [
            'status' => [
                'type'        => Type::string(),
                'description' => 'Filter by status: pending | approved | rejected | disbursed',
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields = null)
    {
        $query = Loan::query();

        if (! empty($args['status'])) {
            $query->where('status', $args['status']);
        }

        return $query->latest()->get();
    }
}
