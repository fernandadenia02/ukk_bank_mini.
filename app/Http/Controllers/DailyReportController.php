<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use Illuminate\Support\Facades\Auth;

class DailyReportController extends Controller
{
    public function index()
    {
        $reports = DailyReport::with('teller')
                    ->orderBy('date','desc')
                    ->get();

        return view('daily_reports.index', compact('reports'));
    }

    public function approve($id)
    {
        if (session('role') != 'admin') {
            abort(403);
        }

        $report = DailyReport::findOrFail($id);

        $report->update([

            'status' => 'approved',

            'supervisor_id' => Auth::id(),

            'approved_at' => now(),

        ]);

        return back()->with('success','Daily Report approved.');
    }

    public function reject($id)
    {
        if (session('role') != 'admin') {
            abort(403);
        }

        $report = DailyReport::findOrFail($id);

        $report->update([

            'status' => 'rejected',

            'supervisor_id' => Auth::id(),

            'approved_at' => now(),

        ]);

        return back()->with('success','Daily Report rejected.');
    }
}