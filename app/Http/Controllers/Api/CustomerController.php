<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * =========================
     * AMBIL CUSTOMER LOGIN
     * =========================
     */
    private function getCustomer(Request $request)
    {
        return $request->user();
    }


    /**
     * =========================
     * DASHBOARD
     * =========================
     */
    public function dashboard(Request $request)
    {
        $customer = $this->getCustomer($request);

        if (!$customer) {
            return response()->json([
                'message' => 'Data customer tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'message' => 'Dashboard customer berhasil diambil.',
            'data' => [
                'name' => $customer->name,
                'account_number' => $customer->account_number,
                'balance' => (float) $customer->balance,
            ]
        ]);
    }


    /**
     * =========================
     * PROFILE
     * =========================
     */
    public function profile(Request $request)
    {
        $customer = $this->getCustomer($request);

        if (!$customer) {
            return response()->json([
                'message' => 'Data customer tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'message' => 'Profile berhasil diambil.',
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'account_number' => $customer->account_number,
                'nis' => $customer->nis,
                'class' => $customer->class,
                'location' => $customer->location,
                'telephone_number' => $customer->telephone_number,
                'email' => $customer->email,
                // Keep the previous aliases for existing API clients.
                'address' => $customer->location,
                'phone' => $customer->telephone_number,
                'balance' => (float) $customer->balance,
            ]
        ]);
    }


    /**
     * =========================
     * UPDATE PROFILE
     * =========================
     */
    public function updateProfile(Request $request)
    {
        $customer = $this->getCustomer($request);

        if (!$customer) {
            return response()->json([
                'message' => 'Data customer tidak ditemukan.'
            ], 404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'address' => [
                'nullable',
                'string',
                'max:500'
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20'
            ],
        ]);

        $customer->update([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? $customer->address,
            'phone' => $validated['phone'] ?? $customer->phone,
        ]);

        return response()->json([
            'message' => 'Profile berhasil diperbarui.',
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'account_number' => $customer->account_number,
                'nis' => $customer->nis,
                'address' => $customer->address,
                'phone' => $customer->phone,
                'balance' => (float) $customer->balance,
            ]
        ]);
    }


    /**
     * =========================
     * BALANCE
     * =========================
     */
    public function balance(Request $request)
    {
        $customer = $this->getCustomer($request);

        if (!$customer) {
            return response()->json([
                'message' => 'Data customer tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'message' => 'Saldo berhasil diambil.',
            'data' => [
                'account_number' => $customer->account_number,
                'balance' => (float) $customer->balance,
            ]
        ]);
    }


    /**
     * =========================
     * BARCODE
     * =========================
     */
    public function barcode(Request $request)
    {
        $customer = $this->getCustomer($request);

        if (!$customer) {
            return response()->json([
                'message' => 'Data customer tidak ditemukan.'
            ], 404);
        }

        $qrcode = $customer->qrcode;
        $qrcodeUrl = $qrcode
            ? url('qrcode/' . $qrcode)
            : null;
        $qrcodeDataUri = null;

        if (
            is_string($qrcode) &&
            basename($qrcode) === $qrcode &&
            strtolower(pathinfo($qrcode, PATHINFO_EXTENSION)) === 'svg'
        ) {
            $qrcodePath = public_path('qrcode/' . $qrcode);

            if (is_file($qrcodePath) && is_readable($qrcodePath)) {
                $qrcodeContents = file_get_contents($qrcodePath);

                if ($qrcodeContents !== false) {
                    $qrcodeDataUri = 'data:image/svg+xml;base64,' .
                        base64_encode($qrcodeContents);
                }
            }
        }

        return response()->json([
            'message' => 'Barcode customer berhasil diambil.',
            'data' => [
                'id' => $customer->id,
                'account_number' => $customer->account_number,
                'qrcode' => $qrcode,
                'qrcode_url' => $qrcodeUrl,
                'qrcode_data_uri' => $qrcodeDataUri,
            ]
        ]);
    }
}