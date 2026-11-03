@extends('layouts.app')

@section('title', 'Customer Dashboard')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Customer Dashboard</h2>
            <p class="text-muted mb-0">
                Welcome, {{ $customer->name }}
            </p>
        </div>
    </div>

    {{-- CUSTOMER INFORMATION --}}
    <div class="row mb-4">

        <div class="col-md-4 mb-3">
            <div class="card shadow h-100">
                <div class="card-body">
                    <h6 class="text-muted">Account Number</h6>

                    <h4>
                        {{ $customer->account_number }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card shadow h-100">
                <div class="card-body">
                    <h6 class="text-muted">Customer Name</h6>

                    <h4>
                        {{ $customer->name }}
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card shadow h-100">
                <div class="card-body">
                    <h6 class="text-muted">Balance</h6>

                    <h4 class="text-success">
                        Rp {{ number_format($customer->balance, 0, ',', '.') }}
                    </h4>
                </div>
            </div>
        </div>

    </div>

    {{-- CUSTOMER DETAILS --}}
    <div class="card shadow mb-4">

        <div class="card-header bg-primary text-white">
            <strong>Customer Information</strong>
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-6 mb-3">
                    <strong>NIS</strong>
                    <br>
                    {{ $customer->nis }}
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Class</strong>
                    <br>
                    {{ $customer->class }}
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Location</strong>
                    <br>
                    {{ $customer->location }}
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Telephone Number</strong>
                    <br>
                    {{ $customer->telephone_number }}
                </div>

            </div>

        </div>

    </div>

    {{-- TRANSACTION HISTORY --}}
    <div class="card shadow">

        <div class="card-header bg-dark text-white">
            <strong>Transaction History</strong>
        </div>

        <div class="card-body">

            @if($transactions->count() > 0)

                <div class="table-responsive">

                    <table class="table table-bordered table-hover">

                        <thead class="table-dark">

                            <tr>
                                <th>No</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>

                        </thead>

                        <tbody>

                            @foreach($transactions as $transaction)

                                <tr>

                                    <td>
                                        {{ $loop->iteration }}
                                    </td>

                                    <td>
                                        {{ $transaction->date }}
                                    </td>

                                    <td>
                                        {{ ucfirst($transaction->type ?? '-') }}
                                    </td>

                                    <td>
                                        Rp {{ number_format($transaction->amount ?? 0, 0, ',', '.') }}
                                    </td>

                                    <td>

                                        @if(($transaction->status ?? '') == 'approved')

                                            <span class="badge bg-success">
                                                Approved
                                            </span>

                                        @elseif(($transaction->status ?? '') == 'pending')

                                            <span class="badge bg-warning text-dark">
                                                Pending
                                            </span>

                                        @else

                                            <span class="badge bg-secondary">
                                                {{ ucfirst($transaction->status ?? '-') }}
                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <div class="alert alert-info mb-0">
                    No transactions found.
                </div>

            @endif

        </div>

    </div>

</div>

@endsection