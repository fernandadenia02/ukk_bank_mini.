<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\WithdrawalAppointment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WithdrawalAppointmentApiTest extends TestCase
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
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('withdrawal_appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->date('withdrawal_date');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_customer_can_request_an_appointment_for_more_than_one_million_at_least_one_day_ahead(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/customer/withdrawal-appointments', [
                'withdrawal_date' => today()->addDay()->toDateString(),
                'amount' => 1500000,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.withdrawal_date', today()->addDay()->toDateString())
            ->assertJsonPath('data.amount', 1500000)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('withdrawal_appointments', [
            'customer_id' => $customer->id,
            'amount' => 1500000,
            'status' => 'pending',
        ]);

        $this->getJson('/api/customer/withdrawal-appointments')
            ->assertOk()
            ->assertJsonPath('data.0.withdrawal_date', today()->addDay()->toDateString())
            ->assertJsonPath('data.0.amount', 1500000);
    }

    public function test_customer_cannot_request_an_appointment_for_today_or_for_one_million_or_less(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/customer/withdrawal-appointments', [
                'withdrawal_date' => today()->toDateString(),
                'amount' => 1500000,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('withdrawal_date');

        $this->postJson('/api/customer/withdrawal-appointments', [
            'withdrawal_date' => today()->addDay()->toDateString(),
            'amount' => 1000000,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->assertDatabaseCount('withdrawal_appointments', 0);
    }

    public function test_teller_can_approve_or_reject_a_pending_appointment(): void
    {
        $teller = $this->createTeller();
        $customer = $this->createCustomer();
        $approved = $this->createAppointment($customer);
        $rejected = $this->createAppointment($customer);

        $this->actingAs($teller)
            ->post(route('teller.withdrawal-appointments.approve', $approved->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->post(route('teller.withdrawal-appointments.reject', $rejected->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('withdrawal_appointments', [
            'id' => $approved->id,
            'status' => 'approved',
            'reviewed_by' => $teller->id,
        ]);
        $this->assertDatabaseHas('withdrawal_appointments', [
            'id' => $rejected->id,
            'status' => 'rejected',
            'reviewed_by' => $teller->id,
        ]);
    }

    private function createCustomer(): Customer
    {
        return Customer::create([
            'account_number' => '10000001',
            'nis' => 'NIS-1',
            'name' => 'Test Customer',
            'username' => 'customer',
            'password' => Hash::make('password'),
            'status' => 'aktif',
            'balance' => 5000000,
        ]);
    }

    private function createTeller(): User
    {
        return User::create([
            'officer_name' => 'Teller',
            'username' => 'teller',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);
    }

    private function createAppointment(Customer $customer): WithdrawalAppointment
    {
        return WithdrawalAppointment::create([
            'customer_id' => $customer->id,
            'withdrawal_date' => today()->addDay()->toDateString(),
            'amount' => 1500000,
            'status' => 'pending',
        ]);
    }
}
