<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankStatus extends Model
{
    protected $fillable = [
        'is_open',
    ];

    protected $casts = [
        'is_open' => 'boolean',
    ];

    public $timestamps = false;

    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            ['is_open' => true]
        );
    }
}
