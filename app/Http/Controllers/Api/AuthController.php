<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * =========================================================
     * LOGIN CUSTOMER
     * POST /api/customer/login
     * =========================================================
     */
    public function login(Request $request)
    {
        // =========================
        // VALIDASI
        // =========================

        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);


        // =========================
        // CARI USER BERDASARKAN USERNAME
        // =========================

        $customer = Customer::where('username', $request->username)->first();


        // =========================
        // CEK USER & PASSWORD
        // =========================

        if (
            !$customer ||
            $customer->status !== 'aktif' ||
            !$customer->password ||
            !Hash::check(
                $request->password,
                $customer->password
            )
        ) {

            return response()->json([
                'success' => false,
                'message' => 'Username atau password salah.'
            ], 401);
        }


        // =========================
        // CARI DATA CUSTOMER
        // =========================


        // =========================
        // HAPUS TOKEN LAMA
        // =========================

        $customer->tokens()->delete();


        // =========================
        // BUAT TOKEN BARU
        // =========================

        $token = $customer
            ->createToken('customer-mobile')
            ->plainTextToken;


        // =========================
        // RESPONSE
        // =========================

        return response()->json([

            'success' => true,

            'message' => 'Login berhasil.',

            'token' => $token,

            'token_type' => 'Bearer',

            'user' => [
                'id' => $customer->id,
                'username' => $customer->username,
                'name' => $customer->name,
                'role' => 'customer',
            ],

            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'account_number' => $customer->account_number,
                'balance' => $customer->balance,
            ],
        ]);
    }


    /**
     * =========================================================
     * DATA CUSTOMER YANG SEDANG LOGIN
     * GET /api/customer/me
     * =========================================================
     */
    public function me(Request $request)
    {
        $user = $request->user();


        // =========================
        // CEK USER
        // =========================

        if (!$user) {

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }


        // =========================
        // CARI CUSTOMER
        // =========================

        $customer = $user;


        if (!$customer) {

            return response()->json([
                'success' => false,
                'message' => 'Data customer tidak ditemukan.'
            ], 404);
        }


        return response()->json([

            'success' => true,

            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'role' => 'customer',
            ],

            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'account_number' => $customer->account_number,
                'balance' => $customer->balance,
            ],
        ]);
    }


    /**
     * =========================================================
     * LOGOUT CUSTOMER
     * POST /api/customer/logout
     * =========================================================
     */
    public function logout(Request $request)
    {
        $user = $request->user();


        if (!$user) {

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }


        // Hapus token yang sedang digunakan
        $request->user()
            ->currentAccessToken()
            ->delete();


        return response()->json([

            'success' => true,

            'message' => 'Logout berhasil.'

        ]);
    }
}