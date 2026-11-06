<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;

class CredentialLockout
{
    private const MAX_ATTEMPTS = 10;

    private const LOCKOUT_SECONDS = 1800;

    public function remaining(string $scope, string $identifier): int
    {
        return RateLimiter::availableIn($this->lockKey($scope, $identifier));
    }

    public function failedAttempt(string $scope, string $identifier): int
    {
        $attemptKey = $this->attemptKey($scope, $identifier);
        $attempts = RateLimiter::hit($attemptKey, self::LOCKOUT_SECONDS);

        if (
            $attempts >= self::MAX_ATTEMPTS &&
            !RateLimiter::tooManyAttempts($this->lockKey($scope, $identifier), 1)
        ) {
            RateLimiter::hit($this->lockKey($scope, $identifier), self::LOCKOUT_SECONDS);
            RateLimiter::clear($attemptKey);
        }

        return $this->remaining($scope, $identifier);
    }

    public function clear(string $scope, string $identifier): void
    {
        RateLimiter::clear($this->attemptKey($scope, $identifier));
        RateLimiter::clear($this->lockKey($scope, $identifier));
    }

    public function message(string $credential, int $remainingSeconds): string
    {
        $minutes = max(1, (int) ceil($remainingSeconds / 60));

        return "Terlalu banyak percobaan {$credential} salah. Coba lagi dalam {$minutes} menit.";
    }

    private function attemptKey(string $scope, string $identifier): string
    {
        return 'credential-attempts:' . $this->fingerprint($scope, $identifier);
    }

    private function lockKey(string $scope, string $identifier): string
    {
        return 'credential-lockout:' . $this->fingerprint($scope, $identifier);
    }

    private function fingerprint(string $scope, string $identifier): string
    {
        return hash('sha256', $scope . ':' . mb_strtolower(trim($identifier)));
    }
}
