<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = User::where('username', 'admin')->exists()
            ? null
            : $this->validatedPassword('admin', config('seeders.admin_password'));
        $tellerPassword = User::where('username', 'teller1')->exists()
            ? null
            : $this->validatedPassword('teller', config('seeders.teller_password'));

        DB::transaction(function () use ($adminPassword, $tellerPassword): void {
            User::firstOrCreate(
                ['username' => 'admin'],
                [
                    'officer_name' => 'Administrator',
                    'email' => 'fernandadenia02@gmail.com',
                    'password' => $adminPassword,
                    'role' => 'admin',
                    'status' => 'aktif',
                ]
            );

            User::firstOrCreate(
                ['username' => 'teller1'],
                [
                    'officer_name' => 'Teller 1',
                    'email' => null,
                    'password' => $tellerPassword,
                    'role' => 'teller',
                    'status' => 'aktif',
                ]
            );

            foreach ($this->sampleMembers() as $member) {
                Member::firstOrCreate(
                    ['nis' => $member['nis']],
                    $member
                );
            }
        });
    }

    private function validatedPassword(string $role, mixed $password): string
    {
        $validator = Validator::make(
            ['password' => $password],
            [
                'password' => [
                    'required',
                    'string',
                    'max:255',
                    new StrongPassword,
                ],
            ]
        );

        if ($validator->fails()) {
            throw new \RuntimeException(
                'Set password awal yang valid pada SEED_'.strtoupper($role)
                .'_PASSWORD di file .env. '.$validator->errors()->first('password')
            );
        }

        return $password;
    }

    /**
     * @return array<int, array{nis: string, name: string, class: string, location: string}>
     */
    private function sampleMembers(): array
    {
        return [
            [
                'nis' => 'SEED-ANGGOTA-001',
                'name' => 'Anggota Contoh 1',
                'class' => 'X',
                'location' => 'Jakarta',
            ],
            [
                'nis' => 'SEED-ANGGOTA-002',
                'name' => 'Anggota Contoh 2',
                'class' => 'XI',
                'location' => 'Bandung',
            ],
            [
                'nis' => 'SEED-ANGGOTA-003',
                'name' => 'Anggota Contoh 3',
                'class' => 'XII',
                'location' => 'Surabaya',
            ],
        ];
    }
}
