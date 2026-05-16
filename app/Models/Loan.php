<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'applicant_name',
        'applicant_nim',
        'applicant_user_id',
        'amount',
        'tenor_months',
        'purpose',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'tenor_months' => 'integer',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    // ─── Status constants ────────────────────────────────────────────────────

    const STATUS_PENDING   = 'pending';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_DISBURSED = 'disbursed';

    const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_DISBURSED,
    ];

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isDisbursed(): bool
    {
        return $this->status === self::STATUS_DISBURSED;
    }
}
