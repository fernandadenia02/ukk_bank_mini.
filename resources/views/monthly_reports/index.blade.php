{{-- Rekap bulanan transaksi dan status laporan harian berdasarkan periode pilihan. --}}
@extends('layouts.app')

@section('title', 'Laporan Bulanan')

@section('content')

<h2>Laporan Bulanan</h2>
<p class="text-muted">Rekap transaksi Bank Mini berdasarkan bulan dan tahun.</p>

<hr>

<form method="GET" action="{{ route('monthly-reports.index') }}"
      class="card card-body shadow-sm mb-4">
    <div class="row align-items-end">
        <div class="col-md-4 mb-3">
            <label for="month" class="form-label">Bulan</label>
            <select name="month" id="month" class="form-select">
                @foreach([
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                    4 => 'April', 5 => 'Mei', 6 => 'Juni',
                    7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                    10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ] as $number => $name)
                    <option value="{{ $number }}"
                        @selected($month == $number)>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4 mb-3">
            <label for="year" class="form-label">Tahun</label>
            <input type="number" name="year" id="year"
                   class="form-control"
                   min="2000" max="2100" value="{{ $year }}" required>
        </div>

        <div class="col-md-4 mb-3">
            <button type="submit" class="btn btn-primary">
                Tampilkan Laporan
            </button>
            <button type="button" onclick="window.print()"
                    class="btn btn-secondary">
                Cetak
            </button>
        </div>
    </div>
</form>

@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<h5 class="mb-3">
    Periode:
    {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}
</h5>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted">Jumlah Transaksi</div>
                <h4>{{ number_format($summary->total_transactions ?? 0, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted">Total Setoran</div>
                <h5 class="text-success">
                    Rp {{ number_format($summary->total_deposit ?? 0, 0, ',', '.') }}
                </h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted">Total Penarikan</div>
                <h5 class="text-danger">
                    Rp {{ number_format($summary->total_withdrawals ?? 0, 0, ',', '.') }}
                </h5>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted">Selisih Setoran - Penarikan</div>
                <h5>
                    Rp {{ number_format(
                        ($summary->total_deposit ?? 0) -
                        ($summary->total_withdrawals ?? 0),
                        0, ',', '.'
                    ) }}
                </h5>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <strong>Status Daily Report</strong>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-2">
                Approved:
                <span class="badge bg-success">
                    {{ $reportStatuses['approved'] ?? 0 }}
                </span>
            </div>

            <div class="col-md-4 mb-2">
                Pending:
                <span class="badge bg-danger text-white">
                    {{ $reportStatuses['pending'] ?? 0 }}
                </span>
            </div>

            <div class="col-md-4 mb-2">
                Rejected:
                <span class="badge bg-danger">
                    {{ $reportStatuses['rejected'] ?? 0 }}
                </span>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <strong>Rincian Transaksi Bulanan</strong>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Nasabah</th>
                        <th>Teller</th>
                        <th>Jenis Transaksi</th>
                        <th>Nominal</th>
                        <th>Saldo Awal</th>
                        <th>Saldo Akhir</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                {{ $transaction->date
                                    ? \Carbon\Carbon::parse($transaction->date)->timezone('Asia/Jakarta')->format('d-m-Y H:i') . ' WIB'
                                    : '-' }}
                            </td>
                            <td>{{ $transaction->customer->name ?? '-' }}</td>
                            <td>{{ $transaction->teller->officer_name ?? '-' }}</td>
                            <td>
                                @if($transaction->transaction_type === 'setoran')
                                    <span class="badge bg-success">Setoran</span>
                                @elseif($transaction->transaction_type === 'penarikan')
                                    <span class="badge bg-danger">Penarikan</span>
                                @else
                                    {{ $transaction->transaction_type }}
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
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">
                                Tidak ada transaksi pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* Hide navigation and controls and simplify cards when printing the monthly report. */
    @media print {
        nav, aside, .sidebar, .btn, form {
            display: none !important;
        }

        body {
            background: white !important;
        }

        .card {
            box-shadow: none !important;
            break-inside: avoid;
        }
    }
</style>

@endsection
