<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Applicant info
            $table->string('applicant_name');
            $table->string('applicant_nim', 50);
            // Optional: user ID from User/Auth Service (no FK — different service/DB)
            $table->string('applicant_user_id', 100)->nullable();

            // Loan terms
            $table->decimal('amount', 15, 2);
            $table->unsignedInteger('tenor_months');
            $table->string('purpose');

            // Workflow
            $table->enum('status', ['pending', 'approved', 'rejected', 'disbursed'])
                  ->default('pending');
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('applicant_nim');
            $table->index('applicant_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
