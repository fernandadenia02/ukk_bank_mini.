<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (mb_strlen($value) < 12) {
            $fail('Password minimal harus 12 karakter.');
        }

        if (! preg_match('/[A-Z]/', $value)) {
            $fail('Password harus mengandung minimal 1 huruf kapital (A-Z).');
        }

        if (! preg_match('/[0-9]/', $value)) {
            $fail('Password harus mengandung minimal 1 angka (0-9).');
        }

        if (! preg_match('/[^A-Za-z0-9]/', $value)) {
            $fail('Password harus mengandung minimal 1 simbol khusus (contoh: !@#$%).');
        }
    }
}
