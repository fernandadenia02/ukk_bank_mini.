@extends('layouts.app')

@section('title', 'Teller Dashboard')

@section('content')

<div class="container-fluid">

    {{-- =============================== --}}
    {{-- HEADER --}}
    {{-- =============================== --}}

    <div class="mb-4">

        <h2 class="fw-bold">
            Teller Dashboard
        </h2>

        <p class="text-muted mb-0">
            Selamat datang di sistem E-Teller Bank Mini.
        </p>

    </div>

    @include('shared.bank-status', ['bankIsOpen' => $bankIsOpen, 'showControls' => true])

    {{-- =============================== --}}
    {{-- STATISTIK --}}
    {{-- =============================== --}}

    <div class="row g-4 mb-4">

        {{-- TRANSAKSI HARI INI --}}
        <div class="col-md-4">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <p class="text-muted mb-1">
                                Today's Transactions
                            </p>

                            <h2 class="fw-bold mb-0">
                                {{ $todayTransactions }}
                            </h2>

                        </div>

                        <div
                            class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                            style="width:50px;height:50px;"
                        >

                            

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- SETORAN --}}
        <div class="col-md-4">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <p class="text-muted mb-1">
                                Total Deposit
                            </p>

                            <h2 class="fw-bold text-success mb-0">

                                Rp {{ number_format(
                                    $totalDeposit,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </h2>

                        </div>

                        <div
                            class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center"
                            style="width:50px;height:50px;"
                        >

                            

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- PENARIKAN --}}
        <div class="col-md-4">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <p class="text-muted mb-1">
                                Total Withdrawal
                            </p>

                            <h2 class="fw-bold text-danger mb-0">

                                Rp {{ number_format(
                                    $totalWithdrawal,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </h2>

                        </div>

                        <div
                            class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center"
                            style="width:50px;height:50px;"
                        >

                            

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ================================================= --}}
    {{-- SEARCH NASABAH --}}
    {{-- ================================================= --}}

    <div class="card shadow-sm mb-4">

        <div class="card-header bg-primary text-white">

            <h5 class="mb-0">

                

                Search Nasabah

            </h5>

        </div>


        <div class="card-body">

            <form
                action="{{ route('teller.dashboard') }}"
                method="GET"
            >

                <div class="row g-2">

                    <div class="col-md-8">

                        <input
                            type="text"
                            name="search"
                            class="form-control form-control-lg"
                            placeholder="Cari nama atau nomor rekening nasabah..."
                            value="{{ $search ?? '' }}"
                        >

                    </div>


                    <div class="col-md-2">

                        <button
                            type="submit"
                            class="btn btn-primary btn-lg w-100"
                        >

                            

                            Cari

                        </button>

                    </div>

                    <div class="col-md-2">

                        <a
                            href="{{ route('transactions.create', ['scan' => 1]) }}#qr-scanner"
                            class="btn btn-success btn-lg w-100"
                        >

                            

                            Scan QR

                        </a>

                    </div>

                </div>

            </form>


            {{-- =============================== --}}
            {{-- HASIL PENCARIAN --}}
            {{-- =============================== --}}

            @if(!empty($search))

                <hr>

                <h6 class="fw-bold mb-3">

                    Hasil pencarian untuk:
                    <span class="text-primary">
                        "{{ $search }}"
                    </span>

                </h6>


                @if($customers->count() > 0)

                    <div class="table-responsive">

                        <table class="table table-hover align-middle">

                            <thead class="table-light">

                                <tr>

                                    <th>No</th>

                                    <th>Nomor Rekening</th>

                                    <th>Nama Nasabah</th>

                                    <th>Saldo</th>

                                    <th>Status</th>

                                    <th width="180">
                                        Aksi
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @foreach($customers as $customer)

                                    <tr>

                                        <td>
                                            {{ $loop->iteration }}
                                        </td>


                                        <td>

                                            <span class="fw-bold">

                                                {{ $customer->account_number }}

                                            </span>

                                        </td>


                                        <td>

                                            {{ $customer->name }}

                                        </td>


                                        <td>

                                            <span class="text-success fw-bold">

                                                Rp
                                                {{ number_format(
                                                    $customer->balance,
                                                    0,
                                                    ',',
                                                    '.'
                                                ) }}

                                            </span>

                                        </td>

                                        <td>
                                            @if($customer->status === 'aktif')
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary">Belum aktif</span>
                                            @endif
                                        </td>

                                        <td>

                                            <a
                                                href="{{ route(
                                                    'transactions.create',
                                                    ['customer_id' => $customer->id]
                                                ) }}"
                                                class="btn btn-success btn-sm"
                                            >

                                                

                                                {{ $customer->status === 'aktif' ? 'Transaksi' : 'Aktivasi' }}

                                            </a>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                @else

                    <div class="alert alert-warning mb-0">

                        

                        Nasabah dengan nama atau nomor rekening

                        <strong>
                            "{{ $search }}"
                        </strong>

                        tidak ditemukan.

                    </div>

                @endif

            @endif

        </div>

    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">
                
                Customer Belum Aktif
                <span class="badge bg-dark">{{ $inactiveCustomers->total() }}</span>
            </h5>
        </div>
        <div class="card-body">
            <p class="text-muted">
                Untuk mengaktifkan rekening, buka rekening dari daftar Anggota dan masukkan setoran awal minimal Rp 10.000.
                Setoran pembukaan akan dicatat sebagai transaksi.
            </p>
            <a href="{{ route('teller.members.index') }}" class="btn btn-primary mb-3">
                
                Buka Rekening dari Data Anggota
            </a>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nomor Rekening</th>
                            <th>Nama Nasabah</th>
                            <th>Saldo</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($inactiveCustomers as $customer)
                        <tr>
                            <td>{{ ($inactiveCustomers->currentPage() - 1) * $inactiveCustomers->perPage() + $loop->iteration }}</td>
                            <td>{{ $customer->account_number }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>Rp {{ number_format($customer->balance, 0, ',', '.') }}</td>
                            <td>
                                <a
                                    href="{{ route('transactions.create', ['customer_id' => $customer->id, 'activation' => 1]) }}"
                                    class="btn btn-success btn-sm"
                                >
                                    
                                    Aktivasi via Setoran
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Tidak ada customer yang menunggu aktivasi.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center">
                {{ $inactiveCustomers->links() }}
            </div>
        </div>
    </div>


    {{-- ================================================= --}}
    {{-- INFORMASI DAILY REPORT --}}
    {{-- ================================================= --}}

    @if($rejectedReports->isNotEmpty())
        <div class="card shadow-sm border-danger mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    
                    Catatan Penolakan Laporan
                </h5>
            </div>

            <div class="list-group list-group-flush">
                @foreach($rejectedReports as $rejectedReport)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <strong>{{ $rejectedReport->date }}</strong>
                                <div class="mt-1" style="white-space: pre-line;">{{ $rejectedReport->rejection_reason ?: 'Alasan penolakan belum tersedia.' }}</div>
                            </div>
                            <a
                                href="{{ route('teller.daily-reports.show', $rejectedReport->id) }}"
                                class="btn btn-outline-danger btn-sm flex-shrink-0"
                            >
                                Detail
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($dailyReport)

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="card shadow-sm">

            <div class="card-header">

                <h5 class="mb-0">

                    

                    Daily Report Hari Ini

                </h5>

            </div>


            <div class="card-body">

                <div class="row">

                    <div class="col-md">
                        <small class="text-muted">Modal Awal Kas</small>
                        <h5 class="text-primary">
                            Rp {{ number_format($dailyReport->beginning_balance, 0, ',', '.') }}
                        </h5>
                    </div>

                    <div class="col-md">

                        <small class="text-muted">
                            Total Setoran
                        </small>

                        <h5 class="text-success">

                            Rp
                            {{ number_format(
                                $dailyReport->total_deposit,
                                0,
                                ',',
                                '.'
                            ) }}

                        </h5>

                    </div>


                    <div class="col-md">

                        <small class="text-muted">
                            Total Penarikan
                        </small>

                        <h5 class="text-danger">

                            Rp
                            {{ number_format(
                                $dailyReport->total_withdrawals,
                                0,
                                ',',
                                '.'
                            ) }}

                        </h5>

                    </div>


                    <div class="col-md">

                        <small class="text-muted">
                            Sisa Modal Awal / Kas Tersedia
                        </small>

                        <h5>

                            Rp
                            {{ number_format(
                                $dailyReport->ending_balance,
                                0,
                                ',',
                                '.'
                            ) }}

                        </h5>

                    </div>


                    <div class="col-md">

                        <small class="text-muted">
                            Status
                        </small>

                        <h5>

                            @if($dailyReport->status == 'pending')

                                <span class="badge bg-danger text-white">
                                    Pending
                                </span>

                            @elseif($dailyReport->status == 'approved')

                                <span class="badge bg-success">
                                    Approved
                                </span>

                            @else

                                <span class="badge bg-danger">
                                    Rejected
                                </span>

                            @endif

                        </h5>

                    </div>

                </div>

                <hr>
                @if(!$dailyReport->closed_at && in_array($dailyReport->status, ['pending', 'rejected'], true))
                    @if($dailyReport->ending_balance <= 0 && !$cashAdvanceRequests->contains('status', 'pending'))
                        <form action="{{ route('teller.daily-reports.opening-cash') }}" method="POST"
                            class="row g-2 align-items-end mb-3">
                            @csrf
                            <div class="col-md-5">
                                <label for="opening_cash" class="form-label mb-1">Ajukan modal kas ke admin</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" id="opening_cash" name="amount"
                                        class="form-control" inputmode="numeric" pattern="[0-9.]*"
                                        value="{{ old('amount') }}"
                                        required>
                                </div>
                                <div class="form-text">Permintaan akan menambah saldo kas setelah disetujui admin.</div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">Kirim Permintaan Modal</button>
                            </div>
                        </form>
                    @elseif($cashAdvanceRequests->contains('status', 'pending'))
                        <div class="alert alert-warning">
                            Permintaan modal sedang menunggu persetujuan admin. Transaksi penarikan dapat dilakukan setelah disetujui dan kas tersedia.
                        </div>
                    @endif
                @endif

                @if($cashAdvanceRequests->isNotEmpty())
                    <div class="mb-3">
                        <strong>Riwayat Permintaan Modal Kas</strong>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                        <th>Catatan Admin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cashAdvanceRequests as $cashRequest)
                                        <tr>
                                            <td>{{ $cashRequest->requested_at->timezone('Asia/Jakarta')->format('d-m-Y H:i') }} WIB</td>
                                            <td>Rp {{ number_format($cashRequest->amount, 0, ',', '.') }}</td>
                                            <td>
                                                @if($cashRequest->status === 'pending')
                                                    <span class="badge bg-danger text-white">Menunggu persetujuan</span>
                                                @elseif($cashRequest->status === 'approved')
                                                    <span class="badge bg-success">Disetujui</span>
                                                    @if($cashRequest->reviewer)
                                                        <div class="small text-muted">{{ $cashRequest->reviewer->officer_name }}</div>
                                                    @endif
                                                @else
                                                    <span class="badge bg-danger">Ditolak</span>
                                                @endif
                                            </td>
                                            <td>{{ $cashRequest->rejection_reason ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <strong>Status Kas:</strong>
                        @if($dailyReport->closed_at)
                            <span class="badge bg-secondary">Ditutup {{ $dailyReport->closed_at->timezone('Asia/Jakarta')->format('H:i') }} WIB</span>
                            <div class="small text-muted mt-1">
                                Uang fisik Rp {{ number_format($dailyReport->physical_money, 0, ',', '.') }}
                                | Selisih Rp {{ number_format($dailyReport->difference, 0, ',', '.') }}
                            </div>
                        @else
                            <span class="badge bg-danger text-white">Belum ditutup</span>
                        @endif
                    </div>
                    @if(!$dailyReport->closed_at && in_array($dailyReport->status, ['pending', 'rejected'], true))
                        <form action="{{ route('teller.daily-reports.close') }}" method="POST"
                            class="d-flex flex-wrap align-items-end gap-2"
                            onsubmit="return confirm('Ajukan tutup kas harian? Transaksi akan dikunci sampai laporan ditolak atau disetujui admin.')">
                            @csrf
                            <div>
                                <label for="physical_money" class="form-label mb-1">
                                    {{ $dailyReport->status === 'rejected' ? 'Perbaiki uang fisik laporan' : 'Uang fisik saat tutup kas' }}
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" id="physical_money" name="physical_money"
                                        class="form-control" inputmode="numeric" pattern="[0-9.]*"
                                        value="{{ old('physical_money', number_format($dailyReport->ending_balance, 0, ',', '.')) }}"
                                        required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger">
                                {{ $dailyReport->status === 'rejected' ? 'Ajukan Ulang Tutup Kas' : 'Tutup Kas Harian' }}
                            </button>
                        </form>
                    @endif
                </div>

                <div class="text-end mt-3">
                    <a
                        href="{{ route('teller.daily-reports.show', $dailyReport->id) }}"
                        class="btn btn-outline-primary btn-sm"
                    >
                        Lihat detail laporan
                    </a>
                </div>

            </div>

        </div>

    @endif

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        ['opening_cash', 'physical_money'].forEach(function (inputId) {
            const moneyInput = document.getElementById(inputId);
            if (!moneyInput) {
                return;
            }

            moneyInput.addEventListener('input', function () {
                const digits = moneyInput.value.replace(/\D/g, '');
                moneyInput.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            });
        });
    });
</script>

@endsection