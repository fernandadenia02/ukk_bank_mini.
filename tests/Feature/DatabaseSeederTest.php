<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('officer_name');
            $table->string('username')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->string('role');
            $table->string('status');
        });

        Schema::create('members', function (Blueprint $table): void {
            $table->id();
            $table->string('nis')->unique();
            $table->string('name');
            $table->string('class');
            $table->text('location');
            $table->timestamps();
        });
    }

    public function test_it_seeds_admin_teller_and_sample_members_idempotently(): void
    {
        config([
            'seeders.admin_password' => 'SecureAdminSeed123!',
            'seeders.teller_password' => 'SecureTellerSeed123!',
        ]);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', [
            'officer_name' => 'Administrator',
            'username' => 'admin',
            'email' => 'fernandadenia02@gmail.com',
            'role' => 'admin',
            'status' => 'aktif',
        ]);
        $this->assertDatabaseHas('users', [
            'officer_name' => 'Teller 1',
            'username' => 'teller1',
            'role' => 'teller',
            'status' => 'aktif',
        ]);
        $this->assertDatabaseCount('members', 3);

        $this->assertTrue(Hash::check(
            'SecureAdminSeed123!',
            User::where('username', 'admin')->value('password')
        ));
        $this->assertTrue(Hash::check(
            'SecureTellerSeed123!',
            User::where('username', 'teller1')->value('password')
        ));
    }

    public function test_it_does_not_replace_passwords_for_existing_seed_accounts(): void
    {
        DB::table('users')->insert([
            'officer_name' => 'Existing Administrator',
            'username' => 'admin',
            'email' => 'existing-admin@example.test',
            'password' => Hash::make('ExistingPassword123!'),
            'role' => 'admin',
            'status' => 'aktif',
        ]);
        config([
            'seeders.admin_password' => null,
            'seeders.teller_password' => 'SecureTellerSeed123!',
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(
            'Existing Administrator',
            User::where('username', 'admin')->value('officer_name')
        );
        $this->assertTrue(Hash::check(
            'ExistingPassword123!',
            User::where('username', 'admin')->value('password')
        ));
        $this->assertDatabaseCount('users', 2);
    }

    public function test_it_requires_strong_passwords_for_new_seed_accounts(): void
    {
        config([
            'seeders.admin_password' => 'weak',
            'seeders.teller_password' => 'SecureTellerSeed123!',
        ]);

        try {
            $this->seed(DatabaseSeeder::class);
            $this->fail('The seeder should reject a weak initial admin password.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('SEED_ADMIN_PASSWORD', $exception->getMessage());
            $this->assertStringContainsString('12 karakter', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('members', 0);
    }
}
