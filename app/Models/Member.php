<?php

namespace App\Models;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'nis',
        'name',
        'class',
        'location',
    ];

    public function customer()
    {
        return $this->hasOne(Customer::class, 'nis', 'nis');
    }
}
