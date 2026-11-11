<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MonthlyReportStatusTest extends TestCase
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
            $table->string('account_number')->nullable();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('teller_id')->nullable();
            $table->string('transaction_type');
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('beginning_balance', 12, 2)->default(0);
            $table->decimal('ending_balance', 12, 2)->default(0);
            $table->dateTime('date');
        });

        Schema::create('daily_reports', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->string('status');
        });
    }

    public function test_monthly_report_displays_daily_report_status_counts_for_selected_period(): void
    {
        $admin = User::create([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        foreach ([
            ['2026-10-01', 'approved'],
            ['2026-10-15', 'approved'],
            ['2026-10-16', 'pending'],
            ['2026-10-16', 'rejected'],
            ['2026-09-30', 'rejected'],
        ] as [$date, $status]) {
            DB::table('daily_reports')->insert(compact('date', 'status'));
        }

        $this->actingAs($admin)
            ->get(route('monthly-reports.index', [
                'month' => 10,
                'year' => 2026,
            ]))
            ->assertOk()
            ->assertSeeText('Approved: 2')
            ->assertSeeText('Pending: 1')
            ->assertSeeText('Rejected: 1');
    }

    public function test_monthly_report_lists_newest_transactions_first(): void
    {
        $admin = User::create([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);
        $oldCustomerId = DB::table('customers')->insertGetId(['name' => 'Oldest Transaction']);
        $newCustomerId = DB::table('customers')->insertGetId(['name' => 'Newest Transaction']);

        DB::table('transactions')->insert([
            [
                'customer_id' => $oldCustomerId,
                'transaction_type' => 'setoran',
                'amount' => 1000,
                'date' => '2026-10-01 09:00:00',
            ],
            [
                'customer_id' => $newCustomerId,
                'transaction_type' => 'setoran',
                'amount' => 2000,
                'date' => '2026-10-31 16:00:00',
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('monthly-reports.index', [
                'month' => 10,
                'year' => 2026,
            ]))
            ->assertOk()
            ->assertSeeInOrder(['Newest Transaction', 'Oldest Transaction']);
    }
}
