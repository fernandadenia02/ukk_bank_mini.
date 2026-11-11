<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'customers';

    const UPDATED_AT = null;

    protected $fillable = [
        'account_number',
        'nis',
        'name',
        'email',
        'username',
        'password',
        'status',
        'class',
        'location',
        'telephone_number',
        'pin',
        'balance',
        'qrcode',
    ];

    protected $hidden = [
        'pin',
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'balance' => 'decimal:2',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'customer_id');
    }
}