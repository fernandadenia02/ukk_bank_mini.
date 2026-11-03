@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')

<h2 class="mb-4 fw-bold">
    📊 Admin Dashboard
</h2>

<div class="row g-4">

    <div class="col-md-3">
        <div class="card text-bg-primary shadow border-0">
            <div class="card-body text-center">
                <h6>Total Users</h6>
                <h2>{{ $totalUsers }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-bg-success shadow border-0">
            <div class="card-body text-center">
                <h6>Total Customers</h6>
                <h2>{{ $totalCustomers }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-bg-warning shadow border-0">
            <div class="card-body text-center">
                <h6>Total Transactions</h6>
                <h2>{{ $totalTransactions }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card text-bg-danger shadow border-0">
            <div class="card-body text-center">
                <h6>Pending Reports</h6>
                <h2>{{ $pendingReports }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-success shadow">
            <div class="card-body text-center">
                <h6>Today's Deposit</h6>
                <h3 class="text-success">
                    Rp {{ number_format($totalDeposit,0,',','.') }}
                </h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-danger shadow">
            <div class="card-body text-center">
                <h6>Today's Withdrawal</h6>
                <h3 class="text-danger">
                    Rp {{ number_format($totalWithdrawal,0,',','.') }}
                </h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-info shadow">
            <div class="card-body text-center">
                <h6>Today's Transactions</h6>
                <h2 class="text-info">
                    {{ $todayTransactions }}
                </h2>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-secondary shadow">
            <div class="card-body text-center">
                <h6>Approved Reports</h6>
                <h2 class="text-secondary">
                    {{ $approvedReports }}
                </h2>
            </div>
        </div>
    </div>

</div>

<hr class="my-5">

<div class="card shadow">

    <div class="card-header bg-dark text-white">

        <h5 class="mb-0">
            📈 System Summary
        </h5>

    </div>

    <div class="card-body">

        <div class="row text-center">

            <div class="col-md-4">

                <h6>Total Deposit Today</h6>

                <h3 class="text-success">
                    Rp {{ number_format($totalDeposit,0,',','.') }}
                </h3>

            </div>

            <div class="col-md-4">

                <h6>Total Withdrawal Today</h6>

                <h3 class="text-danger">
                    Rp {{ number_format($totalWithdrawal,0,',','.') }}
                </h3>

            </div>

            <div class="col-md-4">

                <h6>Today's Transactions</h6>

                <h3 class="text-primary">
                    {{ $todayTransactions }}
                </h3>

            </div>

        </div>

    </div>

</div>

@endsection