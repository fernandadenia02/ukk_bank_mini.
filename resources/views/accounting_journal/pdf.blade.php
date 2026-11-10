<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Accounting Journal</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { margin: 0 0 8px; font-size: 18px; text-align: center; }
        .period { margin-bottom: 16px; text-align: center; }
        .filter { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 6px; }
        th { background: #e9ecef; text-align: left; }
        .number { text-align: right; white-space: nowrap; }
        tfoot th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Accounting Journal</h1>
    <div class="period">
        Periode {{ $periodLabel }}:
        {{ $startDate->format('d-m-Y') }}
        @if($startDate->toDateString() !== $endDate->toDateString())
            s.d. {{ $endDate->format('d-m-Y') }}
        @endif
    </div>
    @if($keyword !== '')
        <div class="filter">Filter akun: {{ $keyword }}</div>
    @endif

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Transaksi</th>
                <th>Nasabah</th>
                <th>Akun</th>
                <th class="number">Debit</th>
                <th class="number">Kredit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($journals as $journal)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($journal->date)->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }} WIB</td>
                    <td>#{{ $journal->transaction_id ?? '-' }}</td>
                    <td>{{ $journal->transaction->customer->name ?? '-' }}</td>
                    <td>{{ $journal->account }}</td>
                    <td class="number">Rp {{ number_format($journal->debit, 0, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format($journal->credit, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center">Tidak ada jurnal pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5">Total</th>
                <th class="number">Rp {{ number_format($totalDebit, 0, ',', '.') }}</th>
                <th class="number">Rp {{ number_format($totalCredit, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
