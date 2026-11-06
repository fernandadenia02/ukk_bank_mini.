<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\WithdrawalPinApproval;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WithdrawalPinApprovalApiTest extends TestCase
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
            $table->string('status')->default('aktif');
            $table->string('account_number');
            $table->string('nis');
            $table->string('name');
            $table->string('pin');
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('withdrawal_pin_approvals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('teller_id');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->timestamp('expires_at')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_statuses', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('is_open')->default(true);
        });
        DB::table('bank_statuses')->insert(['id' => 1, 'is_open' => true]);
    }

    public function test_customer_can_fetch_pending_pin_requests_without_exposing_a_pin(): void
    {
        [$customer, $teller] = $this->createCustomerAndTeller();
        $approval = $this->createApproval($customer, $teller);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/customer/withdrawal-pin-requests/pending')
            ->assertOk()
            ->assertJsonPath('data.0.id', $approval->id)
            ->assertJsonPath('data.0.amount', 5000)
            ->assertJsonPath('data.0.teller_name', 'Teller')
            ->assertJsonMissingPath('data.0.pin');
    }

    public function test_customer_pin_approves_the_request_and_teller_never_receives_pin_value(): void
    {
        [$customer, $teller] = $this->createCustomerAndTeller();
        $approval = $this->createApproval($customer, $teller);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/withdrawal-pin-requests/{$approval->id}/verify", [
                'pin' => '012345',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonMissingPath('data.pin');

        $this->assertDatabaseHas('withdrawal_pin_approvals', [
            'id' => $approval->id,
            'status' => 'approved',
        ]);
    }

    public function test_wrong_pin_is_rejected_and_only_the_own_request_can_be_approved(): void
    {
        [$customer, $teller] = $this->createCustomerAndTeller();
        $otherCustomer = Customer::create([
            'account_number' => '10000002',
            'nis' => 'NIS-2',
            'name' => 'Other Customer',
            'username' => 'othercustomer',
            'password' => Hash::make('password'),
            'status' => 'aktif',
            'pin' => Hash::make('999999'),
            'balance' => 10000,
        ]);
        $approval = $this->createApproval($customer, $teller);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/withdrawal-pin-requests/{$approval->id}/verify", [
                'pin' => '111111',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('withdrawal_pin_approvals', [
            'id' => $approval->id,
            'status' => 'pending',
        ]);

        $otherApproval = $this->createApproval($otherCustomer, $teller);
        $this->postJson("/api/customer/withdrawal-pin-requests/{$otherApproval->id}/verify", [
            'pin' => '012345',
        ])->assertNotFound();
    }

    public function test_ten_wrong_pin_attempts_lock_customer_out_for_thirty_minutes(): void
    {
        [$customer, $teller] = $this->createCustomerAndTeller();
        $approval = $this->createApproval($customer, $teller);

        for ($attempt = 0; $attempt < 9; $attempt++) {
            $this->actingAs($customer, 'sanctum')
                ->postJson("/api/customer/withdrawal-pin-requests/{$approval->id}/verify", [
                    'pin' => '111111',
                ])
                ->assertUnprocessable();
        }

        $this->postJson("/api/customer/withdrawal-pin-requests/{$approval->id}/verify", [
            'pin' => '111111',
        ])
            ->assertTooManyRequests()
            ->assertJsonPath('locked_for_seconds', 1800);

        $this->postJson("/api/customer/withdrawal-pin-requests/{$approval->id}/verify", [
            'pin' => '012345',
        ])->assertTooManyRequests();

        $this->assertDatabaseHas('withdrawal_pin_approvals', [
            'id' => $approval->id,
            'status' => 'pending',
        ]);
    }

    public function test_customer_can_decline_their_own_withdrawal_request(): void
    {
        [$customer, $teller] = $this->createCustomerAndTeller();
        $approval = $this->createApproval($customer, $teller);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/withdrawal-pin-requests/{$approval->id}/decline")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('withdrawal_pin_approvals', [
            'id' => $approval->id,
            'status' => 'rejected',
        ]);
    }

    public function test_bank_status_api_returns_open_and_closed_labels_for_mobile(): void
    {
        [$customer] = $this->createCustomerAndTeller();
        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/customer/bank-status')
            ->assertOk()
            ->assertJsonPath('data.is_open', true)
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.label', 'Bank Buka')
            ->assertJsonStructure(['data' => ['checked_at']]);

        DB::table('bank_statuses')->where('id', 1)->update(['is_open' => false]);

        $this->getJson('/api/customer/bank-status')
            ->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.label', 'Bank Tutup');
    }

    private function createCustomerAndTeller(): array
    {
        $customer = Customer::create([
            'account_number' => '10000001',
            'nis' => 'NIS-1',
            'name' => 'Test Customer',
            'username' => 'testcustomer',
            'password' => Hash::make('password'),
            'status' => 'aktif',
            'pin' => Hash::make('012345'),
            'balance' => 10000,
        ]);

        $teller = User::create([
            'officer_name' => 'Teller',
            'username' => 'teller',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);

        return [$customer, $teller];
    }

    private function createApproval(Customer $customer, User $teller): WithdrawalPinApproval
    {
        return WithdrawalPinApproval::create([
            'customer_id' => $customer->id,
            'teller_id' => $teller->id,
            'amount' => 5000,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(5),
        ]);
    }
}
