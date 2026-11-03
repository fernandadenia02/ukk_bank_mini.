<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TellerTransactionIsolationTest extends TestCase
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
            $table->unsignedBigInteger('user_id');
            $table->string('account_number');
            $table->string('nis');
            $table->string('name');
            $table->decimal('balance', 12, 2)->default(0);
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
    }

    public function test_teller_transaction_table_only_shows_their_transactions(): void
    {
        [$teller, $otherTeller, $customer] = $this->createTellersAndCustomer();
        $ownTransaction = $this->createTransaction($teller, $customer, 1000);
        $otherTransaction = $this->createTransaction($otherTeller, $customer, 2000);

        $this->actingAs($teller)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('Rp 1.000')
            ->assertDontSee('Rp 2.000')
            ->assertSee('Teller One')
            ->assertDontSee('Teller Two');
    }

    public function test_teller_cannot_view_another_tellers_transaction_receipt(): void
    {
        [$teller, $otherTeller, $customer] = $this->createTellersAndCustomer();
        $otherTransaction = $this->createTransaction($otherTeller, $customer, 2000);

        $this->actingAs($teller)
            ->get(route('transactions.receipt', $otherTransaction->id))
            ->assertNotFound();
    }

    private function createTellersAndCustomer(): array
    {
        $teller = User::create([
            'officer_name' => 'Teller One',
            'username' => 'teller-one',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);
        $otherTeller = User::create([
            'officer_name' => 'Teller Two',
            'username' => 'teller-two',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);

        $customer = Customer::create([
            'user_id' => $teller->id,
            'account_number' => '10000001',
            'nis' => 'NIS-1',
            'name' => 'Test Customer',
            'balance' => 10000,
        ]);

        return [$teller, $otherTeller, $customer];
    }

    private function createTransaction(
        User $teller,
        Customer $customer,
        int $amount,
    ): Transaction {
        return Transaction::create([
            'customer_id' => $customer->id,
            'teller_id' => $teller->id,
            'transaction_type' => 'setoran',
            'amount' => $amount,
            'beginning_balance' => 10000,
            'ending_balance' => 10000 + $amount,
            'date' => now(),
        ]);
    }
}
