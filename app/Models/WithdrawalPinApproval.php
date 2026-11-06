<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalPinApproval extends Model
{
    protected $fillable = [
        'customer_id',
        'teller_id',
        'amount',
        'status',
        'expires_at',
        'approved_at',
        'consumed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'approved_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function teller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
