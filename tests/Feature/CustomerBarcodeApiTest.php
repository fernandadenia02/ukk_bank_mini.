<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerBarcodeApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('officer_name');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('role');
            $table->string('status');
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('status')->default('nonaktif');
            $table->string('account_number');
            $table->string('nis');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('class')->nullable();
            $table->string('location')->nullable();
            $table->string('telephone_number')->nullable();
            $table->string('pin');
            $table->decimal('balance', 12, 2);
            $table->string('qrcode')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('teller_id')->nullable();
            $table->string('transaction_type');
            $table->decimal('amount', 12, 2);
            $table->decimal('beginning_balance', 12, 2);
            $table->decimal('ending_balance', 12, 2);
            $table->dateTime('date');
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
    }

    public function test_customer_can_fetch_qrcode_url(): void
    {
        File::ensureDirectoryExists(public_path('qrcode'));

        $customer = Customer::create([
            'account_number' => $accountNumber = '123456',
            'nis' => '1234567890',
            'name' => 'Test Customer',
            'username' => 'testcustomer',
            'password' => bcrypt('123456'),
            'status' => 'aktif',
            'class' => 'XII',
            'location' => 'Jakarta',
            'telephone_number' => '081234567890',
            'pin' => bcrypt('123456'),
            'balance' => 10000,
        ]);

        $fileName = $accountNumber . '.svg';
        file_put_contents(public_path('qrcode/' . $fileName), '<svg></svg>');
        $customer->update(['qrcode' => $fileName]);

        $this->actingAs($customer, 'sanctum');

        $response = $this->getJson('/api/customer/barcode');

        $response->assertOk()
            ->assertJsonPath('data.account_number', $accountNumber)
            ->assertJsonPath('data.qrcode_url', url('qrcode/' . $fileName))
            ->assertJsonPath(
                'data.qrcode_data_uri',
                'data:image/svg+xml;base64,' . base64_encode('<svg></svg>')
            );

        File::delete(public_path('qrcode/' . $fileName));
    }

    public function test_inactive_customer_cannot_log_in_through_the_api(): void
    {
        Customer::create([
            'account_number' => '10000006',
            'nis' => '1234567892',
            'name' => 'Inactive Customer',
            'username' => 'inactivecustomer',
            'password' => bcrypt('123456'),
            'status' => 'nonaktif',
            'pin' => bcrypt('123456'),
            'balance' => 0,
        ]);

        $this->postJson('/api/customer/login', [
            'username' => 'inactivecustomer',
            'password' => '123456',
        ])->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_active_customer_can_log_in_through_the_api_from_the_customer_table(): void
    {
        Customer::create([
            'account_number' => '10000009',
            'nis' => '1234567893',
            'name' => 'Active Customer',
            'username' => 'activecustomer',
            'password' => bcrypt('123456'),
            'status' => 'aktif',
            'pin' => bcrypt('123456'),
            'balance' => 10000,
        ]);

        $this->postJson('/api/customer/login', [
            'username' => 'activecustomer',
            'password' => '123456',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.role', 'customer')
            ->assertJsonPath('customer.account_number', '10000009');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_customer_profile_returns_fields_used_by_the_app(): void
    {
        $customer = Customer::create([
            'account_number' => '10000007',
            'nis' => '1234567890',
            'name' => 'Test Customer',
            'username' => 'profilecustomer',
            'password' => bcrypt('123456'),
            'status' => 'aktif',
            'email' => 'test@example.com',
            'class' => 'XII',
            'location' => 'Jakarta',
            'telephone_number' => '081234567890',
            'pin' => bcrypt('123456'),
            'balance' => 10000,
        ]);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/customer/profile')
            ->assertOk()
            ->assertJsonPath('data.class', 'XII')
            ->assertJsonPath('data.location', 'Jakarta')
            ->assertJsonPath('data.telephone_number', '081234567890')
            ->assertJsonPath('data.email', 'test@example.com');
    }

    public function test_customer_can_fetch_their_recent_transactions(): void
    {
        $customer = Customer::create([
            'account_number' => '10000008',
            'nis' => '1234567891',
            'name' => 'Test Customer',
            'username' => 'transactioncustomer',
            'password' => bcrypt('123456'),
            'status' => 'aktif',
            'class' => 'XII',
            'location' => 'Jakarta',
            'telephone_number' => '081234567891',
            'pin' => bcrypt('123456'),
            'balance' => 15000,
        ]);

        \App\Models\Transaction::create([
            'customer_id' => $customer->id,
            'transaction_type' => 'setoran',
            'amount' => 5000,
            'beginning_balance' => 10000,
            'ending_balance' => 15000,
            'date' => '2026-10-15 09:00:00',
        ]);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/customer/transactions')
            ->assertOk()
            ->assertJsonPath('data.0.transaction_type', 'setoran')
            ->assertJsonPath('data.0.amount', 5000);
    }
}
