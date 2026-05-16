<?php

namespace Database\Factories;

use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        return [
            'applicant_name'    => $this->faker->name(),
            'applicant_nim'     => $this->faker->numerify('10202240####'),
            'applicant_user_id' => $this->faker->optional(0.6)->uuid(),
            'amount'            => $this->faker->randomElement([1_000_000, 5_000_000, 10_000_000, 50_000_000]),
            'tenor_months'      => $this->faker->randomElement([6, 12, 24, 36]),
            'purpose'           => $this->faker->randomElement(['Modal usaha', 'Renovasi rumah', 'Pendidikan', 'Kesehatan']),
            'status'            => $this->faker->randomElement(Loan::STATUSES),
            'notes'             => $this->faker->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => Loan::STATUS_PENDING]);
    }

    public function approved(): static
    {
        return $this->state(['status' => Loan::STATUS_APPROVED]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => Loan::STATUS_REJECTED]);
    }

    public function disbursed(): static
    {
        return $this->state(['status' => Loan::STATUS_DISBURSED]);
    }
}
