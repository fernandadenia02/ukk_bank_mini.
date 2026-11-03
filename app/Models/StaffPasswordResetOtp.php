<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffPasswordResetOtp extends Model
{
    protected $table = 'staff_password_reset_otps';

    protected $fillable = [
        'user_id',
        'otp_hash',
        'expires_at',
        'attempts',
        'used_at',
    ];

    protected $hidden = [
        'otp_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
