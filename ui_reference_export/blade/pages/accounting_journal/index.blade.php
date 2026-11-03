@extends('layouts.app')

@section('title','Accounting Journal')

@section('content')

<h2>Accounting Journal</h2>

<hr>

<form method="GET" class="mb-3">

    <input type="text"
           name="keyword"
           value="{{ $keyword }}"
           class="form-control"
           placeholder="Search account...">

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

        <td>{{ $journal->date }}</td>

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