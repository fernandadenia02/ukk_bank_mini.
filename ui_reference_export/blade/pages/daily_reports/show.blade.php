
@extends('layouts.app')

@section('title', 'Detail Daily Report')

@section('content')

@php
    $role = auth()->user()->role ?? '';
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2>Detail Daily Report</h2>
        <p class="text-muted mb-0">Laporan aktivitas teller</p>
    </div>

    @if($role === 'teller')
        <a href="{{ route('teller.dashboard') }}" class="btn btn-secondary">
            Kembali
        </a>
    @else
        <a href="{{ route('daily-reports.index') }}" class="btn btn-secondary">
            Kembali
        </a>
    @endif
</div>

<hr>

{{-- NOTIFIKASI --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"
            aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"
            aria-label="Close"></button>
    </div>
@endif

{{-- INFORMASI LAPORAN --}}
<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
        <strong>Informasi Daily Report</strong>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <strong>Tanggal</strong>
                <div>
                    {{ $report->date }}
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <strong>Teller</strong>
                <div>
                    {{ $report->teller->officer_name ?? '-' }}
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <strong>Status</strong>
                <div>
                    @if($report->status === 'pending')
                        <span class="badge bg-warning text-dark">Pending</span>
                    @elseif($report->status === 'approved')
                        <span class="badge bg-success">Approved</span>
                    @elseif($report->status === 'rejected')
                        <span class="badge bg-danger">Rejected</span>
                    @else
                        <span class="badge bg-secondary">
                            {{ $report->status }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <strong>Status Kas</strong>
                <div>
                    @if($report->closed_at)
                        <span class="badge bg-secondary">Ditutup {{ $report->closed_at->format('d-m-Y H:i') }}</span>
                    @else
                        <span class="badge bg-warning text-dark">Belum ditutup teller</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ALASAN PENOLAKAN --}}
@if($report->status === 'rejected')
    <div class="card shadow mb-4 border-danger">
        <div class="card-header bg-danger text-white">
            <strong>
                <i class="bi bi-exclamation-circle me-1"></i>
                Alasan Penolakan Daily Report
            </strong>
        </div>

        <div class="card-body">
            @if(filled($report->rejection_reason))
                <div class="alert alert-danger mb-0" style="white-space: pre-line;">{{ $report->rejection_reason }}</div>
            @else
                <div class="alert alert-warning mb-0">
                    Alasan penolakan belum tersedia pada laporan ini.
                </div>
            @endif
        </div>
    </div>
@endif

{{-- RINGKASAN --}}
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card shadow h-100">
            <div class="card-body text-center">
                <h6 class="text-muted">Total Deposit</h6>
                <h4 class="text-success">
                    Rp {{ number_format($report->total_deposit, 0, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow h-100">
            <div class="card-body text-center">
                <h6 class="text-muted">Total Withdrawal</h6>
                <h4 class="text-danger">
                    Rp {{ number_format($report->total_withdrawals, 0, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow h-100">
            <div class="card-body text-center">
                <h6 class="text-muted">Ending Balance</h6>
                <h4>
                    Rp {{ number_format($report->ending_balance, 0, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow h-100">
            <div class="card-body text-center">
                <h6 class="text-muted">Selisih</h6>
                <h4 class="{{ $report->difference == 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($report->difference, 0, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>
</div>

{{-- INFORMASI KAS --}}
<div class="card shadow mb-4">
    <div class="card-header bg-dark text-white">
        <strong>Rekap Kas</strong>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <strong>Beginning Balance</strong>
                <div>
                    Rp {{ number_format($report->beginning_balance, 0, ',', '.') }}
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <strong>Physical Money</strong>
                <div>
                    Rp {{ number_format($report->physical_money, 0, ',', '.') }}
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <strong>Difference</strong>
                <div>
                    @if($report->difference == 0)
                        <span class="badge bg-success">Balance</span>
                    @else
                        <span class="badge bg-danger">
                            Selisih Rp {{ number_format(abs($report->difference), 0, ',', '.') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DAFTAR TRANSAKSI --}}
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <strong>Transaksi Teller</strong>
    </div>

    <div class="card-body">
        @if($transactions->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Waktu</th>
                            <th>Customer</th>
                            <th>Rekening</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                            <th>Saldo Awal</th>
                            <th>Saldo Akhir</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($transactions as $transaction)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>
                                    {{ $transaction->date
                                        ? \Carbon\Carbon::parse($transaction->date)->format('d-m-Y H:i')
                                        : '-' }}
                                </td>

                                <td>
                                    {{ $transaction->customer->name ?? '-' }}
                                </td>

                                <td>
                                    {{ $transaction->customer->account_number ?? '-' }}
                                </td>

                                <td>
                                    @if($transaction->transaction_type === 'setoran')
                                        <span class="badge bg-success">Deposit</span>
                                    @elseif($transaction->transaction_type === 'penarikan')
                                        <span class="badge bg-danger">Withdrawal</span>
                                    @else
                                        <span class="badge bg-secondary">
                                            {{ $transaction->transaction_type }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                                </td>

                                <td>
                                    Rp {{ number_format($transaction->beginning_balance, 0, ',', '.') }}
                                </td>

                                <td>
                                    Rp {{ number_format($transaction->ending_balance, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info mb-0">
                Belum ada transaksi pada laporan ini.
            </div>
        @endif
    </div>
</div>

@endsection
