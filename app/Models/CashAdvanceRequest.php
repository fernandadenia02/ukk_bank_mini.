<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashAdvanceRequest extends Model
{
    protected $table = 'cash_advance_requests';

    protected $fillable = [
        'daily_report_id',
        'teller_id',
        'amount',
        'status',
        'rejection_reason',
        'requested_at',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function report()
    {
        return $this->belongsTo(DailyReport::class, 'daily_report_id');
    }

    public function teller()
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
