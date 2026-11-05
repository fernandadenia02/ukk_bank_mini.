<?php

namespace App\Http\Controllers;

use App\Models\BankStatus;
use Illuminate\Http\Request;

class BankStatusController extends Controller
{
    public function show()
    {
        return response()->json([
            'message' => 'Status bank berhasil diambil.',
            'data' => [
                'is_open' => BankStatus::current()->is_open,
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
