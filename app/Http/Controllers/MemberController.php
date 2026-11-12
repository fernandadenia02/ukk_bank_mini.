<?php

namespace App\Http\Controllers;

use App\Rules\StrongPassword;
use App\Models\Customer;
use App\Models\Member;
use App\Models\Transaction;
use App\Models\DailyReport;
use App\Models\AccountingJournal;
use App\Services\CustomerAccountCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class MemberController extends Controller
{
    public function create()
    {
        return view('members.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:30', 'unique:members,nis', 'unique:customers,nis'],
            'name' => ['required', 'string', 'max:100'],
            'class' => ['required', 'string', 'max:20'],
            'location' => ['required', 'string'],
        ]);

        Member::create($validated);

        return redirect()
            ->route('members.index')
            ->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $member = Member::with('customer')->findOrFail($id);

        return view('members.edit', compact('member'));
    }

    public function update(Request $request, int $id)
    {
        $member = Member::with('customer')->findOrFail($id);
        $customerUniqueRule = Rule::unique('customers', 'nis');

        if ($member->customer) {
            $customerUniqueRule->ignore($member->customer->id);
        }

        $validated = $request->validate([
            'nis' => [
                'required',
                'string',
                'max:30',
                Rule::unique('members', 'nis')->ignore($member->id),
                $customerUniqueRule,
            ],
            'name' => ['required', 'string', 'max:100'],
            'class' => ['required', 'string', 'max:20'],
            'location' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($member, $validated): void {
            $member->refresh();
            $member->fill($validated);
            $member->save();

            if ($member->customer) {
                $member->customer->update($validated);
            }
        });

        return redirect()
            ->route('members.index')
            ->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function index(Request $request)
    {
        return $this->memberList($request, false);
    }

    public function promotion()
    {
        $members = Member::query()
            ->orderBy('class')
            ->orderBy('name')
            ->get()
            ->filter(fn (Member $member) => $this->nextClass($member->class) !== null)
            ->values();
        $nextClasses = $members->mapWithKeys(
            fn (Member $member) => [$member->id => $this->nextClass($member->class)]
        );

        return view('members.promotion', compact('members', 'nextClasses'));
    }

    public function promote(Request $request)
    {
        $validated = $request->validate([
            'stay_ids' => ['sometimes', 'array'],
            'stay_ids.*' => ['integer', 'distinct', 'exists:members,id'],
        ]);
        $stayIds = array_map('intval', $validated['stay_ids'] ?? []);

        $result = DB::transaction(function () use ($stayIds) {
            $members = Member::with('customer')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $eligible = $members->filter(
                fn (Member $member) => $this->nextClass($member->class) !== null
            );
            $eligibleIds = $eligible->modelKeys();
            $invalidStayIds = array_diff($stayIds, $eligibleIds);

            if ($invalidStayIds !== []) {
                throw ValidationException::withMessages([
                    'stay_ids' => 'Pilih hanya siswa yang masih berada di kelas X, XI, atau XII.',
                ]);
            }

            if ($eligible->isEmpty()) {
                return ['promoted' => 0, 'stayed' => 0, 'alumni' => 0];
            }

            $stayIdLookup = array_fill_keys($stayIds, true);
            $promoted = 0;
            $alumniCount = 0;

            foreach ($eligible as $member) {
                if (isset($stayIdLookup[$member->id])) {
                    continue;
                }

                $nextClass = $this->nextClass($member->class);
                $member->class = $nextClass;
                $member->save();

                $customer = $member->customer;
                if ($customer) {
                    $customer->class = $nextClass;
                    $customer->save();

                    if ($nextClass === 'ALUMNI') {
                        $customer->update(['status' => 'nonaktif']);
                        $customer->tokens()->delete();
                    }
                }

                $promoted++;
                if ($nextClass === 'ALUMNI') {
                    $alumniCount++;
                }
            }

            return [
                'promoted' => $promoted,
                'stayed' => count($stayIds),
                'alumni' => $alumniCount,
            ];
        });

        if ($result['promoted'] === 0 && $result['stayed'] === 0) {
            return redirect()
                ->route('members.promotion')
                ->with('error', 'Tidak ada siswa di kelas X, XI, atau XII untuk dinaikkan.');
        }

        return redirect()
            ->route('members.promotion')
            ->with(
                'success',
                "{$result['promoted']} siswa berhasil diproses naik kelas, "
                    . "{$result['stayed']} siswa tetap di kelas. "
                    . "{$result['alumni']} siswa menjadi alumni dan akunnya dinonaktifkan."
            );
    }

    public function tellerIndex(Request $request)
    {
        return $this->memberList($request, true);
    }

    private function nextClass(string $class): ?string
    {
        if (!preg_match('/^(XII|XI|X)(?=$|[\s\/-])(.*)$/i', trim($class), $matches)) {
            return null;
        }

        return match (strtoupper($matches[1])) {
            'X' => 'XI' . $matches[2],
            'XI' => 'XII' . $matches[2],
            'XII' => 'ALUMNI',
        };
    }

    private function memberList(Request $request, bool $forTeller)
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $membersQuery = Member::with('customer')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('nis', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('class', 'like', "%{$keyword}%");
                });
            });

        if ($forTeller) {
            $membersQuery->whereDoesntHave('customer');
            $membersQuery->orderByDesc('id');
        } else {
            $membersQuery->orderBy('class')
                ->orderBy('name');
        }

        $members = $membersQuery->paginate(10)->withQueryString();

        return view('members.index', compact('members', 'keyword', 'forTeller'));
    }

    public function downloadImportTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Anggota');

        $headers = ['nis', 'name', 'class', 'location'];
        foreach ($headers as $column => $header) {
            $columnLetter = Coordinate::stringFromColumnIndex($column + 1);
            $sheet->setCellValue($columnLetter . '1', $header);
            $sheet->getColumnDimension($columnLetter)->setWidth(28);
            $sheet->getStyle($columnLetter . '2:' . $columnLetter . '5001')
                ->getNumberFormat()
                ->setFormatCode('@');
        }

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk');
        $guide->fromArray([
            ['Panduan Import Anggota'],
            ['Isi satu calon nasabah per baris pada sheet Anggota, mulai dari baris 2.'],
            ['Kolom wajib: nis, name, class, location.'],
            ['Jangan masukkan nomor telepon, email, password, atau PIN pada file anggota.'],
            ['Email, nomor telepon, password login, dan PIN diisi teller saat membuka rekening.'],
            ['Anggota yang sudah memiliki rekening tidak dapat diimport kembali menggunakan NIS yang sama.'],
            ['Jika ada data salah atau NIS duplikat, seluruh import dibatalkan.'],
        ], null, 'A1');
        $guide->getColumnDimension('A')->setWidth(110);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template-import-anggota.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        $expectedHeaders = ['nis', 'name', 'class', 'location'];
        $rows = [];
        $errors = [];

        try {
            $spreadsheet = IOFactory::load($validated['file']->getRealPath());
            $sheet = $spreadsheet->getSheetByName('Anggota') ?? $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();
            $headers = [];

            for ($column = 1; $column <= count($expectedHeaders); $column++) {
                $headers[] = strtolower(trim((string) $sheet->getCell([$column, 1])->getValue()));
            }

            if (
                $headers !== $expectedHeaders ||
                Coordinate::columnIndexFromString($sheet->getHighestDataColumn(1)) !== count($expectedHeaders)
            ) {
                return back()->with('error', 'Format Excel tidak sesuai. Unduh dan gunakan template import anggota.');
            }

            if ($highestRow > 5001) {
                return back()->with('error', 'Import maksimal 5.000 anggota per file.');
            }

            for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
                $row = [];
                $hasData = false;

                foreach ($expectedHeaders as $index => $header) {
                    $cell = $sheet->getCell([$index + 1, $rowNumber]);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        $errors[] = "Baris {$rowNumber}: formula tidak diperbolehkan.";
                        continue 2;
                    }

                    $value = trim((string) $cell->getFormattedValue());
                    $row[$header] = $value === '' ? null : $value;
                    $hasData = $hasData || $row[$header] !== null;
                }

                if (!$hasData) {
                    continue;
                }

                $validator = Validator::make($row, [
                    'nis' => ['required', 'string', 'max:30', 'unique:members,nis', 'unique:customers,nis'],
                    'name' => ['required', 'string', 'max:100'],
                    'class' => ['required', 'string', 'max:20'],
                    'location' => ['required', 'string'],
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->all() as $error) {
                        $errors[] = "Baris {$rowNumber}: {$error}";
                    }
                    continue;
                }

                if (isset($rows['nis'][$row['nis']])) {
                    $errors[] = "Baris {$rowNumber}: NIS duplikat dengan baris {$rows['nis'][$row['nis']]}.";
                    continue;
                }

                $rows['nis'][$row['nis']] = $rowNumber;
                $rows['data'][] = $row;
            }

            $spreadsheet->disconnectWorksheets();
        } catch (Throwable $e) {
            Log::warning('File import anggota tidak dapat dibaca.', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'File Excel tidak dapat dibaca. Pastikan file berformat .xlsx dan menggunakan template anggota.');
        }

        $memberRows = $rows['data'] ?? [];
        if ($errors !== []) {
            return back()->with('import_errors', $errors);
        }
        if ($memberRows === []) {
            return back()->with('error', 'File Excel belum berisi data anggota.');
        }

        try {
            DB::transaction(function () use ($memberRows) {
                foreach ($memberRows as $row) {
                    Member::create($row);
                }
            });
        } catch (Throwable $e) {
            Log::error('Import anggota gagal disimpan.', [
                'rows' => count($memberRows),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Import gagal disimpan. Tidak ada anggota yang ditambahkan. Periksa NIS duplikat dan coba lagi.');
        }

        return redirect()
            ->route('members.index')
            ->with('success', count($memberRows) . ' calon nasabah berhasil diimport ke data Anggota.');
    }

    public function openingForm(string $id)
    {
        $member = Member::findOrFail($id);
        if (Customer::where('nis', $member->nis)->exists()) {
            return redirect()
                ->route('teller.members.index')
                ->with('error', 'Anggota ini sudah memiliki rekening.');
        }

        return view('members.activate', compact('member'));
    }

    public function openAccount(Request $request, string $id, CustomerAccountCreator $accountCreator)
    {
        $member = Member::findOrFail($id);
        $openingDeposit = $request->input('opening_deposit');
        if (is_string($openingDeposit)) {
            $request->merge([
                'opening_deposit' => preg_replace('/\D/', '', $openingDeposit),
            ]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:customers,email', 'unique:users,email'],
            'telephone_number' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'max:255', new StrongPassword],
            'pin' => ['required', 'digits:6'],
            'opening_deposit' => ['required', 'numeric', 'min:10000', 'max:9999999999.99'],
        ]);

        if (Customer::where('nis', $member->nis)->exists()) {
            return redirect()
                ->route('teller.members.index')
                ->with('error', 'Anggota ini sudah memiliki rekening.');
        }

        $qrPath = null;
        try {
            $openingResult = DB::transaction(function () use ($member, $validated, $accountCreator, &$qrPath) {
                \App\Models\User::whereKey(Auth::id())
                    ->lockForUpdate()
                    ->firstOrFail();

                $todayReport = DailyReport::where('teller_id', Auth::id())
                    ->whereDate('date', today())
                    ->lockForUpdate()
                    ->first();
                if ($todayReport && $todayReport->closed_at) {
                    throw new \RuntimeException('Kas harian sudah ditutup. Rekening baru tidak dapat dibuka hari ini.');
                }

                $lockedMember = Member::whereKey($member->id)->lockForUpdate()->firstOrFail();
                if (Customer::where('nis', $lockedMember->nis)->exists()) {
                    throw new \RuntimeException('Anggota ini sudah memiliki rekening.');
                }

                $lastAccountNumber = Customer::orderByDesc('id')
                    ->lockForUpdate()
                    ->value('account_number');
                $accountNumber = $lastAccountNumber
                    ? (string) ((int) $lastAccountNumber + 1)
                    : '10000001';

                [$customer, $qrPath] = $accountCreator->create([
                    'nis' => $lockedMember->nis,
                    'name' => $lockedMember->name,
                    'class' => $lockedMember->class,
                    'location' => $lockedMember->location,
                    ...$validated,
                ], $accountNumber);

                $amount = $validated['opening_deposit'];
                $customer->update(['balance' => $amount]);
                $customer->update(['status' => 'aktif']);

                $transaction = Transaction::create([
                    'customer_id' => $customer->id,
                    'teller_id' => Auth::id(),
                    'transaction_type' => 'setoran',
                    'amount' => $amount,
                    'beginning_balance' => 0,
                    'ending_balance' => $amount,
                    'date' => now(),
                ]);

                $report = $todayReport ?? DailyReport::firstOrCreate(
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
                $report->increment('total_deposit', $amount);
                $report->refresh();
                $report->ending_balance = $report->total_deposit - $report->total_withdrawals;
                $report->physical_money = $report->ending_balance;
                $report->difference = 0;
                if (!in_array($report->status, ['approved', 'rejected'], true)) {
                    $report->status = 'pending';
                }
                $report->save();

                AccountingJournal::create([
                    'transaction_id' => $transaction->id,
                    'account' => 'Kas',
                    'debit' => $amount,
                    'credit' => 0,
                    'date' => now(),
                ]);
                AccountingJournal::create([
                    'transaction_id' => $transaction->id,
                    'account' => 'Tabungan Nasabah',
                    'debit' => 0,
                    'credit' => $amount,
                    'date' => now(),
                ]);

                return [$customer, $transaction];
            });
        } catch (Throwable $e) {
            if ($qrPath && is_file($qrPath)) {
                unlink($qrPath);
            }

            if (!str_starts_with($e->getMessage(), 'Kas harian sudah ditutup.')) {
                Log::error('Pembuatan rekening dari anggota gagal.', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return back()
                ->withInput($request->except(['password', 'pin']))
                ->with('error', str_starts_with($e->getMessage(), 'Kas harian sudah ditutup.')
                    ? $e->getMessage()
                    : 'Pembukaan rekening gagal. Periksa data dan pastikan setoran awal minimal Rp 10.000.');
        }

        [, $transaction] = $openingResult;

        return redirect()->route('transactions.receipt', $transaction->id);
    }
}
