<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountingJournalDateFilterTest extends TestCase
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
            $table->string('name')->nullable();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
        });

        Schema::create('accounting_journal', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('account');
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->dateTime('date');
        });
    }

    public function test_admin_can_filter_journal_entries_and_totals_by_date_and_account(): void
    {
        $admin = User::create([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        DB::table('accounting_journal')->insert([
            [
                'account' => 'Tabungan Nasabah',
                'debit' => 200,
                'credit' => 0,
                'date' => '2026-10-10 09:00:00',
            ],
            [
                'account' => 'Kas',
                'debit' => 0,
                'credit' => 200,
                'date' => '2026-10-10 09:00:00',
            ],
            [
                'account' => 'Tabungan Nasabah',
                'debit' => 500,
                'credit' => 0,
                'date' => '2026-10-11 09:00:00',
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('accounting-journal.index', [
                'keyword' => 'Tabungan',
                'date_from' => '2026-10-10',
                'date_to' => '2026-10-10',
            ]))
            ->assertOk()
            ->assertSeeText('Tabungan Nasabah')
            ->assertDontSeeText('Kas')
            ->assertDontSeeText('500')
            ->assertSeeText('Total Debit :')
            ->assertSeeText('Rp 200')
            ->assertSeeText('Total Credit :');
    }

    public function test_end_date_can_be_used_without_start_date(): void
    {
        $admin = User::create([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        DB::table('accounting_journal')->insert([
            [
                'account' => 'Kas',
                'debit' => 100,
                'credit' => 0,
                'date' => '2026-10-10 09:00:00',
            ],
            [
                'account' => 'Kas',
                'debit' => 300,
                'credit' => 0,
                'date' => '2026-10-11 09:00:00',
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('accounting-journal.index', [
                'date_to' => '2026-10-10',
            ]))
            ->assertOk()
            ->assertDontSeeText('300')
            ->assertSeeText('Rp 100');
    }
}
