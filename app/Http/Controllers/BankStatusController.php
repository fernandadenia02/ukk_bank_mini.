<?php

namespace App\Http\Controllers;

use App\Models\BankStatus;
use Illuminate\Http\Request;

class BankStatusController extends Controller
{
    public function show()
    {
        $isOpen = BankStatus::current()->is_open;

        return response()->json([
            'message' => 'Status bank berhasil diambil.',
            'data' => [
                'is_open' => $isOpen,
                'status' => $isOpen ? 'open' : 'closed',
                'label' => $isOpen ? 'Bank Buka' : 'Bank Tutup',
                'checked_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'is_open' => ['required', 'boolean'],
        ]);

        BankStatus::current()->update([
            'is_open' => $validated['is_open'],
        ]);

        return back()->with(
            'success',
            $validated['is_open']
                ? 'Status bank berhasil diubah menjadi buka.'
                : 'Bank sedang istirahat dan ditutup sementara.'
        );
    }
}
