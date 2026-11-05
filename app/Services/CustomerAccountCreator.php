<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CustomerAccountCreator
{
    public function create(array $data, string $accountNumber): array
    {
        $username = $this->availableUsername($data['name']);
        $customer = Customer::create([
            'account_number' => $accountNumber,
            'nis' => trim($data['nis']),
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'username' => $username,
            'password' => Hash::make($data['password']),
            'status' => 'nonaktif',
            'class' => trim($data['class']),
            'location' => trim($data['location']),
            'telephone_number' => trim($data['telephone_number']),
            'pin' => Hash::make($data['pin']),
            'balance' => 0,
        ]);

        $directory = public_path('qrcode');
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException('Folder QR code tidak dapat dibuat.');
        }

        $fileName = $customer->account_number . '.svg';
        $qrPath = $directory . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($qrPath)) {
            throw new \RuntimeException('File QR code customer sudah ada.');
        }

        $this->ensureQrCode($customer);

        return [$customer, $qrPath];
    }

    public function ensureQrCode(Customer $customer): string
    {
        $directory = public_path('qrcode');
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException('Folder QR code tidak dapat dibuat.');
        }

        $fileName = $customer->account_number . '.svg';
        $qrPath = $directory . DIRECTORY_SEPARATOR . $fileName;

        if (!is_file($qrPath)) {
            try {
                $writtenBytes = file_put_contents(
                    $qrPath,
                    QrCode::size(300)->generate($customer->account_number)
                );

                if ($writtenBytes === false) {
                    throw new \RuntimeException('QR code customer tidak dapat dibuat.');
                }
            } catch (\Throwable $e) {
                if (is_file($qrPath)) {
                    unlink($qrPath);
                }

                throw $e;
            }
        }

        if ($customer->qrcode !== $fileName) {
            $customer->update(['qrcode' => $fileName]);
        }

        return $fileName;
    }

    private function availableUsername(string $name): string
    {
        $base = mb_substr(strtolower(trim($name)), 0, 44);
        $username = $base;
        $suffix = 1;

        while (
            Customer::where('username', $username)->exists() ||
            User::where('username', $username)->exists()
        ) {
            $username = $base . $suffix;
            $suffix++;
        }

        return $username;
    }
}
