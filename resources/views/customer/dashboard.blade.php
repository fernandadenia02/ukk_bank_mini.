@extends('layouts.app')

@section('title', 'Customer Dashboard')

@section('content')

<style>
    /* ================================
       CUSTOMER DASHBOARD RESPONSIVE
    ================================= */

    .customer-dashboard {
        width: 100%;
        overflow-x: hidden;
    }

    .balance-card {
        border: none;
        border-radius: 16px;
    }

    .balance-amount {
        font-size: 2.2rem;
        font-weight: 700;
        word-break: break-word;
    }

    .dashboard-card {
        border: none;
        border-radius: 14px;
    }

    .stat-card {
        min-height: 120px;
    }

    .qr-image {
        width: 220px;
        max-width: 100%;
        height: auto;
    }

    .info-value {
        word-break: break-word;
    }

    .transaction-table {
        min-width: 750px;
    }

    .table-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* ================================
       MOBILE
    ================================= */

    @media (max-width: 767.98px) {

        .customer-dashboard {
            padding-left: 0;
            padding-right: 0;
        }

        .dashboard-title {
            font-size: 1.5rem;
        }

        .balance-card .card-body {
            padding: 20px;
        }

        .balance-amount {
            font-size: 1.8rem;
        }

        .balance-account {
            font-size: 0.9rem;
        }

        .qr-image {
            width: 180px;
        }

        .stat-card {
            min-height: auto;
        }

        .stat-card h3 {
            font-size: 1.4rem;
        }

        .card-header {
            padding: 12px 15px;
        }

        .card-body {
            padding: 15px;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .btn-mobile {
            width: 100%;
        }

        .table-wrapper {
            margin-left: -5px;
            margin-right: -5px;
            width: calc(100% + 10px);
        }

        .transaction-table th,
        .transaction-table td {
            white-space: nowrap;
            font-size: 0.85rem;
        }

        .transaction-table .btn {
            white-space: nowrap;
        }
    }
</style>


<div class="customer-dashboard">

    {{-- ========================================= --}}
    {{-- HEADER --}}
    {{-- ========================================= --}}

    <div class="mb-4">

        <h2 class="dashboard-title mb-1">
            Customer Dashboard
        </h2>

        <p class="text-muted mb-0">
            Selamat datang,
            <strong>{{ $customer->name }}</strong>
        </p>

    </div>

    @include('shared.bank-status', ['bankIsOpen' => $bankIsOpen, 'showControls' => false])

    {{-- ========================================= --}}
    {{-- SALDO UTAMA --}}
    {{-- ========================================= --}}

    <div class="card shadow-sm balance-card mb-4">

        <div class="card-body">

            <div class="row align-items-center">

                <div class="col-md-8">

                    <p class="text-muted mb-1">
                        Saldo Rekening
                    </p>

                    <div class="balance-amount text-success">

                        Rp {{ number_format(
                            $customer->balance,
                            0,
                            ',',
                            '.'
                        ) }}

                    </div>

                    <p class="balance-account text-muted mb-0">

                        Nomor Rekening:

                        <strong>
                            {{ $customer->account_number }}
                        </strong>

                    </p>

                </div>


                <div class="col-md-4 mt-3 mt-md-0">

                    <a
                        href="{{ asset('qrcode/' . $customer->qrcode) }}"
                        target="_blank"
                        class="btn btn-primary btn-mobile"
                    >

                        📱 Lihat QR Code

                    </a>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================= --}}
    {{-- STATISTIK --}}
    {{-- ========================================= --}}

    <div class="row mb-4">

        {{-- TOTAL TRANSAKSI --}}
        <div class="col-12 col-sm-4 mb-3">

            <div class="card shadow-sm dashboard-card stat-card h-100">

                <div class="card-body">

                    <p class="text-muted mb-1">
                        Total Transaksi
                    </p>

                    <h3 class="mb-0">
                        {{ $totalTransactions }}
                    </h3>

                </div>

            </div>

        </div>


        {{-- TOTAL SETORAN --}}
        <div class="col-12 col-sm-4 mb-3">

            <div class="card shadow-sm dashboard-card stat-card h-100">

                <div class="card-body">

                    <p class="text-muted mb-1">
                        Total Setoran
                    </p>

                    <h3 class="text-success mb-0">

                        Rp {{ number_format(
                            $totalDeposit,
                            0,
                            ',',
                            '.'
                        ) }}

                    </h3>

                </div>

            </div>

        </div>


        {{-- TOTAL PENARIKAN --}}
        <div class="col-12 col-sm-4 mb-3">

            <div class="card shadow-sm dashboard-card stat-card h-100">

                <div class="card-body">

                    <p class="text-muted mb-1">
                        Total Penarikan
                    </p>

                    <h3 class="text-danger mb-0">

                        Rp {{ number_format(
                            $totalWithdrawal,
                            0,
                            ',',
                            '.'
                        ) }}

                    </h3>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================= --}}
    {{-- INFORMASI NASABAH + QR --}}
    {{-- ========================================= --}}

    <div class="row mb-4">

        {{-- DATA NASABAH --}}
        <div class="col-12 col-lg-8 mb-3">

            <div class="card shadow-sm dashboard-card h-100">

                <div class="card-header bg-primary text-white">

                    <strong>
                        👤 Data Nasabah
                    </strong>

                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-12 col-sm-6 info-item">

                            <small class="text-muted">
                                Nama
                            </small>

                            <div class="fw-bold info-value">
                                {{ $customer->name }}
                            </div>

                        </div>


                        <div class="col-12 col-sm-6 info-item">

                            <small class="text-muted">
                                Nomor Rekening
                            </small>

                            <div class="fw-bold info-value">
                                {{ $customer->account_number }}
                            </div>

                        </div>


                        <div class="col-12 col-sm-6 info-item">

                            <small class="text-muted">
                                NIS
                            </small>

                            <div class="fw-bold info-value">
                                {{ $customer->nis }}
                            </div>

                        </div>


                        <div class="col-12 col-sm-6 info-item">

                            <small class="text-muted">
                                Kelas
                            </small>

                            <div class="fw-bold info-value">
                                {{ $customer->class }}
                            </div>

                        </div>


                        <div class="col-12 col-sm-6 info-item">

                            <small class="text-muted">
                                Alamat
                            </small>

                            <div class="fw-bold info-value">
                                {{ $customer->location }}
                            </div>

                        </div>


                        <div class="col-12 col-sm-6 info-item">

                            <small class="text-muted">
                                No. Telepon
                            </small>

                            <div class="fw-bold info-value">
                                {{ $customer->telephone_number }}
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- QR CODE --}}
        <div class="col-12 col-lg-4 mb-3">

            <div class="card shadow-sm dashboard-card h-100 text-center">

                <div class="card-header bg-dark text-white">

                    <strong>
                        📱 QR Code Rekening
                    </strong>

                </div>

                <div class="card-body d-flex flex-column align-items-center justify-content-center">

                    @if($customer->qrcode)

                        <img
                            src="{{ asset('qrcode/' . $customer->qrcode) }}"
                            alt="QR Code"
                            class="qr-image img-fluid"
                        >

                        <p class="mt-3 mb-2">

                            <strong>
                                {{ $customer->account_number }}
                            </strong>

                        </p>

                        <a
                            href="{{ asset('qrcode/' . $customer->qrcode) }}"
                            target="_blank"
                            class="btn btn-primary btn-sm"
                        >

                            Buka QR

                        </a>

                    @else

                        <div class="alert alert-warning mb-0">

                            QR Code belum tersedia.

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================= --}}
    {{-- 5 TRANSAKSI TERBARU --}}
    {{-- ========================================= --}}

    <div class="card shadow-sm dashboard-card mb-4">

        <div class="card-header bg-dark text-white">

            <strong>
                🧾 5 Transaksi Terbaru
            </strong>

        </div>

        <div class="card-body">

            @if($latestTransactions->count() > 0)

                {{-- Scroll horizontal khusus HP --}}
                <div class="table-wrapper">

                    <table class="table table-bordered table-hover transaction-table">

                        <thead class="table-dark">

                            <tr>

                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Nominal</th>
                                <th>Saldo Awal</th>
                                <th>Saldo Akhir</th>
                                <th>Aksi</th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach($latestTransactions as $transaction)

                                <tr>

                                    <td>
                                        {{ $loop->iteration }}
                                    </td>

                                    <td>
                                        {{ $transaction->date }}
                                    </td>

                                    <td>

                                        @if(
                                            $transaction->transaction_type
                                            == 'setoran'
                                        )

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

                                        Rp {{ number_format(
                                            $transaction->beginning_balance,
                                            0,
                                            ',',
                                            '.'
                                        ) }}

                                    </td>

                                    <td>

                                        Rp {{ number_format(
                                            $transaction->ending_balance,
                                            0,
                                            ',',
                                            '.'
                                        ) }}

                                    </td>

                                    <td>

                                        <a
                                            href="{{ route(
                                                'transactions.receipt',
                                                $transaction->id
                                            ) }}"
                                            target="_blank"
                                            class="btn btn-primary btn-sm"
                                        >

                                            🧾 Bukti

                                        </a>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

                <div class="text-muted small mt-2">

                    💡 Geser tabel ke kanan/kiri untuk melihat seluruh data.

                </div>

            @else

                <div class="alert alert-info mb-0">

                    Belum ada transaksi.

                </div>

            @endif

        </div>

    </div>


    {{-- ========================================= --}}
    {{-- SEMUA RIWAYAT TRANSAKSI --}}
    {{-- ========================================= --}}

    <div class="card shadow-sm dashboard-card">

        <div class="card-header bg-secondary text-white">

            <strong>
                📋 Semua Riwayat Transaksi
            </strong>

        </div>

        <div class="card-body">

            @if($transactions->count() > 0)

                <div class="table-wrapper">

                    <table class="table table-striped table-bordered transaction-table">

                        <thead>

                            <tr>

                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Nominal</th>
                                <th>Saldo Akhir</th>

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

                                        @if(
                                            $transaction->transaction_type
                                            == 'setoran'
                                        )

                                            <span class="text-success fw-bold">
                                                Deposit
                                            </span>

                                        @else

                                            <span class="text-danger fw-bold">
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

                                        Rp {{ number_format(
                                            $transaction->ending_balance,
                                            0,
                                            ',',
                                            '.'
                                        ) }}

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

                <div class="text-muted small mt-2">

                    💡 Geser tabel ke kanan/kiri untuk melihat seluruh data.

                </div>

            @else

                <div class="alert alert-info mb-0">

                    Belum ada riwayat transaksi.

                </div>

            @endif

        </div>

    </div>

</div>

@endsection