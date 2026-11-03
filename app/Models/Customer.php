<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'account_number',
        'nis',
        'name',
        'class',
        'location',
        'telephone_number',
        'pin',
        'balance',
        'qrcode',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'customer_id');
    }
}