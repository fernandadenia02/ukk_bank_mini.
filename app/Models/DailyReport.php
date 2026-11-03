<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DailyReport extends Model
{
    use HasFactory;

    protected $table = 'daily_reports';

    public $timestamps = false;

    protected $fillable = [

        'teller_id',

        'supervisor_id',

        'date',

        'beginning_balance',

        'total_deposit',

        'total_withdrawals',

        'ending_balance',

        'physical_money',

        'difference',

        'status',

        'approved_at',

    ];

    public function teller()
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}