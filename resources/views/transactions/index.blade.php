@extends('layouts.app')

@section('title', 'Transactions')

@section('content')

<h2>Transaction Data</h2>

<hr>

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<a href="{{ route('transactions.create') }}"
   class="btn btn-primary mb-3">

    New Transaction

</a>

<table class="table table-bordered table-hover">

    <thead class="table-dark">

        <tr>

            <th>No</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Account Number</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Beginning Balance</th>
            <th>Ending Balance</th>
            <th>Teller</th>

        </tr>

    </thead>

    <tbody>

    @forelse($transactions as $transaction)

        <tr>

            <td>{{ ($transactions->currentPage()-1) * $transactions->perPage() + $loop->iteration }}</td>

            <td>{{ $transaction->date }}</td>

            <td>{{ $transaction->customer->name ?? '-' }}</td>

            <td>{{ $transaction->customer->account_number ?? '-' }}</td>

            <td>

                @if($transaction->transaction_type == 'setoran')

                    <span class="badge bg-success">
                        Deposit
                    </span>

                @else

                    <span class="badge bg-danger">
                        Withdrawal
                    </span>

                @endif

            </td>

            <td>

                Rp {{ number_format($transaction->amount,0,',','.') }}

            </td>

            <td>

                Rp {{ number_format($transaction->beginning_balance,0,',','.') }}

            </td>

            <td>

                Rp {{ number_format($transaction->ending_balance,0,',','.') }}

            </td>

            <td>

                {{ $transaction->teller->officer_name ?? '-' }}

            </td>

        </tr>

    @empty

        <tr>

            <td colspan="9" class="text-center">

                No transaction data.

            </td>

        </tr>

    @endforelse

    </tbody>

</table>

{{ $transactions->links() }}

@endsection