<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ForgotPasswordOtpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('officer_name')->nullable();
            $table->string('email')->nullable();
            $table->string('role');
            $table->string('status');
            $table->string('password');
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('status');
            $table->string('account_number')->nullable();
            $table->string('name');
            $table->string('email');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('customer_id');
            $table->string('otp_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_password_reset_otps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('otp_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_reset_form_keeps_the_email_that_received_the_otp(): void
    {
        Mail::fake();
        $this->createCustomerAccount('10000001');

        $this->post(route('password.otp.send'), [
            'identity' => '10000001',
            'email' => 'customer@example.test',
        ])
            ->assertRedirect(route('password.reset.form'))
            ->assertSessionHas('status', 'Jika akun terdaftar, OTP berhasil dikirimkan. Silakan cek email dan folder spam.')
            ->assertSessionHas('_old_input.identity', '10000001')
            ->assertSessionHas('_old_input.email', 'customer@example.test');

        $this->get(route('password.reset.form'))
            ->assertOk()
            ->assertSee('OTP berhasil dikirimkan. Silakan cek email dan folder spam.')
            ->assertSee('value="10000001"', false)
            ->assertSee('value="customer@example.test"', false);
    }

    public function test_api_sends_a_reset_otp_and_returns_a_generic_message(): void
    {
        Mail::fake();
        $customerId = $this->createCustomerAccount('10000002');

        $this->postJson('/api/customer/forgot-password', [
            'email' => 'customer@example.test',
        ])
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Jika akun terdaftar, OTP berhasil dikirimkan. Silakan cek email dan folder spam.',
            ]);

        $otpHash = DB::table('password_reset_otps')
            ->where('customer_id', $customerId)
            ->value('otp_hash');
        $this->assertNotEmpty($otpHash);
        $this->assertNotSame('123456', $otpHash);
    }

    public function test_a_correct_otp_can_reset_the_password_before_expiry(): void
    {
        $customerId = $this->createCustomerAccount('10000002');
        DB::table('password_reset_otps')->insert([
            'customer_id' => $customerId,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('password.update'), [
            'identity' => '10000002',
            'email' => 'customer@example.test',
            'otp' => '123456',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check(
            'new-password',
            DB::table('customers')->where('id', $customerId)->value('password')
        ));
    }

    public function test_reset_form_uses_six_digit_otp_and_four_character_password_minimum(): void
    {
        $this->get(route('password.reset.form'))
            ->assertOk()
            ->assertSee('kode OTP 6 digit', false)
            ->assertSee('minlength="6"', false)
            ->assertSee('minlength="4"', false)
            ->assertSee('Minimal 4 karakter.');
    }

    public function test_four_character_password_can_be_set_with_a_six_digit_otp(): void
    {
        $customerId = $this->createCustomerAccount('10000004');
        DB::table('password_reset_otps')->insert([
            'customer_id' => $customerId,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('password.update'), [
            'identity' => '10000004',
            'email' => 'customer@example.test',
            'otp' => '123456',
            'password' => 'abcd',
            'password_confirmation' => 'abcd',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check(
            'abcd',
            DB::table('customers')->where('id', $customerId)->value('password')
        ));
    }

    public function test_reset_password_rejects_password_shorter_than_four_characters(): void
    {
        $this->post(route('password.update'), [
            'identity' => '10000004',
            'email' => 'customer@example.test',
            'otp' => '123456',
            'password' => 'abc',
            'password_confirmation' => 'abc',
        ])
            ->assertSessionHasErrors('password');
    }

    public function test_admin_and_teller_accounts_can_reset_their_passwords_with_email_otp(): void
    {
        foreach (['admin', 'teller'] as $role) {
            $userId = DB::table('users')->insertGetId([
                'officer_name' => ucfirst($role),
                'email' => "{$role}@example.test",
                'role' => $role,
                'status' => 'aktif',
                'password' => Hash::make('old-password'),
            ]);
            DB::table('staff_password_reset_otps')->insert([
                'user_id' => $userId,
                'otp_hash' => Hash::make('123456'),
                'expires_at' => now()->addMinutes(5),
                'attempts' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->post(route('password.update'), [
                'identity' => (string) $userId,
                'email' => "{$role}@example.test",
                'otp' => '123456',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertRedirect(route('login'));

            $this->assertTrue(Hash::check(
                'new-password',
                DB::table('users')->where('id', $userId)->value('password')
            ));
        }
    }

    public function test_staff_email_receives_a_password_reset_otp(): void
    {
        Mail::fake();
        $userId = DB::table('users')->insertGetId([
            'officer_name' => 'Teller',
            'email' => 'teller@example.test',
            'role' => 'teller',
            'status' => 'aktif',
            'password' => Hash::make('old-password'),
        ]);

        $this->post(route('password.otp.send'), [
            'identity' => (string) $userId,
            'email' => 'teller@example.test',
        ])
            ->assertRedirect(route('password.reset.form'))
            ->assertSessionHas('_old_input.identity', (string) $userId)
            ->assertSessionHas('_old_input.email', 'teller@example.test');

        $this->assertDatabaseHas('staff_password_reset_otps', [
            'user_id' => $userId,
            'attempts' => 0,
        ]);
    }

    public function test_reset_otp_requires_matching_identity_and_registered_email(): void
    {
        Mail::fake();
        $userId = DB::table('users')->insertGetId([
            'officer_name' => 'Teller',
            'email' => 'teller@example.test',
            'role' => 'teller',
            'status' => 'aktif',
            'password' => Hash::make('old-password'),
        ]);

        $this->post(route('password.otp.send'), [
            'identity' => (string) ($userId + 1),
            'email' => 'teller@example.test',
        ])
            ->assertRedirect(route('password.reset.form'))
            ->assertSessionHas('_old_input.identity', (string) ($userId + 1));

        $this->assertDatabaseCount('staff_password_reset_otps', 0);
    }

    public function test_api_can_reset_a_password_with_a_valid_otp(): void
    {
        $customerId = $this->createCustomerAccount('10000006');
        DB::table('password_reset_otps')->insert([
            'customer_id' => $customerId,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/customer/reset-password', [
            'email' => 'customer@example.test',
            'otp' => '123456',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Password berhasil diubah. Silakan login.',
            ]);

        $this->assertTrue(Hash::check(
            'new-password',
            DB::table('customers')->where('id', $customerId)->value('password')
        ));
        $this->assertNotNull(
            DB::table('password_reset_otps')
                ->where('customer_id', $customerId)
                ->value('used_at')
        );
    }

    public function test_api_rejects_an_incorrect_otp_and_increments_attempts(): void
    {
        $customerId = $this->createCustomerAccount('10000007');
        DB::table('password_reset_otps')->insert([
            'customer_id' => $customerId,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/customer/reset-password', [
            'email' => 'customer@example.test',
            'otp' => '654321',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertSame(
            1,
            DB::table('password_reset_otps')
                ->where('customer_id', $customerId)
                ->value('attempts')
        );
        $this->assertTrue(Hash::check(
            'old-password',
            DB::table('customers')->where('id', $customerId)->value('password')
        ));
    }

    private function createCustomerAccount(string $accountNumber): int
    {
        return DB::table('customers')->insertGetId([
            'username' => 'customer-' . $accountNumber,
            'password' => Hash::make('old-password'),
            'status' => 'aktif',
            'account_number' => $accountNumber,
            'name' => 'Test Customer',
            'email' => 'customer@example.test',
            'created_at' => now(),
        ]);
    }
}
