@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')

{{-- ============================= --}}
{{-- HEADER --}}
{{-- ============================= --}}

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h2 class="fw-bold mb-1">
             Admin Dashboard
        </h2>

        <p class="text-muted mb-0">
            Monitoring Sistem E-Teller Bank Mini
        </p>

    </div>

    <div class="text-end">

        <small class="text-muted">
            {{ now()->format('d F Y') }}
        </small>

    </div>

</div>


{{-- ============================= --}}
{{-- STATISTIK USER --}}
{{-- ============================= --}}

<h5 class="fw-bold mb-3">
     User & Customer
</h5>

<div class="row g-4 mb-4">

    {{-- TOTAL USER --}}
    <div class="col-md-3">

        <div class="card shadow border-0 h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <div>

                        <small class="text-muted">
                            Total Users
                        </small>

                        <h2 class="fw-bold">
                            {{ $totalUsers }}
                        </h2>

                    </div>

                    <div class="fs-1 text-primary" aria-hidden="true">
                        <i class="bi bi-people-fill"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- CUSTOMER --}}
    <div class="col-md-3">

        <div class="card shadow border-0 h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <div>

                        <small class="text-muted">
                            Customers
                        </small>

                        <h2 class="fw-bold text-success">
                            {{ $totalCustomers }}
                        </h2>

                    </div>

                    <div class="fs-1 text-success" aria-hidden="true">
                        <i class="bi bi-person-vcard-fill"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- TELLER --}}
    <div class="col-md-3">

        <div class="card shadow border-0 h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <div>

                        <small class="text-muted">
                            Tellers
                        </small>

                        <h2 class="fw-bold text-primary">
                            {{ $totalTellers }}
                        </h2>

                    </div>

                    <div class="fs-1 text-primary" aria-hidden="true">
                        <i class="bi bi-person-workspace"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- SUPERVISOR --}}
    <div class="col-md-3">

        <div class="card shadow border-0 h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <div>

                        <small class="text-muted">
                            Supervisors
                        </small>

                        <h2 class="fw-bold text-warning">
                            {{ $totalSupervisors }}
                        </h2>

                    </div>

                    <div class="fs-1 text-warning" aria-hidden="true">
                        <i class="bi bi-shield-check"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- ============================= --}}
{{-- STATISTIK TRANSAKSI --}}
{{-- ============================= --}}

<h5 class="fw-bold mb-3">
     Transaksi Hari Ini
</h5>

<div class="row g-4 mb-4">

    {{-- TRANSAKSI --}}
    <div class="col-md-3">

        <div class="card shadow border-0">

            <div class="card-body text-center">

                <h6 class="text-muted">
                    Transactions
                </h6>

                <h2 class="fw-bold text-primary">
                    {{ $todayTransactions }}
                </h2>

            </div>

        </div>

    </div>


    {{-- DEPOSIT --}}
    <div class="col-md-3">

        <div class="card shadow border-0">

            <div class="card-body text-center">

                <h6 class="text-muted">
                    Total Deposit
                </h6>

                <h4 class="fw-bold text-success">

                    Rp {{ number_format(
                        $totalDeposit,
                        0,
                        ',',
                        '.'
                    ) }}

                </h4>

            </div>

        </div>

    </div>


    {{-- WITHDRAWAL --}}
    <div class="col-md-3">

        <div class="card shadow border-0">

            <div class="card-body text-center">

                <h6 class="text-muted">
                    Total Withdrawal
                </h6>

                <h4 class="fw-bold text-danger">

                    Rp {{ number_format(
                        $totalWithdrawal,
                        0,
                        ',',
                        '.'
                    ) }}

                </h4>

            </div>

        </div>

    </div>


    {{-- TOTAL TRANSAKSI --}}
    <div class="col-md-3">

        <div class="card shadow border-0">

            <div class="card-body text-center">

                <h6 class="text-muted">
                    All Transactions
                </h6>

                <h2 class="fw-bold">
                    {{ $totalTransactions }}
                </h2>

            </div>

        </div>

    </div>

</div>


{{-- ============================= --}}
{{-- DAILY REPORT --}}
{{-- ============================= --}}

<h5 class="fw-bold mb-3">
     Daily Report
</h5>

<div class="row g-4 mb-5">

    {{-- PENDING --}}
    <div class="col-md-4">

        <div class="card shadow border-0">

            <div class="card-body text-center">

                <span class="badge bg-warning text-dark mb-2">
                    Pending
                </span>

                <h2 class="fw-bold">
                    {{ $pendingReports }}
                </h2>

            </div>

        </div>

    </div>


    {{-- APPROVED --}}
    <div class="col-md-4">

        <div class="card shadow border-0">

            <div class="card-body text-center">

                <span class="badge bg-success mb-2">
                    Approved
                </span>

                <h2 class="fw-bold">
                    {{ $approvedReports }}
                </h2>

            </div>

        </div>

    </div>


    {{-- REJECTED --}}
    <div class="col-md-4">

        <div class="card shadow border-0">

            <div class="card-body text-center">

                <span class="badge bg-danger mb-2">
                    Rejected
                </span>

                <h2 class="fw-bold">
                    {{ $rejectedReports }}
                </h2>

            </div>

        </div>

    </div>

</div>


{{-- ============================= --}}
{{-- TRANSAKSI TERBARU --}}
{{-- ============================= --}}

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-dark text-white">

        <strong>
             Transaksi Terbaru
        </strong>

    </div>

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-bordered table-hover mb-0">

                <thead class="table-light">

                    <tr>

                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Customer</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                        <th>Teller</th>

                    </tr>

                </thead>

                <tbody>

                @forelse($latestTransactions as $transaction)

                    <tr>

                        <td>
                            {{ $loop->iteration }}
                        </td>

                        <td>
                            {{ $transaction->date }}
                        </td>

                        <td>

                            {{ $transaction->customer->name ?? '-' }}

                            <br>

                            <small class="text-muted">

                                {{ $transaction->customer->account_number ?? '-' }}

                            </small>

                        </td>

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

                            Rp {{ number_format(
                                $transaction->amount,
                                0,
                                ',',
                                '.'
                            ) }}

                        </td>

                        <td>

                            {{ $transaction->teller->officer_name ?? '-' }}

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="6"
                            class="text-center py-4"
                        >

                            Belum ada transaksi.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>


{{-- ============================= --}}
{{-- CUSTOMER TERBARU --}}
{{-- ============================= --}}

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-primary text-white">

        <strong>
             Customer Terbaru
        </strong>

    </div>

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-bordered table-hover mb-0">

                <thead class="table-light">

                    <tr>

                        <th>No</th>
                        <th>Account Number</th>
                        <th>NIS</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Balance</th>

                    </tr>

                </thead>

                <tbody>

                @forelse($latestCustomers as $customer)

                    <tr>

                        <td>
                            {{ $loop->iteration }}
                        </td>

                        <td>
                            {{ $customer->account_number }}
                        </td>

                        <td>
                            {{ $customer->nis }}
                        </td>

                        <td>
                            {{ $customer->name }}
                        </td>

                        <td>
                            {{ $customer->class }}
                        </td>

                        <td>

                            Rp {{ number_format(
                                $customer->balance,
                                0,
                                ',',
                                '.'
                            ) }}

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="6"
                            class="text-center py-4"
                        >

                            Belum ada customer.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>


{{-- ============================= --}}
{{-- QUICK ACTION --}}
{{-- ============================= --}}

<div class="card shadow border-0">

    <div class="card-header bg-dark text-white">

        <strong>
             Quick Action
        </strong>

    </div>

    <div class="card-body">

        <a
            href="{{ route('customers.index') }}"
            class="btn btn-primary me-2"
        >
             Manage Customers
        </a>

        <a
            href="{{ route('users.index') }}"
            class="btn btn-secondary me-2"
        >
             Manage Users
        </a>

        <a
            href="{{ route('daily-reports.index') }}"
            class="btn btn-warning me-2"
        >
             Daily Reports
        </a>

        <a
            href="{{ route('accounting-journal.index') }}"
            class="btn btn-success"
        >
             Accounting Journal
        </a>

        <a href="{{ route('monthly-reports.index') }}" class="btn btn-primary">
    Laporan Bulanan
</a>

    </div>

</div>

@endsection