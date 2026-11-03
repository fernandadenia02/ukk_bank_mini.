<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Customer;
use App\Models\CustomerPinResetOtp;
use App\Models\DailyReport;
use App\Models\AccountingJournal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

class TransactionController extends Controller
{
    /**
     * =========================================================
     * DAFTAR TRANSAKSI
     * =========================================================
     */
    public function index()
    {
        $query = Transaction::with([
            'customer',
            'teller'
        ]);

        if (Auth::user()->role === 'teller') {
            $query->where('teller_id', Auth::id());
        }

        $transactions = $query
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view(
            'transactions.index',
            compact('transactions')
        );
    }


    /**
     * =========================================================
     * FORM TRANSAKSI
     * =========================================================
     *
     * Bisa menerima customer_id dari:
     *
     * 1. Search Customer di Teller Dashboard
     * 2. QR Scanner
     * 3. Pilihan Customer manual
     *
     * Contoh:
     * /transactions/create?customer_id=5
     *
     * =========================================================
     */
    public function create(Request $request)
    {
        $dailyReport = DailyReport::where('teller_id', Auth::id())
            ->whereDate('date', today())
            ->first();

        if ($dailyReport && $dailyReport->closed_at) {
            return redirect()
                ->route('teller.dashboard')
                ->with('error', 'Kas harian sudah ditutup. Transaksi baru tidak dapat dibuat hari ini.');
        }

        $customers = Customer::query()
            ->orderBy('name', 'asc')
            ->get();

        $selectedCustomer = $request->customer_id;

        return view(
            'transactions.create',
            compact(
                'customers',
                'selectedCustomer'
            )
        );
    }

    public function sendCustomerPinResetOtp(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);

        if (!$customer->email) {
            return $this->pinResetRedirect($customer->id)
                ->with('pin_reset_error', 'Email customer belum terdaftar. Perbarui email customer terlebih dahulu.');
        }

        $otp = (string) random_int(100000, 999999);

        CustomerPinResetOtp::where('customer_id', $customer->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        CustomerPinResetOtp::create([
            'customer_id' => $customer->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);

        try {
            Mail::raw(
                "Halo {$customer->name},\n\n"
                . "Kode OTP untuk membuat PIN baru Bank Mini kamu adalah: {$otp}\n\n"
                . "Kode berlaku selama 5 menit dan hanya dapat digunakan sekali.\n"
                . "Jangan bagikan kode ini kepada siapa pun.\n\n"
                . "Jika kamu tidak meminta reset PIN, abaikan email ini.",
                function ($mail) use ($customer) {
                    $mail->to($customer->email)
                        ->subject('Kode OTP Reset PIN Bank Mini');
                }
            );

            Log::info('Email OTP reset PIN diterima oleh mail transport.', [
                'customer_id' => $customer->id,
                'mail_transport' => config('mail.default'),
            ]);
        } catch (Throwable $e) {
            CustomerPinResetOtp::where('customer_id', $customer->id)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            Log::error('Pengiriman email OTP reset PIN gagal.', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return $this->pinResetRedirect($customer->id)
                ->with('pin_reset_error', 'Email OTP gagal dikirim. Periksa konfigurasi email atau coba lagi.');
        }

        return $this->pinResetRedirect($customer->id)
            ->with(
                'pin_reset_status',
                'Server email menerima pengiriman OTP ke ' .
                $this->maskEmailAddress($customer->email) .
                '. Periksa Inbox dan Spam/Junk. Kode berlaku selama 5 menit.'
            );
    }

    public function resetCustomerPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'otp' => ['required', 'digits:6'],
            'pin' => ['required', 'digits:6', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->pinResetRedirect((int) $request->input('customer_id'))
                ->withErrors($validator)
                ->withInput($request->except(['otp', 'pin', 'pin_confirmation']));
        }

        $validated = $validator->validated();
        $customer = Customer::findOrFail($validated['customer_id']);

        $otpRecord = CustomerPinResetOtp::where('customer_id', $customer->id)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (
            !$otpRecord ||
            $otpRecord->expires_at->isPast() ||
            $otpRecord->attempts >= 5
        ) {
            return $this->pinResetRedirect($customer->id)
                ->with('pin_reset_error', 'OTP tidak valid atau sudah kedaluwarsa. Minta kode OTP baru.');
        }

        if (!Hash::check($validated['otp'], $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');

            return $this->pinResetRedirect($customer->id)
                ->with('pin_reset_error', 'Kode OTP salah.');
        }

        $pinUpdated = DB::transaction(function () use ($customer, $otpRecord, $validated) {
            $lockedCustomer = Customer::whereKey($customer->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedOtp = CustomerPinResetOtp::whereKey($otpRecord->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedOtp->used_at ||
                $lockedOtp->expires_at->isPast() ||
                $lockedOtp->attempts >= 5 ||
                !Hash::check($validated['otp'], $lockedOtp->otp_hash)
            ) {
                return false;
            }

            $lockedCustomer->update([
                'pin' => Hash::make($validated['pin']),
            ]);

            $lockedOtp->update(['used_at' => now()]);

            CustomerPinResetOtp::where('customer_id', $customer->id)
                ->whereNull('used_at')
                ->where('id', '!=', $lockedOtp->id)
                ->update(['used_at' => now()]);

            return true;
        });

        if (!$pinUpdated) {
            return $this->pinResetRedirect($customer->id)
                ->with('pin_reset_error', 'OTP tidak valid atau sudah kedaluwarsa. Minta kode OTP baru.');
        }

        return $this->pinResetRedirect($customer->id)
            ->with('pin_reset_status', 'PIN customer berhasil diperbarui.');
    }

    private function pinResetRedirect(int $customerId)
    {
        return redirect()->route('transactions.create', [
            'customer_id' => $customerId,
            'pin_reset' => 1,
        ])->withFragment('pin-reset');
    }

    private function maskEmailAddress(string $email): string
    {
        [$localPart, $domain] = explode('@', $email, 2);

        return mb_substr($localPart, 0, 1) .
            '***' .
            '@' . $domain;
    }


    /**
     * =========================================================
     * SIMPAN TRANSAKSI
     * =========================================================
     */
    public function store(Request $request)
    {
        $amountInput = $request->input('amount');
        if (is_string($amountInput)) {
            $request->merge([
                'amount' => preg_replace('/[.,\s]/', '', $amountInput),
            ]);
        }

        /**
         * =====================================================
         * VALIDASI
         * =====================================================
         */

        $rules = [
            'customer_id' => [
                'required',
                'exists:customers,id'
            ],

            'transaction_type' => [
                'required',
                'in:setoran,penarikan'
            ],

            'amount' => [
                'required',
                'numeric',
                'min:1000'
            ],
        ];

        if ($request->input('transaction_type') === 'penarikan') {
            $rules['customer_pin'] = [
                'required',
                'digits:6',
            ];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('customer_pin'));
        }

        $validated = $validator->validated();


        /**
         * =====================================================
         * CEK LOGIN
         * =====================================================
         */

        if (!Auth::check()) {

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Silakan login terlebih dahulu.'
                );
        }


        /**
         * =====================================================
         * PROSES DATABASE
         * =====================================================
         */

        DB::beginTransaction();

        try {
            \App\Models\User::whereKey(Auth::id())
                ->lockForUpdate()
                ->firstOrFail();

            $dailyReport = DailyReport::where('teller_id', Auth::id())
                ->whereDate('date', today())
                ->lockForUpdate()
                ->first();

            if ($dailyReport && $dailyReport->closed_at) {
                DB::rollBack();

                return back()
                    ->withInput($request->except('customer_pin'))
                    ->with('error', 'Kas harian sudah ditutup. Transaksi tidak dapat diproses hari ini.');
            }

            $report = $dailyReport ?: DailyReport::firstOrCreate(
                [
                    'teller_id' => Auth::id(),
                    'date' => now()->toDateString(),
                ],
                [
                    'beginning_balance' => 0,
                    'total_deposit' => 0,
                    'total_withdrawals' => 0,
                    'ending_balance' => 0,
                    'physical_money' => 0,
                    'difference' => 0,
                    'status' => 'pending',
                ]
            );

            /**
             * =================================================
             * CUSTOMER
             * =================================================
             */

            $customer = Customer::whereKey($validated['customer_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $customerIsActive = $customer->status === 'aktif';

            if (!$customerIsActive) {
                if ($validated['transaction_type'] !== 'setoran') {
                    DB::rollBack();

                    return back()
                        ->withInput($request->except('customer_pin'))
                        ->with('error', 'Customer belum aktif. Lakukan setoran minimal Rp 10.000 untuk aktivasi.');
                }

                if ($validated['amount'] < 10000) {
                    DB::rollBack();

                    return back()
                        ->withInput($request->except('customer_pin'))
                        ->with('error', 'Setoran pertama untuk aktivasi minimal Rp 10.000.');
                }
            }

            if (
                $validated['transaction_type'] === 'penarikan' &&
                !Hash::check($validated['customer_pin'], $customer->pin)
            ) {
                DB::rollBack();

                return back()
                    ->withErrors([
                        'customer_pin' => 'PIN nasabah salah. Transaksi tidak diproses.',
                    ])
                    ->withInput($request->except('customer_pin'));
            }


            /**
             * =================================================
             * SALDO AWAL
             * =================================================
             */

            $beginningBalance = $customer->balance;


            /**
             * =================================================
             * JUMLAH TRANSAKSI
             * =================================================
             */

            $amount = $validated['amount'];

            if (
                $validated['transaction_type'] === 'penarikan' &&
                $amount > (float) $report->ending_balance
            ) {
                DB::rollBack();

                return back()
                    ->withInput($request->except('customer_pin'))
                    ->with(
                        'error',
                        'Kas teller tidak mencukupi. Kas tersedia: Rp '
                        . number_format((float) $report->ending_balance, 0, ',', '.')
                        . '. Tambahkan modal awal atau tunggu setoran.'
                    );
            }


            /**
             * =================================================
             * HITUNG SALDO
             * =================================================
             */

            if (
                $validated['transaction_type'] === 'penarikan'
            ) {

                /**
                 * Saldo minimum yang harus tersisa
                 */
                $minimumBalance = 10000;


                /**
                 * Hitung saldo setelah penarikan
                 */
                $endingBalance =
                    $beginningBalance - $amount;


                /**
                 * Cek saldo minimum
                 */
                if (
                    $endingBalance < $minimumBalance
                ) {

                    DB::rollBack();

                    return back()
                        ->withInput($request->except('customer_pin'))
                        ->with(
                            'error',
                            'Transaksi ditolak. Saldo minimal yang harus tersisa adalah Rp 10.000.'
                        );
                }


            } else {

                /**
                 * =================================================
                 * SETORAN
                 * =================================================
                 */

                $endingBalance =
                    $beginningBalance + $amount;
            }


            /**
             * =================================================
             * UPDATE SALDO CUSTOMER
             * =================================================
             */

            $customer->update([
                'balance' => $endingBalance
            ]);

            if (!$customerIsActive) {
                $customer->update([
                    'status' => 'aktif',
                ]);
            }


            /**
             * =================================================
             * SIMPAN TRANSAKSI
             * =================================================
             */

            $transaction = Transaction::create([

                'customer_id' =>
                    $customer->id,

                'teller_id' =>
                    Auth::id(),

                'transaction_type' =>
                    $validated['transaction_type'],

                'amount' =>
                    $amount,

                'beginning_balance' =>
                    $beginningBalance,

                'ending_balance' =>
                    $endingBalance,

                'date' =>
                    now(),

            ]);


            /**
             * =================================================
             * DAILY REPORT OTOMATIS
             * =================================================
             */

            /**
             * =================================================
             * UPDATE DAILY REPORT
             * =================================================
             */

            if (
                $validated['transaction_type'] === 'setoran'
            ) {

                $report->increment(
                    'total_deposit',
                    $amount
                );

            } else {

                $report->increment(
                    'total_withdrawals',
                    $amount
                );
            }


            /**
             * =================================================
             * HITUNG SALDO DAILY REPORT
             * =================================================
             */

            $report->refresh();

            $report->ending_balance =
                $report->beginning_balance
                + $report->total_deposit
                - $report->total_withdrawals;


            /**
             * Uang fisik sementara
             */
            $report->physical_money =
                $report->ending_balance;


            /**
             * Selisih
             */
            $report->difference =
                $report->physical_money
                - $report->ending_balance;


            /**
             * Pastikan status tetap pending
             * selama laporan belum diproses.
             */
            if (
                !in_array(
                    $report->status,
                    ['approved', 'rejected']
                )
            ) {

                $report->status = 'pending';
            }


            $report->save();


            /**
             * =================================================
             * ACCOUNTING JOURNAL
             * =================================================
             */

            if (
                $validated['transaction_type'] === 'setoran'
            ) {

                /**
                 * =============================================
                 * SETORAN
                 * =============================================
                 *
                 * Kas
                 * Debit
                 *
                 * Tabungan Nasabah
                 * Credit
                 */


                // Kas bertambah
                AccountingJournal::create([

                    'transaction_id' =>
                        $transaction->id,

                    'account' =>
                        'Kas',

                    'debit' =>
                        $amount,

                    'credit' =>
                        0,

                    'date' =>
                        now(),

                ]);


                // Tabungan nasabah bertambah
                AccountingJournal::create([

                    'transaction_id' =>
                        $transaction->id,

                    'account' =>
                        'Tabungan Nasabah',

                    'debit' =>
                        0,

                    'credit' =>
                        $amount,

                    'date' =>
                        now(),

                ]);


            } else {

                /**
                 * =============================================
                 * PENARIKAN
                 * =============================================
                 *
                 * Tabungan Nasabah
                 * Debit
                 *
                 * Kas
                 * Credit
                 */


                // Tabungan nasabah berkurang
                AccountingJournal::create([

                    'transaction_id' =>
                        $transaction->id,

                    'account' =>
                        'Tabungan Nasabah',

                    'debit' =>
                        $amount,

                    'credit' =>
                        0,

                    'date' =>
                        now(),

                ]);


                // Kas berkurang
                AccountingJournal::create([

                    'transaction_id' =>
                        $transaction->id,

                    'account' =>
                        'Kas',

                    'debit' =>
                        0,

                    'credit' =>
                        $amount,

                    'date' =>
                        now(),

                ]);
            }


            /**
             * =================================================
             * SEMUA BERHASIL
             * =================================================
             */

            DB::commit();


            /**
             * =================================================
             * REDIRECT
             * =================================================
             */

            return redirect()
                ->route('transactions.receipt', $transaction->id);


        } catch (\Exception $e) {

            /**
             * =================================================
             * JIKA TERJADI ERROR
             * =================================================
             */

            DB::rollBack();


            return back()
                ->withInput($request->except('customer_pin'))
                ->with(
                    'error',
                    'Transaksi gagal disimpan: ' .
                    $e->getMessage()
                );
        }
    }


    /**
     * =========================================================
     * BUKTI TRANSAKSI
     * =========================================================
     */
    public function receipt($id)
    {
        $transaction = Transaction::with([
            'customer',
            'teller'
        ])
        ->findOrFail($id);

        if (
            Auth::user()->role === 'teller' &&
            (int) $transaction->teller_id !== (int) Auth::id()
        ) {
            abort(404);
        }

        return view(
            'transactions.receipt',
            compact('transaction')
        );
    }
}