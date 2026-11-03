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

@endsection