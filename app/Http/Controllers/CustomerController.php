<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $customers = Customer::when($keyword, function ($query) use ($keyword) {

            $query->where('account_number', 'like', "%{$keyword}%")
                ->orWhere('nis', 'like', "%{$keyword}%")
                ->orWhere('name', 'like', "%{$keyword}%")
                ->orWhere('class', 'like', "%{$keyword}%");

        })
        ->orderBy('id', 'desc')
        ->paginate(10)
        ->withQueryString();

        return view('customers.index', compact(
            'customers',
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
        $request->validate([
            'account_number'   => 'required|unique:customers,account_number',
            'nis'              => 'required|unique:customers,nis',
            'name'             => 'required|max:100',
            'class'            => 'required|max:20',
            'location'         => 'required',
            'telephone_number' => 'required|max:20',
            'pin'              => 'required|digits:6',
        ]);

        DB::beginTransaction();

        try {

            // ==========================================
            // BUAT USER UNTUK LOGIN CUSTOMER
            // ==========================================

            $username = strtolower(trim($request->name));

            // Cek username agar tidak duplikat
            if (User::where('username', $username)->exists()) {

                $username = $username . rand(10, 99);

            }

            $user = User::create([
                'officer_name' => $request->name,
                'username'     => $username,
                'password'     => Hash::make($request->pin),
                'role'         => 'customer',
                'status'       => 'aktif',
            ]);

            // ==========================================
            // BUAT DATA CUSTOMER
            // ==========================================

            $customer = Customer::create([

                'user_id'          => $user->id,
                'account_number'   => $request->account_number,
                'nis'              => $request->nis,
                'name'             => $request->name,
                'class'            => $request->class,
                'location'         => $request->location,
                'telephone_number' => $request->telephone_number,
                'pin'              => Hash::make($request->pin),
                'balance'          => 10000,

            ]);

            // ==========================================
            // BUAT FOLDER QR CODE
            // ==========================================

            if (!file_exists(public_path('qrcode'))) {

                mkdir(
                    public_path('qrcode'),
                    0777,
                    true
                );

            }

            // ==========================================
            // GENERATE QR CODE
            // ==========================================

            $fileName = $customer->account_number . '.svg';

            file_put_contents(
                public_path('qrcode/' . $fileName),
                QrCode::size(300)->generate(
                    $customer->account_number
                )
            );

            // ==========================================
            // SIMPAN NAMA FILE QR CODE
            // ==========================================

            $customer->update([
                'qrcode' => $fileName,
            ]);

            DB::commit();

            // ==========================================
            // BERHASIL
            // ==========================================

            return redirect()
                ->route('customers.index')
                ->with(
                    'success',
                    'Customer berhasil dibuat. ' .
                    'Username: ' . $user->username .
                    ' | Password: PIN yang dimasukkan.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal membuat customer: ' .
                    $e->getMessage()
                );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $customer = Customer::with('user')
            ->findOrFail($id);

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

        $request->validate([
            'nis' => 'required|unique:customers,nis,' . $customer->id,
            'name' => 'required|max:100',
            'class' => 'required|max:20',
            'location' => 'required',
            'telephone_number' => 'required|max:20',
        ]);

        $customer->update([

            'nis' => $request->nis,
            'name' => $request->name,
            'class' => $request->class,
            'location' => $request->location,
            'telephone_number' => $request->telephone_number,

        ]);

        // ==========================================
        // UPDATE NAMA USER
        // ==========================================

        if ($customer->user_id) {

            User::where('id', $customer->user_id)
                ->update([
                    'officer_name' => $request->name,
                ]);

        }

        return redirect()
            ->route('customers.index')
            ->with(
                'success',
                'Customer updated successfully.'
            );
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(string $id)
    {
        $customer = Customer::findOrFail($id);

        // Simpan user_id
        $userId = $customer->user_id;

        // ==========================================
        // HAPUS QR CODE
        // ==========================================

        if (
            $customer->qrcode &&
            file_exists(
                public_path(
                    'qrcode/' . $customer->qrcode
                )
            )
        ) {

            unlink(
                public_path(
                    'qrcode/' . $customer->qrcode
                )
            );

        }

        // ==========================================
        // HAPUS CUSTOMER
        // ==========================================

        $customer->delete();

        // ==========================================
        // HAPUS AKUN LOGIN CUSTOMER
        // ==========================================

        if ($userId) {

            User::where('id', $userId)
                ->where('role', 'customer')
                ->delete();

        }

        return redirect()
            ->route('customers.index')
            ->with(
                'success',
                'Customer dan akun login berhasil dihapus.'
            );
    }
}