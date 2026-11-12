<?php

namespace Tests\Feature;

use App\Models\CustomerPinResetOtp;
use App\Models\Customer;
use App\Models\CashAdvanceRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WithdrawalAppointment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TellerTransactionPinTest extends TestCase
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
            $table->string('username')->unique();
            $table->string('password');
            $table->string('status')->default('nonaktif');
            $table->string('account_number');
            $table->string('nis');
            $table->string('name');
            $table->string('class')->nullable();
            $table->string('email')->nullable();
            $table->string('pin');
            $table->decimal('balance', 12, 2);
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('teller_id');
            $table->string('transaction_type');
            $table->decimal('amount', 12, 2);
            $table->decimal('beginning_balance', 12, 2);
            $table->decimal('ending_balance', 12, 2);
            $table->dateTime('date');
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

        Schema::create('accounting_journal', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('account');
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->dateTime('date');
        });

        Schema::create('customer_pin_reset_otps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('otp_hash');
            $table->dateTime('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('withdrawal_pin_approvals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('teller_id');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('withdrawal_appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->date('withdrawal_date');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->dateTime('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_withdrawal_cannot_be_saved_without_customer_mobile_pin_approval(): void
    {
        [$teller, $customer] = $this->createTellerAndCustomer();
        $customer->update(['balance' => 20000]);

        $this->actingAs($teller)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'transaction_type' => 'penarikan',
                'amount' => 1000,
            ])
            ->assertSessionHasErrors('pin_approval_id')
            ->assertSessionMissing('_old_input.pin_approval_id');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertEquals(20000, $customer->fresh()->balance);
    }

    public function test_teller_can_create_customer_mobile_pin_approval_request(): void
    {
        [$teller, $customer] = $this->createTellerAndCustomer();
        $customer->update(['balance' => 20000]);
        $this->actingAs($teller)
            ->post(route('teller.daily-reports.opening-cash'), ['amount' => '5.000'])
            ->assertSessionHas('success');
        $this->approveCashRequest(CashAdvanceRequest::firstOrFail(), $teller);

        $response = $this->actingAs($teller)
            ->postJson(route('transactions.pin-approval.create'), [
                'customer_id' => $customer->id,
                'amount' => 1000,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('withdrawal_pin_approvals', [
            'id' => $response->json('data.id'),
            'customer_id' => $customer->id,
            'teller_id' => $teller->id,
            'amount' => 1000,
            'status' => 'pending',
        ]);

        $approvalId = $response->json('data.id');
        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/customer/withdrawal-pin-requests/{$approvalId}/verify", [
                'pin' => '012345',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($teller, 'web')
            ->getJson(route('transactions.pin-approval.status', $approvalId))
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->post(route('transactions.store'), [
            'customer_id' => $customer->id,
            'transaction_type' => 'penarikan',
            'amount' => 1000,
            'pin_approval_id' => $approvalId,
        ])->assertRedirect(route('transactions.receipt', Transaction::query()->max('id')));

        $this->assertDatabaseHas('withdrawal_pin_approvals', [
            'id' => $approvalId,
            'status' => 'approved',
        ]);
        $this->assertNotNull(
            \App\Models\WithdrawalPinApproval::findOrFail($approvalId)->consumed_at
        );
        $this->assertEquals(19000, $customer->fresh()->balance);

        $this->post(route('transactions.store'), [
            'customer_id' => $customer->id,
            'transaction_type' => 'penarikan',
            'amount' => 1000,
            'pin_approval_id' => $approvalId,
        ])->assertSessionHas(
            'error',
            'Persetujuan PIN tidak valid atau kedaluwarsa. Minta verifikasi PIN nasabah kembali.'
        );
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_customer_pin_is_not_required_for_a_deposit(): void
    {
        [$teller, $customer] = $this->createTellerAndCustomer();

        $this->actingAs($teller)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'transaction_type' => 'setoran',
                'amount' => 1000,
            ])
            ->assertRedirect(route(
                'transactions.receipt',
                Transaction::query()->max('id')
            ));

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $customer->id,
            'teller_id' => $teller->id,
            'amount' => 1000,
        ]);
        $this->assertEquals(11000, $customer->fresh()->balance);
        $this->assertArrayNotHasKey('pin', $customer->fresh()->toArray());

        $this->get(route('transactions.receipt', Transaction::query()->max('id')))
            ->assertOk()
            ->assertSee('Kembali ke Beranda')
            ->assertSee(route('teller.dashboard'))
            ->assertSee('Lanjut Transaksi')
            ->assertSee(route('transactions.create', [
                'customer_id' => $customer->id,
            ]));
    }

    public function test_teller_opening_cash_is_recorded_and_prevents_cash_shortfalls(): void
    {
        [$teller, $customer] = $this->createTellerAndCustomer();
        $customer->update(['balance' => 20000]);

        $this->actingAs($teller)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'pin_approval_id' => $this->createApprovedPinApproval($customer, $teller, 3000)->id,
                'transaction_type' => 'penarikan',
                'amount' => 3000,
            ])
            ->assertSessionHas(
                'error',
                'Kas teller tidak mencukupi. Kas tersedia: Rp 0. Tambahkan modal awal atau tunggu setoran.'
            );

        $this->assertDatabaseCount('transactions', 0);
        $this->assertEquals(20000, $customer->fresh()->balance);

        $this->post(route('teller.daily-reports.opening-cash'), [
            'amount' => '5.000',
        ])->assertSessionHas('success');

        $cashRequest = CashAdvanceRequest::firstOrFail();
        $this->assertSame('pending', $cashRequest->status);
        $this->approveCashRequest($cashRequest, $teller);

        $this->post(route('transactions.store'), [
            'customer_id' => $customer->id,
            'pin_approval_id' => $this->createApprovedPinApproval($customer, $teller, 6000)->id,
            'transaction_type' => 'penarikan',
            'amount' => 6000,
        ])->assertSessionHas(
            'error',
            'Kas teller tidak mencukupi. Kas tersedia: Rp 5.000. Tambahkan modal awal atau tunggu setoran.'
        );
        $this->assertDatabaseCount('transactions', 0);

        $this->post(route('transactions.store'), [
            'customer_id' => $customer->id,
            'pin_approval_id' => $this->createApprovedPinApproval($customer, $teller, 3000)->id,
            'transaction_type' => 'penarikan',
            'amount' => 3000,
        ])->assertRedirect(route('transactions.receipt', Transaction::query()->max('id')));

        $report = \App\Models\DailyReport::where('teller_id', $teller->id)->firstOrFail();
        $this->assertEquals(5000, $report->beginning_balance);
        $this->assertEquals(3000, $report->total_withdrawals);
        $this->assertEquals(2000, $report->ending_balance);

        $this->post(route('teller.daily-reports.opening-cash'), [
            'amount' => '8.000',
        ])->assertSessionHas(
            'error',
            'Permintaan modal tambahan hanya dapat diajukan saat kas tersedia sudah habis.'
        );
        $this->assertEquals(5000, $report->fresh()->beginning_balance);
    }

    public function test_customer_daily_withdrawal_limit_requires_an_approved_appointment_for_the_day(): void
    {
        [$teller, $customer] = $this->createTellerAndCustomer();
        $customer->update(['balance' => 4100000]);

        $this->actingAs($teller)->post(route('teller.daily-reports.opening-cash'), [
            'amount' => '5.000.000',
        ])->assertSessionHas('success');
        $this->approveCashRequest(CashAdvanceRequest::firstOrFail(), $teller);

        Transaction::create([
            'customer_id' => $customer->id,
            'teller_id' => $teller->id,
            'transaction_type' => 'penarikan',
            'amount' => 900000,
            'beginning_balance' => 5000000,
            'ending_balance' => 4100000,
            'date' => now(),
        ]);

        $pinApproval = $this->createApprovedPinApproval($customer, $teller, 200000);
        $this->post(route('transactions.store'), [
            'customer_id' => $customer->id,
            'pin_approval_id' => $pinApproval->id,
            'transaction_type' => 'penarikan',
            'amount' => 200000,
        ])->assertSessionHas('error', 'Batas penarikan harian nasabah adalah Rp 1.000.000. Penarikan lebih dari batas hanya dapat dilakukan sesuai janji yang sudah disetujui teller untuk hari ini.');

        $this->assertDatabaseCount('transactions', 1);
        $this->assertEquals(4100000, $customer->fresh()->balance);

        $appointment = WithdrawalAppointment::create([
            'customer_id' => $customer->id,
            'withdrawal_date' => today()->toDateString(),
            'amount' => 1500000,
            'status' => 'approved',
            'reviewed_by' => $teller->id,
            'reviewed_at' => now(),
        ]);

        $this->post(route('transactions.store'), [
            'customer_id' => $customer->id,
            'pin_approval_id' => $pinApproval->id,
            'transaction_type' => 'penarikan',
            'amount' => 200000,
        ])->assertRedirect(route('transactions.receipt', Transaction::query()->max('id')));

        $secondPinApproval = $this->createApprovedPinApproval($customer, $teller, 400000);
        $this->post(route('transactions.store'), [
            'customer_id' => $customer->id,
            'pin_approval_id' => $secondPinApproval->id,
            'transaction_type' => 'penarikan',
            'amount' => 400000,
        ])->assertRedirect(route('transactions.receipt', Transaction::query()->max('id')));

        $this->assertNotNull($appointment->fresh()->consumed_at);
        $this->assertEquals(3500000, $customer->fresh()->balance);
        $this->assertDatabaseCount('transactions', 3);
    }

    public function test_withdrawal_appointment_history_is_available_from_its_own_page_not_the_dashboard(): void
    {
        [$teller, $customer] = $this->createTellerAndCustomer();
        $appointment = WithdrawalAppointment::create([
            'customer_id' => $customer->id,
            'withdrawal_date' => today()->addDay()->toDateString(),
            'amount' => 1500000,
            'status' => 'approved',
            'reviewed_by' => $teller->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($teller)
            ->withSession(['role' => 'teller'])
            ->get(route('teller.dashboard'))
            ->assertOk()
            ->assertDontSee('Rp 1.500.000')
            ->assertSee(route('teller.withdrawal-appointments.index'), false);

        $this->get(route('teller.withdrawal-appointments.index'))
            ->assertOk()
            ->assertSee('Riwayat Janji Penarikan')
            ->assertSee($customer->name)
            ->assertSee('Disetujui')
            ->assertSee('Rp 1.500.000')
            ->assertDontSee(route('teller.withdrawal-appointments.approve', $appointment->id));
    }

    public function test_admin_daily_report_shows_teller_opening_cash_for_review(): void
    {
        $teller = User::create([
            'officer_name' => 'Teller',
            'username' => 'teller',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);
        $admin = User::create([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        $this->actingAs($teller)
            ->post(route('teller.daily-reports.opening-cash'), [
                'amount' => '5.000',
            ])
            ->assertSessionHas('success');

        $report = \App\Models\DailyReport::where('teller_id', $teller->id)->firstOrFail();
        $cashRequest = CashAdvanceRequest::firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Permintaan Modal Kas Teller')
            ->assertSee('Rp 5.000');

        $this->post(route('cash-advance-requests.approve', $cashRequest->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cash_advance_requests', [
            'id' => $cashRequest->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
        $this->assertEquals(5000, $report->fresh()->beginning_balance);

        $this->get(route('daily-reports.show', $report->id))
            ->assertOk()
            ->assertSee('Modal Awal Kas')
            ->assertSee('Rp 5.000');
    }

    public function test_teller_can_request_and_admin_can_approve_more_cash_after_cash_runs_out(): void
    {
        [$teller, $customer] = $this->createTellerAndCustomer();
        $customer->update(['balance' => 20000]);
        $admin = User::create([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        $this->actingAs($teller)->post(route('teller.daily-reports.opening-cash'), [
            'amount' => '3.000',
        ])->assertSessionHas('success');
        $this->approveCashRequest(CashAdvanceRequest::firstOrFail(), $teller, $admin);

        $this->post(route('transactions.store'), [
            'customer_id' => $customer->id,
            'pin_approval_id' => $this->createApprovedPinApproval($customer, $teller, 3000)->id,
            'transaction_type' => 'penarikan',
            'amount' => 3000,
        ])->assertRedirect(route('transactions.receipt', Transaction::query()->max('id')));

        $report = \App\Models\DailyReport::where('teller_id', $teller->id)->firstOrFail();
        $this->assertEquals(0, $report->fresh()->ending_balance);

        $this->post(route('teller.daily-reports.opening-cash'), [
            'amount' => '4.000',
        ])->assertSessionHas('success');

        $secondRequest = CashAdvanceRequest::orderByDesc('id')->firstOrFail();
        $this->assertSame('pending', $secondRequest->status);
        $this->actingAs($admin)
            ->post(route('cash-advance-requests.approve', $secondRequest->id))
            ->assertSessionHas('success');

        $this->assertEquals(7000, $report->fresh()->beginning_balance);
        $this->assertEquals(4000, $report->fresh()->ending_balance);
        $this->assertDatabaseHas('cash_advance_requests', [
            'id' => $secondRequest->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_reject_cash_request_and_teller_can_submit_a_new_request(): void
    {
        [$teller] = $this->createTellerAndCustomer();
        $admin = User::create([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        $this->actingAs($teller)
            ->post(route('teller.daily-reports.opening-cash'), [
                'amount' => '5.000',
            ])
            ->assertSessionHas('success');

        $request = CashAdvanceRequest::firstOrFail();
        $this->actingAs($admin)
            ->post(route('cash-advance-requests.reject', $request->id), [
                'rejection_reason' => 'Jumlah perlu dikonfirmasi.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cash_advance_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'rejection_reason' => 'Jumlah perlu dikonfirmasi.',
        ]);
        $this->assertEquals(0, $request->report->fresh()->beginning_balance);

        $this->actingAs($teller)
            ->post(route('teller.daily-reports.opening-cash'), [
                'amount' => '7.000',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('cash_advance_requests', 2);
        $this->assertSame(
            'pending',
            CashAdvanceRequest::orderByDesc('id')->firstOrFail()->status
        );
    }

    public function test_correct_customer_pin_allows_a_withdrawal(): void
    {
        [$teller, $customer] = $this->createTellerAndCustomer();
        $customer->update(['balance' => 20000]);

        $this->actingAs($teller)
            ->post(route('teller.daily-reports.opening-cash'), [
                'amount' => '5.000',
            ])
            ->assertSessionHas('success');
        $this->approveCashRequest(CashAdvanceRequest::firstOrFail(), $teller);

        $this->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'pin_approval_id' => $this->createApprovedPinApproval($customer, $teller, 1000)->id,
                'transaction_type' => 'penarikan',
                'amount' => 1000,
            ])
            ->assertRedirect(route(
                'transactions.receipt',
                Transaction::query()->max('id')
            ));

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $customer->id,
            'teller_id' => $teller->id,
            'transaction_type' => 'penarikan',
            'amount' => 1000,
        ]);
        $this->assertEquals(19000, $customer->fresh()->balance);
    }

    public function test_inactive_customer_requires_minimum_activation_deposit_and_is_activated_afterwards(): void
    {
        [$teller, $customer] = $this->createTellerAndCustomer();
        $customer->update([
            'status' => 'nonaktif',
            'balance' => 0,
        ]);

        $this->actingAs($teller)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'transaction_type' => 'setoran',
                'amount' => 9999,
            ])
            ->assertSessionHas('error', 'Setoran pertama untuk aktivasi minimal Rp 10.000.');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertEquals(0, $customer->fresh()->balance);

        $this->post(route('transactions.store'), [
            'customer_id' => $customer->id,
            'transaction_type' => 'setoran',
            'amount' => 10000,
        ])->assertRedirect(route(
            'transactions.receipt',
            Transaction::query()->max('id')
        ));

        $this->assertEquals(10000, $customer->fresh()->balance);
        $this->assertSame('aktif', $customer->fresh()->status);
    }

    public function test_customer_pin_is_stored_as_a_hash(): void
    {
        [, $customer] = $this->createTellerAndCustomer();

        $this->assertNotSame('012345', $customer->pin);
        $this->assertTrue(Hash::check('012345', $customer->pin));
    }

    public function test_teller_transaction_form_requests_pin_approval_from_customer_mobile_app(): void
    {
        [$teller] = $this->createTellerAndCustomer();

        $this->actingAs($teller)
            ->get(route('transactions.create'))
            ->assertOk()
            ->assertDontSee('name="customer_pin"', false)
            ->assertSee('id="customerSearch"', false)
            ->assertSee('id="searchCustomer"', false)
            ->assertSee('id="startScanner"', false)
            ->assertSee('placeholder="Cari nama atau nomor rekening nasabah..."', false)
            ->assertSee('Minta PIN melalui Aplikasi Nasabah')
            ->assertSee(route('transactions.pin-approval.create'), false)
            ->assertSee('pinApprovalId.value = approvalId', false)
            ->assertSee(route('transactions.pin-reset.send'), false)
            ->assertSee(route('transactions.pin-reset.update'), false)
            ->assertSee('name="pin_confirmation"', false);
    }

    public function test_amount_input_formats_thousands_and_submits_numeric_value(): void
    {
        [$teller, $customer] = $this->createTellerAndCustomer();

        $this->actingAs($teller)
            ->get(route('transactions.create'))
            ->assertOk()
            ->assertSee('type="text"', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertSee("Number(amount.value.replace(/\\D/g, ''))", false)
            ->assertSee("amount.value.replace(/\\D/g, '')", false)
            ->assertSee('id="cashDenominationTable"', false)
            ->assertSee('data-denomination="20000"', false)
            ->assertSee('data-denomination="50000"', false)
            ->assertSee('data-denomination="100000"', false)
            ->assertSee('cash-denomination-quantity', false)
            ->assertSee('id="uiConfirmModal"', false)
            ->assertSee('window.showUiConfirm', false)
            ->assertDontSee('window.confirm', false)
            ->assertSee('Mengisi jumlah lembar akan memperbarui nominal transaksi.')
            ->assertSee('Jika nominal diketik manual, jumlah lembar akan dikosongkan.');

        $this->post(route('transactions.store'), [
            'customer_id' => $customer->id,
            'transaction_type' => 'setoran',
            'amount' => '10.000',
        ])->assertRedirect(route(
            'transactions.receipt',
            Transaction::query()->max('id')
        ));

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $customer->id,
            'amount' => 10000,
        ]);
        $this->assertEquals(20000, $customer->fresh()->balance);
    }

    public function test_teller_dashboard_has_a_qr_button_near_search_that_opens_the_scanner(): void
    {
        [$teller] = $this->createTellerAndCustomer();

        $this->actingAs($teller)
            ->get(route('teller.dashboard'))
            ->assertOk()
            ->assertSee(route('transactions.create', ['scan' => 1]) . '#qr-scanner');

        $this->get(route('transactions.create', ['scan' => 1]))
            ->assertOk()
            ->assertSee("new URLSearchParams(window.location.search).get('scan') === '1'", false)
            ->assertSee('startScanner.click()', false);
    }

    public function test_teller_dashboard_lists_inactive_customers_with_activation_transaction_link(): void
    {
        [$teller, $customer] = $this->createTellerAndCustomer();
        $customer->update([
            'status' => 'nonaktif',
            'balance' => 0,
        ]);

        $this->actingAs($teller)
            ->get(route('teller.dashboard'))
            ->assertOk()
            ->assertSee('Customer Belum Aktif')
            ->assertSee('Aktivasi via Setoran')
            ->assertSee(route('transactions.create', [
                'customer_id' => $customer->id,
                'activation' => 1,
            ]));

        $this->get(route('transactions.create', [
            'customer_id' => $customer->id,
            'activation' => 1,
        ]))
            ->assertOk()
            ->assertSee('data-active="0"', false)
            ->assertSee('setoran pertama minimal Rp 10.000');
    }

    public function test_teller_can_send_customer_pin_reset_otp_to_registered_email(): void
    {
        Mail::fake();
        [$teller, $customer] = $this->createTellerAndCustomer();
        $customer->update([
            'status' => 'aktif',
            'email' => 'customer@example.test',
        ]);

        $this->actingAs($teller)
            ->post(route('transactions.pin-reset.send'), [
                'customer_id' => $customer->id,
            ])
            ->assertRedirect(route('transactions.create', [
                'customer_id' => $customer->id,
                'pin_reset' => 1,
            ]) . '#pin-reset')
            ->assertSessionHas('pin_reset_status', function ($message) {
                return str_contains($message, 'c***@example.test') &&
                    str_contains($message, 'Spam/Junk');
            });

        $otp = CustomerPinResetOtp::where('customer_id', $customer->id)->firstOrFail();
        $this->assertNotSame('123456', $otp->otp_hash);
        $this->assertTrue($otp->expires_at->isFuture());
    }

    public function test_teller_cannot_send_customer_pin_reset_otp_without_registered_email(): void
    {
        Mail::fake();
        [$teller, $customer] = $this->createTellerAndCustomer();
        $customer->update(['status' => 'aktif']);

        $this->actingAs($teller)
            ->post(route('transactions.pin-reset.send'), [
                'customer_id' => $customer->id,
            ])
            ->assertRedirect(route('transactions.create', [
                'customer_id' => $customer->id,
                'pin_reset' => 1,
            ]) . '#pin-reset')
            ->assertSessionHas('pin_reset_error');

        $this->assertDatabaseCount('customer_pin_reset_otps', 0);
    }

    public function test_customer_can_reset_pin_with_a_valid_email_otp(): void
    {
        [$teller, $customer] = $this->createTellerAndCustomer();
        $customer->update(['status' => 'aktif']);

        $otp = CustomerPinResetOtp::create([
            'customer_id' => $customer->id,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);

        $this->actingAs($teller)
            ->post(route('transactions.pin-reset.update'), [
                'customer_id' => $customer->id,
                'otp' => '123456',
                'pin' => '987654',
                'pin_confirmation' => '987654',
            ])
            ->assertRedirect(route('transactions.create', [
                'customer_id' => $customer->id,
                'pin_reset' => 1,
            ]) . '#pin-reset')
            ->assertSessionHas('pin_reset_status');

        $this->assertTrue(Hash::check('987654', $customer->fresh()->pin));
        $this->assertNotSame('987654', $customer->fresh()->pin);
        $this->assertNotNull($otp->fresh()->used_at);
    }

    public function test_withdrawal_requires_pin_approval_and_does_not_keep_it_in_old_input(): void
    {
        [$teller, $customer] = $this->createTellerAndCustomer();
        $customer->update(['balance' => 20000]);

        $this->actingAs($teller)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'transaction_type' => 'penarikan',
                'amount' => 1000,
            ])
            ->assertSessionHasErrors('pin_approval_id')
            ->assertSessionMissing('_old_input.pin_approval_id');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_teller_can_close_cash_with_a_difference_and_cannot_transact_afterward(): void
    {
        [$teller, $customer] = $this->createTellerAndCustomer();

        $this->actingAs($teller)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'transaction_type' => 'setoran',
                'amount' => 1000,
            ])
            ->assertRedirect(route('transactions.receipt', Transaction::query()->max('id')));

        $this->post(route('teller.daily-reports.close'), [
            'physical_money' => '900',
        ])
            ->assertRedirect(route('teller.dashboard'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('daily_reports', [
            'teller_id' => $teller->id,
            'total_deposit' => 1000,
            'ending_balance' => 1000,
            'physical_money' => 900,
            'difference' => -100,
        ]);
        $this->assertNotNull(
            \App\Models\DailyReport::where('teller_id', $teller->id)->firstOrFail()->closed_at
        );

        $this->post(route('transactions.store'), [
            'customer_id' => $customer->id,
            'transaction_type' => 'setoran',
            'amount' => 1000,
        ])
            ->assertSessionHas('error', 'Kas harian sudah ditutup. Transaksi tidak dapat diproses hari ini.');
    }

    public function test_teller_can_transact_while_today_report_is_rejected_and_reopened(): void
    {
        [$teller, $customer] = $this->createTellerAndCustomer();
        \App\Models\DailyReport::create([
            'teller_id' => $teller->id,
            'date' => today()->toDateString(),
            'beginning_balance' => 0,
            'total_deposit' => 0,
            'total_withdrawals' => 0,
            'ending_balance' => 0,
            'physical_money' => 0,
            'difference' => 0,
            'status' => 'rejected',
            'rejection_reason' => 'Mohon perbaiki laporan.',
        ]);

        $this->actingAs($teller)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'transaction_type' => 'setoran',
                'amount' => 1000,
            ])
            ->assertRedirect(route('transactions.receipt', Transaction::query()->max('id')));

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $customer->id,
            'teller_id' => $teller->id,
            'transaction_type' => 'setoran',
            'amount' => 1000,
        ]);
        $this->assertDatabaseHas('daily_reports', [
            'teller_id' => $teller->id,
            'status' => 'rejected',
            'total_deposit' => 1000,
            'rejection_reason' => 'Mohon perbaiki laporan.',
        ]);

        $this->get(route('transactions.create'))
            ->assertOk();
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_teller_can_close_cash_with_no_transactions(): void
    {
        $teller = User::create([
            'officer_name' => 'Teller',
            'username' => 'teller',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);

        $this->actingAs($teller)
            ->post(route('teller.daily-reports.close'), [
                'physical_money' => '0',
            ])
            ->assertRedirect(route('teller.dashboard'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('daily_reports', [
            'teller_id' => $teller->id,
            'total_deposit' => 0,
            'physical_money' => 0,
            'difference' => 0,
        ]);
    }

    private function createTellerAndCustomer(): array
    {
        $teller = User::create([
            'officer_name' => 'Teller',
            'username' => 'teller',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);

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

        return [$teller, $customer];
    }

    private function createApprovedPinApproval(Customer $customer, User $teller, int $amount): \App\Models\WithdrawalPinApproval
    {
        return \App\Models\WithdrawalPinApproval::create([
            'customer_id' => $customer->id,
            'teller_id' => $teller->id,
            'amount' => $amount,
            'status' => 'approved',
            'expires_at' => now()->addMinutes(5),
            'approved_at' => now(),
        ]);
    }

    private function approveCashRequest(
        CashAdvanceRequest $cashRequest,
        User $teller,
        ?User $admin = null,
    ): void {
        $admin ??= User::create([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->post(route('cash-advance-requests.approve', $cashRequest->id))
            ->assertSessionHas('success');

        $this->actingAs($teller);
    }
}
