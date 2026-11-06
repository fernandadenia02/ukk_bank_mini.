<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerPinResetOtp;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerPinRecoveryApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('status')->default('aktif');
            $table->string('account_number');
            $table->string('nis');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('pin');
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('customer_pin_reset_otps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('otp_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_authenticated_customer_can_request_pin_reset_otp_by_email(): void
    {
        config(['mail.default' => 'array']);
        $customer = $this->createCustomer();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/customer/forgot-pin')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('customer_pin_reset_otps', [
            'customer_id' => $customer->id,
            'attempts' => 0,
            'used_at' => null,
        ]);
        $this->assertNotSame(
            '123456',
            CustomerPinResetOtp::where('customer_id', $customer->id)->value('otp_hash')
        );
    }

    public function test_customer_can_reset_pin_with_a_valid_email_otp(): void
    {
        $customer = $this->createCustomer();
        $otp = $this->createOtp($customer, '123456');

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/customer/reset-pin', [
                'otp' => '123456',
                'pin' => '654321',
                'pin_confirmation' => '654321',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $customer->refresh();
        $this->assertTrue(Hash::check('654321', $customer->pin));
        $this->assertFalse(Hash::check('123456', $customer->pin));
        $this->assertNotNull($otp->fresh()->used_at);
    }

    public function test_wrong_otp_is_limited_and_never_changes_the_pin(): void
    {
        $customer = $this->createCustomer();
        $this->createOtp($customer, '123456');

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->actingAs($customer, 'sanctum')
                ->postJson('/api/customer/reset-pin', [
                    'otp' => '000000',
                    'pin' => '654321',
                    'pin_confirmation' => '654321',
                ])
                ->assertUnprocessable()
                ->assertJsonPath('success', false);
        }

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/customer/reset-pin', [
                'otp' => '000000',
                'pin' => '654321',
                'pin_confirmation' => '654321',
            ])
            ->assertTooManyRequests()
            ->assertJsonStructure(['locked_for_seconds']);

        $customer->refresh();
        $this->assertTrue(Hash::check('123456', $customer->pin));
    }

    public function test_expired_otp_cannot_change_the_pin(): void
    {
        $customer = $this->createCustomer();
        $this->createOtp($customer, '123456', now()->subMinute());

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/customer/reset-pin', [
                'otp' => '123456',
                'pin' => '654321',
                'pin_confirmation' => '654321',
            ])
            ->assertStatus(410)
            ->assertJsonPath('success', false);

        $customer->refresh();
        $this->assertTrue(Hash::check('123456', $customer->pin));
    }

    private function createCustomer(): Customer
    {
        return Customer::create([
            'username' => 'customer',
            'password' => Hash::make('password'),
            'status' => 'aktif',
            'account_number' => '10000001',
            'nis' => 'NIS-1',
            'name' => 'Test Customer',
            'email' => 'customer@example.test',
            'pin' => Hash::make('123456'),
            'balance' => 10000,
        ]);
    }

    private function createOtp(
        Customer $customer,
        string $code,
        ?\Illuminate\Support\Carbon $expiresAt = null,
    ): CustomerPinResetOtp {
        return CustomerPinResetOtp::create([
            'customer_id' => $customer->id,
            'otp_hash' => Hash::make($code),
            'expires_at' => $expiresAt ?? now()->addMinutes(5),
            'attempts' => 0,
        ]);
    }
}
