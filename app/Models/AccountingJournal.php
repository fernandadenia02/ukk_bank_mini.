<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountingJournal extends Model
{
    use HasFactory;

    protected $table = 'accounting_journal';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'account',
        'debit',
        'credit',
        'date',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}