<?php

namespace App\GraphQL\Types;

use App\Models\Loan;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class LoanType extends GraphQLType
{
    protected $attributes = [
        'name'        => 'Loan',
        'description' => 'A loan application',
        'model'       => Loan::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type'        => Type::nonNull(Type::string()),
                'description' => 'UUID of the loan',
            ],
            'applicant_name' => [
                'type'        => Type::nonNull(Type::string()),
                'description' => 'Full name of the applicant',
            ],
            'applicant_nim' => [
                'type'        => Type::nonNull(Type::string()),
                'description' => 'NIM of the applicant',
            ],
            'applicant_user_id' => [
                'type'        => Type::string(),
                'description' => 'User ID from the User/Auth Service (optional)',
            ],
            'amount' => [
                'type'        => Type::nonNull(Type::float()),
                'description' => 'Loan amount in IDR',
            ],
            'tenor_months' => [
                'type'        => Type::nonNull(Type::int()),
                'description' => 'Loan duration in months',
            ],
            'purpose' => [
                'type'        => Type::nonNull(Type::string()),
                'description' => 'Purpose of the loan',
            ],
            'status' => [
                'type'        => Type::nonNull(Type::string()),
                'description' => 'pending | approved | rejected | disbursed',
            ],
            'notes' => [
                'type'        => Type::string(),
                'description' => 'Internal notes',
            ],
            'created_at' => [
                'type'        => Type::string(),
                'description' => 'ISO 8601 creation timestamp',
            ],
            'updated_at' => [
                'type'        => Type::string(),
                'description' => 'ISO 8601 last updated timestamp',
            ],
        ];
    }
}
