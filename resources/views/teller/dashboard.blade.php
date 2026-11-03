@extends('layouts.app')

@section('title', 'Teller Dashboard')

@section('content')

<h2 class="mb-4">Teller Dashboard</h2>

<div class="row">

    <div class="col-md-4 mb-3">
        <div class="card text-bg-primary shadow">
            <div class="card-body text-center">
                <h5>Today's Transactions</h5>
                <h2>{{ $todayTransactions }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card text-bg-success shadow">
            <div class="card-body text-center">
                <h5>Total Deposit</h5>
                <h2>Rp {{ number_format($totalDeposit,0,',','.') }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card text-bg-danger shadow">
            <div class="card-body text-center">
                <h5>Total Withdrawal</h5>
                <h2>Rp {{ number_format($totalWithdrawal,0,',','.') }}</h2>
            </div>
        </div>
    </div>

</div>

@endsection