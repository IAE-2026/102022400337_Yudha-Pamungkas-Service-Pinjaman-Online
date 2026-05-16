<?php

namespace Database\Seeders;

use App\Models\Loan;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Loan::factory()->count(10)->create();

        Loan::factory()->approved()->create([
            'applicant_name' => 'Demo Approved',
            'applicant_nim'  => '102022400001',
            'amount'         => 25_000_000,
            'tenor_months'   => 24,
            'purpose'        => 'Modal usaha',
            'notes'          => 'Approved by admin',
        ]);

        Loan::factory()->pending()->create([
            'applicant_name' => 'Demo Pending',
            'applicant_nim'  => '102022400002',
            'amount'         => 10_000_000,
            'tenor_months'   => 12,
            'purpose'        => 'Renovasi rumah',
        ]);

        Loan::factory()->disbursed()->create([
            'applicant_name' => 'Demo Disbursed',
            'applicant_nim'  => '102022400003',
            'amount'         => 5_000_000,
            'tenor_months'   => 6,
            'purpose'        => 'Kebutuhan mendesak',
            'notes'          => 'Disbursed; payment schedule created.',
        ]);
    }
}
