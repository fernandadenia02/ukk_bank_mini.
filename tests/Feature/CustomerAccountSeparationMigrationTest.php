<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerAccountSeparationMigrationTest extends TestCase
{
    public function test_migration_moves_customer_credentials_and_tokens_out_of_users(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('officer_name');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('role');
            $table->string('status');
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('account_number');
            $table->string('name');
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('payload');
            $table->integer('last_activity');
        });

        $staffId = DB::table('users')->insertGetId([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'password' => Hash::make('admin-password'),
            'role' => 'admin',
            'status' => 'aktif',
        ]);
        $customerUserId = DB::table('users')->insertGetId([
            'officer_name' => 'Customer',
            'username' => 'legacycustomer',
            'password' => Hash::make('customer-password'),
            'role' => 'customer',
            'status' => 'aktif',
        ]);
        $customerId = DB::table('customers')->insertGetId([
            'user_id' => $customerUserId,
            'account_number' => '10000001',
            'name' => 'Customer',
        ]);
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => User::class,
            'tokenable_id' => $customerUserId,
            'name' => 'mobile',
            'token' => str_repeat('a', 64),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sessions')->insert([
            'id' => 'legacy-customer-session',
            'user_id' => $customerUserId,
            'payload' => '',
            'last_activity' => time(),
        ]);

        $migration = require database_path(
            'migrations/2026_10_20_000000_move_customer_accounts_to_customers_table.php'
        );
        $migration->up();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['id' => $staffId, 'role' => 'admin']);
        $this->assertDatabaseHas('customers', [
            'id' => $customerId,
            'username' => 'legacycustomer',
            'status' => 'aktif',
        ]);
        $this->assertTrue(Hash::check(
            'customer-password',
            DB::table('customers')->where('id', $customerId)->value('password')
        ));
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => Customer::class,
            'tokenable_id' => $customerId,
        ]);
        $this->assertDatabaseCount('sessions', 0);
        $this->assertFalse(Schema::hasColumn('customers', 'user_id'));
    }
}
