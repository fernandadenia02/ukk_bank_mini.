<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TellerDailyReportRejectionTest extends TestCase
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

        Schema::create('bank_statuses', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('is_open')->default(true);
        });
        DB::table('bank_statuses')->insert(['id' => 1, 'is_open' => true]);

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('account_number');
            $table->string('nis');
            $table->string('name');
            $table->string('class')->nullable();
            $table->string('status')->default('nonaktif');
            $table->decimal('balance', 12, 2)->default(0);
        });

        Schema::create('daily_reports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('teller_id');
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->date('date');
            $table->decimal('beginning_balance', 12, 2)->default(0);
            $table->decimal('total_deposit', 12, 2)->default(0);
            $table->decimal('total_withdrawals', 12, 2)->default(0);
            $table->decimal('ending_balance', 12, 2)->default(0);
            $table->decimal('physical_money', 12, 2)->default(0);
            $table->decimal('difference', 12, 2)->default(0);
            $table->string('status');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('rejection_reason')->nullable();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('teller_id');
            $table->string('transaction_type');
            $table->decimal('amount', 12, 2)->default(0);
            $table->dateTime('date');
        });

        Schema::create('cash_advance_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('daily_report_id');
            $table->unsignedBigInteger('teller_id');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->dateTime('requested_at');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_teller_dashboard_shows_their_own_rejection_notes_from_previous_days(): void
    {
        $teller = User::create([
            'officer_name' => 'Teller One',
            'username' => 'teller-one',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);
        $reportId = DB::table('daily_reports')->insertGetId([
            'teller_id' => $teller->id,
            'date' => today()->subDay()->toDateString(),
            'status' => 'rejected',
            'rejection_reason' => 'Mohon sesuaikan saldo akhir laporan.',
        ]);

        $this->actingAs($teller)
            ->get(route('teller.dashboard'))
            ->assertOk()
            ->assertSee('Catatan Penolakan Laporan')
            ->assertSee('Mohon sesuaikan saldo akhir laporan.')
            ->assertSee(route('teller.daily-reports.show', $reportId));
    }

    public function test_teller_dashboard_does_not_show_another_tellers_rejection_note(): void
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

        DB::table('daily_reports')->insert([
            'teller_id' => $otherTeller->id,
            'date' => today()->toDateString(),
            'status' => 'rejected',
            'rejection_reason' => 'Catatan khusus untuk teller lain.',
        ]);

        $this->actingAs($teller)
            ->get(route('teller.dashboard'))
            ->assertOk()
            ->assertDontSee('Catatan khusus untuk teller lain.');
    }

    public function test_admin_cannot_approve_or_send_rejection_before_teller_closes_cash(): void
    {
        $admin = User::create([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);
        $teller = User::create([
            'officer_name' => 'Teller',
            'username' => 'teller',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);
        $reportId = DB::table('daily_reports')->insertGetId([
            'teller_id' => $teller->id,
            'date' => today()->toDateString(),
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('daily-reports.index'))
            ->assertOk()
            ->assertSee('Menunggu kas ditutup teller')
            ->assertSee('Aksi review dan catatan admin belum tersedia.');

        $this->post(route('daily-reports.approve', $reportId))
            ->assertSessionHas('error', 'Teller harus menutup kas harian sebelum laporan dapat disetujui.');
        $this->post(route('daily-reports.reject', $reportId), [
            'rejection_reason' => 'Mohon koreksi laporan.',
        ])
            ->assertSessionHas('error', 'Teller harus menutup kas harian sebelum admin dapat mengirim catatan atau menolak laporan.');

        $this->assertDatabaseHas('daily_reports', [
            'id' => $reportId,
            'status' => 'pending',
            'rejection_reason' => null,
        ]);
    }

    public function test_admin_can_approve_or_send_rejection_after_cash_is_closed(): void
    {
        $admin = User::create([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);
        $teller = User::create([
            'officer_name' => 'Teller',
            'username' => 'teller',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);
        $reportId = DB::table('daily_reports')->insertGetId([
            'teller_id' => $teller->id,
            'date' => today()->toDateString(),
            'status' => 'pending',
            'closed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('daily-reports.reject', $reportId), [
                'rejection_reason' => 'Mohon koreksi laporan.',
            ])
            ->assertSessionHas('success', 'Daily Report berhasil ditolak. Alasan penolakan telah disimpan.');

        $this->assertDatabaseHas('daily_reports', [
            'id' => $reportId,
            'status' => 'rejected',
            'rejection_reason' => 'Mohon koreksi laporan.',
        ]);
    }

    public function test_rejected_report_for_today_can_be_corrected_and_resubmitted_for_review(): void
    {
        $admin = User::create([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);
        $teller = User::create([
            'officer_name' => 'Teller',
            'username' => 'teller',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);
        $reportId = DB::table('daily_reports')->insertGetId([
            'teller_id' => $teller->id,
            'date' => today()->toDateString(),
            'status' => 'pending',
            'closed_at' => now(),
            'physical_money' => 10000,
            'ending_balance' => 10000,
        ]);

        $this->actingAs($admin)
            ->post(route('daily-reports.reject', $reportId), [
                'rejection_reason' => 'Mohon koreksi uang fisik laporan.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('daily_reports', [
            'id' => $reportId,
            'status' => 'rejected',
            'closed_at' => null,
            'rejection_reason' => 'Mohon koreksi uang fisik laporan.',
        ]);

        $this->actingAs($teller)
            ->get(route('teller.dashboard'))
            ->assertOk()
            ->assertSee('Mohon koreksi uang fisik laporan.')
            ->assertSee('Perbaiki uang fisik laporan')
            ->assertSee('Ajukan Ulang Tutup Kas');

        $this->post(route('teller.daily-reports.close'), [
            'physical_money' => '9500',
        ])
            ->assertRedirect(route('teller.dashboard'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('daily_reports', [
            'id' => $reportId,
            'status' => 'pending',
            'physical_money' => 9500,
            'difference' => -500,
            'rejection_reason' => null,
            'supervisor_id' => null,
        ]);
        $this->assertNotNull(DB::table('daily_reports')->where('id', $reportId)->value('closed_at'));

        $this->actingAs($admin)
            ->post(route('daily-reports.approve', $reportId))
            ->assertSessionHas('success', 'Daily Report berhasil disetujui.');

        $this->assertDatabaseHas('daily_reports', [
            'id' => $reportId,
            'status' => 'approved',
            'closed_at' => DB::table('daily_reports')->where('id', $reportId)->value('closed_at'),
        ]);
    }

    public function test_rejecting_previous_day_report_does_not_reopen_its_cash(): void
    {
        $admin = User::create([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);
        $teller = User::create([
            'officer_name' => 'Teller',
            'username' => 'teller',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);
        $closedAt = now()->subDay();
        $reportId = DB::table('daily_reports')->insertGetId([
            'teller_id' => $teller->id,
            'date' => today()->subDay()->toDateString(),
            'status' => 'pending',
            'closed_at' => $closedAt,
        ]);

        $this->actingAs($admin)
            ->post(route('daily-reports.reject', $reportId), [
                'rejection_reason' => 'Mohon koreksi laporan sebelumnya.',
            ])
            ->assertSessionHas('success');

        $this->assertSame(
            $closedAt->toDateTimeString(),
            DB::table('daily_reports')->where('id', $reportId)->value('closed_at')
        );
    }
}
