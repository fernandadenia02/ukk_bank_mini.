<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalAppointment extends Model
{
    protected $fillable = [
        'customer_id',
        'withdrawal_date',
        'amount',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'consumed_at',
    ];

    protected $casts = [
        'withdrawal_date' => 'date',
        'amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
