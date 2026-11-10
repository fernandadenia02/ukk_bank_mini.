{{-- Menampilkan jurnal akuntansi dengan filter akun dan rentang tanggal. --}}
@extends('layouts.app')

@section('title','Accounting Journal')

@section('content')

<h2>Accounting Journal</h2>

<hr>

<form method="GET" action="{{ route('accounting-journal.index') }}" class="row g-2 align-items-end mb-3">
    <div class="col-md-4">
        <label for="keyword" class="form-label">Cari akun</label>
        <input
            type="text"
            id="keyword"
            name="keyword"
            value="{{ $keyword }}"
            class="form-control"
            placeholder="Search account..."
        >
    </div>
    <div class="col-md-3">
        <label for="date_from" class="form-label">Dari tanggal</label>
        <input
            type="date"
            id="date_from"
            name="date_from"
            value="{{ $dateFrom }}"
            class="form-control"
        >
    </div>
    <div class="col-md-3">
        <label for="date_to" class="form-label">Sampai tanggal</label>
        <input
            type="date"
            id="date_to"
            name="date_to"
            value="{{ $dateTo }}"
            class="form-control"
        >
    </div>
    <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="{{ route('accounting-journal.index') }}" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

<form method="GET" action="{{ route('accounting-journal.pdf') }}" class="card card-body mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label for="print_period" class="form-label">Periode cetak</label>
            <select name="period" id="print_period" class="form-select">
                <option value="daily">Harian</option>
                <option value="weekly">Mingguan</option>
                <option value="monthly" selected>Bulanan</option>
                <option value="yearly">Tahunan</option>
                <option value="custom">Rentang tanggal</option>
            </select>
        </div>
        <div class="col-md-3" id="print_date_group">
            <label for="print_date" class="form-label">Tanggal acuan</label>
            <input type="date" name="date" id="print_date" class="form-control"
                   value="{{ now()->toDateString() }}">
        </div>
        <div class="col-md-3 d-none" id="print_date_from_group">
            <label for="print_date_from" class="form-label">Dari tanggal</label>
            <input type="date" name="date_from" id="print_date_from" class="form-control">
        </div>
        <div class="col-md-3 d-none" id="print_date_to_group">
            <label for="print_date_to" class="form-label">Sampai tanggal</label>
            <input type="date" name="date_to" id="print_date_to" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="print_keyword" class="form-label">Filter akun (opsional)</label>
            <input type="text" name="keyword" id="print_keyword" class="form-control"
                   value="{{ $keyword }}" placeholder="Semua akun">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-success">Cetak PDF</button>
        </div>
    </div>
</form>

<table class="table table-bordered table-striped">

    <thead class="table-dark">

        <tr>

            <th>No</th>

            <th>Date</th>

            <th>Transaction</th>

            <th>Customer</th>

            <th>Account</th>

            <th>Debit</th>

            <th>Credit</th>

        </tr>

    </thead>

    <tbody>

    @forelse($journals as $journal)

    <tr>

        <td>{{ $loop->iteration }}</td>

        <td>{{ \Illuminate\Support\Carbon::parse($journal->date)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') }} WIB</td>

        <td>#{{ $journal->transaction_id }}</td>

        <td>{{ $journal->transaction->customer->name ?? '-' }}</td>

        <td>{{ $journal->account }}</td>

        <td>
            Rp {{ number_format($journal->debit,0,',','.') }}
        </td>

        <td>
            Rp {{ number_format($journal->credit,0,',','.') }}
        </td>

    </tr>

    @empty

    <tr>

        <td colspan="7" class="text-center">

            No Journal Found

        </td>

    </tr>

    @endforelse

    </tbody>

</table>

<div class="mt-3">

    <h5>Total Debit :
        Rp {{ number_format($totalDebit,0,',','.') }}
    </h5>

    <h5>Total Credit :
        Rp {{ number_format($totalCredit,0,',','.') }}
    </h5>

</div>

{{ $journals->links() }}

<script>
    const printPeriod = document.getElementById('print_period');
    const printDateGroup = document.getElementById('print_date_group');
    const printDateFromGroup = document.getElementById('print_date_from_group');
    const printDateToGroup = document.getElementById('print_date_to_group');
    const printDate = document.getElementById('print_date');
    const printDateFrom = document.getElementById('print_date_from');
    const printDateTo = document.getElementById('print_date_to');

    function updatePrintPeriodFields() {
        const isCustom = printPeriod.value === 'custom';
        printDateGroup.classList.toggle('d-none', isCustom);
        printDateFromGroup.classList.toggle('d-none', !isCustom);
        printDateToGroup.classList.toggle('d-none', !isCustom);
        printDate.required = !isCustom;
        printDateFrom.required = isCustom;
        printDateTo.required = isCustom;
    }

    printPeriod.addEventListener('change', updatePrintPeriodFields);
    updatePrintPeriodFields();
</script>

@endsection