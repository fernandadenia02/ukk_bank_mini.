<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    // Karena tabel tidak memiliki created_at dan updated_at
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'teller_id',
        'transaction_type',
        'amount',
        'beginning_balance',
        'ending_balance',
        'date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'beginning_balance' => 'decimal:2',
        'ending_balance' => 'decimal:2',
        'date' => 'datetime',
    ];

    /**
     * Relasi ke Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Relasi ke Teller (User)
     */
    public function teller()
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    /**
     * Relasi ke Accounting Journal
     */
    public function journals()
    {
        return $this->hasMany(AccountingJournal::class, 'transaction_id');
    }
}