<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerAccountCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $customers = Customer::where('status', 'aktif')
            ->when($keyword, function ($query) use ($keyword) {

                $query->where(function ($query) use ($keyword) {
                    $query->where('account_number', 'like', "%{$keyword}%")
                        ->orWhere('nis', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('class', 'like', "%{$keyword}%");
                });

            })
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'active_page')
            ->withQueryString();

        $inactiveCustomers = Customer::where('status', 'nonaktif')
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('account_number', 'like', "%{$keyword}%")
                        ->orWhere('nis', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('class', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'inactive_page')
            ->withQueryString();

        return view('customers.index', compact(
            'customers',
            'inactiveCustomers',
            'keyword'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $lastCustomer = Customer::orderBy('id', 'desc')->first();

        if ($lastCustomer) {
            $accountNumber = $lastCustomer->account_number + 1;
        } else {
            $accountNumber = 10000001;
        }

        return view(
            'customers.create',
            compact('accountNumber')
        );
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_number'   => 'required|unique:customers,account_number',
            'nis'              => 'required|unique:customers,nis',
            'name'             => 'required|max:100',
            'email'            => 'required|email|max:255|unique:customers,email|unique:users,email',
            'class'            => 'required|max:20',
            'location'         => 'required',
            'telephone_number' => 'required|max:20',
            'password'         => 'required|string|min:4',
            'pin'              => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except(['password', 'pin']));
        }

        DB::beginTransaction();

        try {

            [$customer] = $this->createCustomerAccount(
                $validator->validated(),
                (string) $request->account_number
            );

            DB::commit();

            // ==========================================
            // BERHASIL
            // ==========================================

            return redirect()
                ->route('customers.index')
                ->with(
                    'success',
                    'Customer berhasil dibuat. ' .
                    'Username: ' . $customer->username
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput($request->except(['password', 'pin']))
                ->with(
                    'error',
                    'Gagal membuat customer: ' .
                    $e->getMessage()
                );
        }
    }

    public function downloadImportTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customer');

        $headers = [
            'nis',
            'name',
            'email',
            'class',
            'location',
            'telephone_number',
            'password',
            'pin',
        ];

        foreach ($headers as $column => $header) {
            $columnLetter = Coordinate::stringFromColumnIndex($column + 1);
            $sheet->setCellValue($columnLetter . '1', $header);
            $sheet->getColumnDimension($columnLetter)->setWidth(24);
            $sheet->getStyle($columnLetter . '2:' . $columnLetter . '5001')
                ->getNumberFormat()
                ->setFormatCode('@');
        }

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk');
        $guide->fromArray([
            ['Panduan Import Customer'],
            ['Isi satu customer per baris pada sheet Customer, mulai dari baris 2.'],
            ['Jangan ubah atau menghapus nama kolom pada baris pertama.'],
            ['Nomor rekening dibuat otomatis oleh sistem.'],
            ['Customer baru berstatus nonaktif dengan saldo Rp 0.'],
            ['Password kosong otomatis diisi 1234.'],
            ['PIN kosong otomatis diisi 123456. PIN wajib 6 angka jika diisi.'],
            ['NIS, name, email, class, location, dan telephone_number wajib diisi.'],
            ['Jika ada data salah atau duplikat, seluruh file dibatalkan dan tidak ada baris yang disimpan.'],
            ['Simpan password dan PIN dengan aman, dan anjurkan customer untuk mengubahnya setelah login.'],
        ], null, 'A1');
        $guide->getColumnDimension('A')->setWidth(110);

        $filename = 'template-import-customer.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        $expectedHeaders = [
            'nis',
            'name',
            'email',
            'class',
            'location',
            'telephone_number',
            'password',
            'pin',
        ];
        $rows = [];
        $errors = [];

        try {
            $spreadsheet = IOFactory::load($validated['file']->getRealPath());
            $sheet = $spreadsheet->getSheetByName('Customer') ?? $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();
            $headers = [];

            for ($column = 1; $column <= count($expectedHeaders); $column++) {
                $headers[] = strtolower(trim((string) $sheet->getCell([$column, 1])->getValue()));
            }

            if ($headers !== $expectedHeaders) {
                return back()->with('error', 'Format kolom Excel tidak sesuai. Unduh dan gunakan template import customer.');
            }

            if ($highestRow > 5001) {
                return back()->with('error', 'Import maksimal 5.000 baris customer per file.');
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

                $row['password'] = $row['password'] ?? '1234';
                $row['pin'] = $row['pin'] ?? '123456';
                $row['nis'] = trim((string) ($row['nis'] ?? ''));
                $row['email'] = strtolower(trim((string) ($row['email'] ?? '')));

                $validator = Validator::make($row, [
                    'nis' => ['required', 'string', 'max:30', 'unique:customers,nis'],
                    'name' => ['required', 'string', 'max:100'],
                    'email' => ['required', 'email', 'max:255', 'unique:customers,email', 'unique:users,email'],
                    'class' => ['required', 'string', 'max:20'],
                    'location' => ['required', 'string'],
                    'telephone_number' => ['required', 'string', 'max:20'],
                    'password' => ['required', 'string', 'min:4', 'max:255'],
                    'pin' => ['required', 'digits:6'],
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->all() as $error) {
                        $errors[] = "Baris {$rowNumber}: {$error}";
                    }

                    continue;
                }

                foreach (['nis', 'email'] as $uniqueField) {
                    if (isset($rows[$uniqueField][$row[$uniqueField]])) {
                        $errors[] = "Baris {$rowNumber}: {$uniqueField} duplikat dengan baris {$rows[$uniqueField][$row[$uniqueField]]}.";
                    } else {
                        $rows[$uniqueField][$row[$uniqueField]] = $rowNumber;
                    }
                }

                $rows['data'][] = $row;
            }

            $spreadsheet->disconnectWorksheets();
        } catch (Throwable $e) {
            Log::warning('File import customer tidak dapat dibaca.', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'File Excel tidak dapat dibaca. Pastikan file berformat .xlsx dan menggunakan template yang disediakan.');
        }

        $customerRows = $rows['data'] ?? [];

        if ($errors !== []) {
            return back()->with('import_errors', $errors);
        }

        if ($customerRows === []) {
            return back()->with('error', 'File Excel belum berisi data customer.');
        }

        $generatedQrFiles = [];

        try {
            DB::transaction(function () use ($customerRows, &$generatedQrFiles) {
                $lastAccountNumber = Customer::orderByDesc('id')
                    ->lockForUpdate()
                    ->value('account_number');
                $nextAccountNumber = $lastAccountNumber
                    ? (int) $lastAccountNumber + 1
                    : 10000001;

                foreach ($customerRows as $row) {
                    [$customer, $qrPath] = $this->createCustomerAccount(
                        $row,
                        (string) $nextAccountNumber
                    );
                    $generatedQrFiles[] = $qrPath;
                    $nextAccountNumber++;
                }
            });
        } catch (Throwable $e) {
            foreach ($generatedQrFiles as $qrPath) {
                if (is_file($qrPath)) {
                    unlink($qrPath);
                }
            }

            Log::error('Import customer gagal disimpan.', [
                'rows' => count($customerRows),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Import gagal disimpan. Tidak ada customer yang ditambahkan. Periksa email/NIS duplikat dan coba lagi.');
        }

        return redirect()
            ->route('customers.index')
            ->with('success', count($customerRows) . ' customer berhasil diimport. Nomor rekening dibuat otomatis dan seluruh akun berstatus nonaktif dengan saldo Rp 0.');
    }

    private function createCustomerAccount(array $data, string $accountNumber): array
    {
        return app(CustomerAccountCreator::class)->create($data, $accountNumber);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $customer = Customer::findOrFail($id);

        return view(
            'customers.show',
            compact('customer')
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $customer = Customer::findOrFail($id);

        return view(
            'customers.edit',
            compact('customer')
        );
    }

    /**
     * Update the specified resource.
     */
    public function update(
        Request $request,
        string $id
    ) {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'nis' => 'required|unique:customers,nis,' . $customer->id,
            'name' => 'required|max:100',
            'email' => 'required|email|max:255|unique:customers,email,' . $customer->id
                . '|unique:users,email',
            'class' => 'required|max:20',
            'location' => 'required',
            'telephone_number' => 'required|max:20',
        ]);

        $customer->update([

            'nis' => $validated['nis'],
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'class' => $validated['class'],
            'location' => $validated['location'],
            'telephone_number' => $validated['telephone_number'],

        ]);

        return redirect()
            ->route('customers.index')
            ->with(
                'success',
                'Customer updated successfully.'
            );
    }

    public function updateStatus(Request $request, string $id)
    {
        $customer = Customer::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:aktif,nonaktif',
        ]);
        if ($validated['status'] === 'aktif' && (float) $customer->balance < 10000) {
            return redirect()
                ->route('customers.index')
                ->with('error', 'Customer hanya dapat diaktifkan jika saldo minimal Rp 10.000.');
        }

        $customer->update(['status' => $validated['status']]);

        if ($validated['status'] === 'nonaktif') {
            $customer->tokens()->delete();
        }

        return redirect()
            ->route('customers.index')
            ->with('success', 'Status customer berhasil diperbarui.');
    }
}