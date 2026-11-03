@extends('layouts.app')

@section('title', 'New Transaction')

@section('content')

<style>
    .transaction-page {
        max-width: 1000px;
        margin: 0 auto;
    }

    .qr-card {
        border-radius: 15px;
        overflow: hidden;
    }

    .qr-reader-wrapper {
        width: 100%;
        max-width: 500px;
        margin: 20px auto;
    }

    #qrReader {
        width: 100%;
        border-radius: 12px;
        overflow: hidden;
    }

    #qrReader video {
        transform: none !important;
    }

    .customer-info-card {
        border-radius: 15px;
        overflow: hidden;
    }

    .balance-number {
        font-size: 1.8rem;
        font-weight: bold;
    }

    .form-label {
        font-weight: 600;
    }

    @media (max-width: 576px) {

        .transaction-page {
            width: 100%;
        }

        .transaction-page h2 {
            font-size: 1.5rem;
        }

        .card-body {
            padding: 1rem;
        }

        .balance-number {
            font-size: 1.4rem;
        }

        #qrReader {
            max-width: 100%;
        }

        .qr-reader-wrapper {
            max-width: 100%;
        }

        .btn {
            width: 100%;
            margin-bottom: 8px;
        }

        .btn:last-child {
            margin-bottom: 0;
        }

        .customer-info-card .col-md-4 {
            margin-bottom: 15px;
        }
    }
</style>


<div class="transaction-page">

    {{-- ========================= --}}
    {{-- HEADER --}}
    {{-- ========================= --}}

    <div class="mb-4">

        <h2 class="fw-bold mb-1">
            <i class="bi bi-cash-stack"></i>
            New Transaction
        </h2>

        <p class="text-muted mb-0">
            Proses transaksi setoran atau penarikan customer.
        </p>

    </div>

    {{-- ========================= --}}
    {{-- ERROR --}}
    {{-- ========================= --}}

    @if(session('error'))

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="bi bi-exclamation-triangle-fill"></i>

            {{ session('error') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    @endif

    @if(session('pin_reset_status'))
        <div class="alert alert-success">
            {{ session('pin_reset_status') }}
        </div>
    @endif

    @if(session('pin_reset_error'))
        <div class="alert alert-danger">
            {{ session('pin_reset_error') }}
        </div>
    @endif


    @if(session('success'))

        <div class="alert alert-success alert-dismissible fade show">

            <i class="bi bi-check-circle-fill"></i>

            {{ session('success') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    @endif


    @if($errors->any())

        <div class="alert alert-danger">

            <strong>
                Terjadi kesalahan:
            </strong>

            <ul class="mb-0 mt-2">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif

    @if(request()->boolean('activation'))
        <div class="alert alert-warning">
            <strong>Aktivasi customer:</strong>
            lakukan setoran pertama minimal Rp 10.000. Status akan menjadi aktif
            hanya setelah transaksi berhasil disimpan.
        </div>
    @endif


    {{-- ========================= --}}
    {{-- SCAN QR CUSTOMER --}}
    {{-- ========================= --}}

    <div id="qr-scanner" class="card shadow-sm mb-4 qr-card">

        <div class="card-header bg-dark text-white py-3">

            <strong>
                <i class="bi bi-qr-code-scan"></i>
                Scan QR Customer
            </strong>

        </div>

        <div class="card-body">

            <p class="text-muted">

                Scan QR Code rekening customer
                untuk memilih customer secara otomatis.

            </p>


            <div class="d-flex flex-wrap gap-2">

                <button
                    type="button"
                    id="startScanner"
                    class="btn btn-primary">

                    <i class="bi bi-camera"></i>
                    Scan QR Code

                </button>


                <button
                    type="button"
                    id="stopScanner"
                    class="btn btn-danger"
                    style="display:none;">

                    <i class="bi bi-x-circle"></i>
                    Tutup Scanner

                </button>

            </div>


            {{-- CAMERA --}}

            <div
                id="qrReaderWrapper"
                class="qr-reader-wrapper"
                style="display:none;">

                <div id="qrReader"></div>

            </div>


            {{-- HASIL SCAN --}}

            <div
                id="scanResult"
                class="alert alert-info mt-3"
                style="display:none;">

            </div>

        </div>

    </div>


    {{-- ========================= --}}
    {{-- FORM TRANSAKSI --}}
    {{-- ========================= --}}

    <form
        id="transactionForm"
        action="{{ route('transactions.store') }}"
        method="POST">

        @csrf


        {{-- ========================= --}}
        {{-- CUSTOMER --}}
        {{-- ========================= --}}

        <div class="card shadow-sm mb-4">

            <div class="card-body">

                <div class="mb-3">

                    <label
                        for="customer_id"
                        class="form-label">

                        <i class="bi bi-person-vcard"></i>
                        Customer

                    </label>


                    <select
                        name="customer_id"
                        id="customer_id"
                        class="form-select form-select-lg"
                        required>

                        <option value="">
                            -- Select Customer --
                        </option>


                        @foreach($customers as $customer)

                            <option
                                value="{{ $customer->id }}"
                                data-account="{{ $customer->account_number }}"
                                data-name="{{ $customer->name }}"
                                data-balance="{{ $customer->balance }}"
                                data-active="{{ $customer->status === 'aktif' ? '1' : '0' }}"

                                {{ isset($selectedCustomer)
                                    && $selectedCustomer == $customer->id
                                    ? 'selected'
                                    : '' }}
                            >

                                {{ $customer->account_number }}
                                -
                                {{ $customer->name }}

                            </option>

                        @endforeach

                    </select>

                </div>

            </div>

        </div>


        {{-- ========================= --}}
        {{-- INFORMASI CUSTOMER --}}
        {{-- ========================= --}}

        <div
            id="customerInfo"
            class="card shadow-sm mb-4 customer-info-card"
            style="display:none;">

            <div class="card-header bg-primary text-white py-3">

                <strong>

                    <i class="bi bi-person-vcard"></i>

                    Customer Information

                </strong>

            </div>


            <div class="card-body">

                <div class="row">

                    {{-- ACCOUNT --}}

                    <div class="col-md-4 mb-3 mb-md-0">

                        <small class="text-muted">
                            Account Number
                        </small>

                        <div
                            id="accountNumber"
                            class="fw-bold mt-1">

                            -

                        </div>

                    </div>


                    {{-- NAME --}}

                    <div class="col-md-4 mb-3 mb-md-0">

                        <small class="text-muted">
                            Customer Name
                        </small>

                        <div
                            id="customerName"
                            class="fw-bold mt-1">

                            -

                        </div>

                    </div>


                    {{-- BALANCE --}}

                    <div class="col-md-4">

                        <small class="text-muted">
                            Current Balance
                        </small>

                        <div
                            id="currentBalance"
                            class="text-success balance-number mt-1">

                            Rp 0

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================= --}}
        {{-- TRANSACTION --}}
        {{-- ========================= --}}

        <div class="card shadow-sm mb-4">

            <div class="card-header bg-light py-3">

                <strong>

                    <i class="bi bi-arrow-left-right"></i>

                    Transaction Details

                </strong>

            </div>


            <div class="card-body">


                {{-- TRANSACTION TYPE --}}

                <div class="mb-4">

                    <label
                        for="transaction_type"
                        class="form-label">

                        Transaction Type

                    </label>


                    <select
                        name="transaction_type"
                        id="transaction_type"
                        class="form-select form-select-lg"
                        required>

                        <option value="setoran" selected>

                            Deposit

                        </option>

                        <option value="penarikan"
                            {{ old('transaction_type', 'setoran') === 'penarikan' ? 'selected' : '' }}>

                            Withdrawal

                        </option>

                    </select>

                </div>


                {{-- AMOUNT --}}

                <div class="mb-4">

                    <label
                        for="amount"
                        class="form-label">

                        Amount

                    </label>


                    <div class="input-group input-group-lg">

                        <span class="input-group-text">
                            Rp
                        </span>

                        <input
                            type="text"
                            name="amount"
                            id="amount"
                            class="form-control"
                            inputmode="numeric"
                            pattern="[0-9.,]*"
                            autocomplete="off"
                            placeholder="Enter amount"
                            required>

                    </div>

                    <small class="text-muted">

                        Minimal transaksi Rp 1.000.

                    </small>

                </div>

                <div
                    id="customerPinGroup"
                    class="mb-4"
                    style="display:none;">

                    <label for="customer_pin" class="form-label">
                        PIN Nasabah
                    </label>

                    <input
                        type="password"
                        name="customer_pin"
                        id="customer_pin"
                        class="form-control form-control-lg"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        minlength="6"
                        maxlength="6"
                        autocomplete="off"
                        placeholder="Masukkan PIN 6 angka nasabah"
                        disabled>

                    <small class="text-muted">
                        PIN nasabah diperlukan untuk transaksi penarikan.
                    </small>

                </div>


                {{-- ========================= --}}
                {{-- ESTIMATED BALANCE --}}
                {{-- ========================= --}}

                <div
                    id="balancePreview"
                    class="alert alert-info"
                    style="display:none;">

                    <div class="d-flex justify-content-between align-items-center flex-wrap">

                        <strong>
                            <i class="bi bi-wallet2"></i>
                            Estimated Balance
                        </strong>

                        <span
                            id="endingBalance"
                            class="fw-bold">

                            Rp 0

                        </span>

                    </div>

                </div>


            </div>

        </div>


        {{-- ========================= --}}
        {{-- BUTTON --}}
        {{-- ========================= --}}

        <div class="card shadow-sm mb-4">

            <div class="card-body">

                <div class="d-flex flex-wrap gap-2">

                    <button
                        type="submit"
                        class="btn btn-success btn-lg">

                        <i class="bi bi-check-circle"></i>

                        Save Transaction

                    </button>


                    <a
                        href="{{ route('transactions.index') }}"
                        class="btn btn-secondary btn-lg">

                        <i class="bi bi-arrow-left"></i>

                        Back

                    </a>

                </div>

            </div>

        </div>

    </form>

    <div id="pin-reset" class="card shadow-sm mb-4" style="display:none;">
        <div class="card-header bg-warning">
            <strong>
                <i class="bi bi-key"></i>
                Lupa PIN Nasabah
            </strong>
        </div>
        <div class="card-body">
            <p class="text-muted">
                Kode OTP akan dikirim ke email customer yang terdaftar. OTP berlaku 5 menit.
                Setelah customer memasukkan OTP dan PIN baru, PIN lama akan diganti.
            </p>

            <form action="{{ route('transactions.pin-reset.send') }}" method="POST" class="mb-4">
                @csrf
                <input type="hidden" name="customer_id" id="pinResetSendCustomerId">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-envelope"></i>
                    Kirim OTP ke Email Customer
                </button>
            </form>

            <form action="{{ route('transactions.pin-reset.update') }}" method="POST">
                @csrf
                <input type="hidden" name="customer_id" id="pinResetConfirmCustomerId">

                <div class="mb-3">
                    <label for="pin_reset_otp" class="form-label">Kode OTP dari Email</label>
                    <input
                        type="text"
                        name="otp"
                        id="pin_reset_otp"
                        class="form-control"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        minlength="6"
                        maxlength="6"
                        autocomplete="one-time-code"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="new_customer_pin" class="form-label">PIN Baru</label>
                    <input
                        type="password"
                        name="pin"
                        id="new_customer_pin"
                        class="form-control"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        minlength="6"
                        maxlength="6"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="new_customer_pin_confirmation" class="form-label">Konfirmasi PIN Baru</label>
                    <input
                        type="password"
                        name="pin_confirmation"
                        id="new_customer_pin_confirmation"
                        class="form-control"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        minlength="6"
                        maxlength="6"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-shield-check"></i>
                    Verifikasi OTP dan Ubah PIN
                </button>
            </form>
        </div>
    </div>

</div>


{{-- ========================= --}}
{{-- QR SCANNER LIBRARY --}}
{{-- ========================= --}}

<script src="https://unpkg.com/html5-qrcode"></script>


<script>

document.addEventListener('DOMContentLoaded', function () {

    // =========================
    // ELEMENT
    // =========================

    const customerSelect =
        document.getElementById('customer_id');

    const pinResetCard =
        document.getElementById('pin-reset');

    const pinResetSendCustomerId =
        document.getElementById('pinResetSendCustomerId');

    const pinResetConfirmCustomerId =
        document.getElementById('pinResetConfirmCustomerId');

    const transactionType =
        document.getElementById('transaction_type');

    const customerPinGroup =
        document.getElementById('customerPinGroup');

    const customerPin =
        document.getElementById('customer_pin');

    const transactionForm =
        document.getElementById('transactionForm');

    const amount =
        document.getElementById('amount');

    const customerInfo =
        document.getElementById('customerInfo');

    const accountNumber =
        document.getElementById('accountNumber');

    const customerName =
        document.getElementById('customerName');

    const currentBalance =
        document.getElementById('currentBalance');

    const balancePreview =
        document.getElementById('balancePreview');

    const endingBalance =
        document.getElementById('endingBalance');

    const startScanner =
        document.getElementById('startScanner');

    const stopScanner =
        document.getElementById('stopScanner');

    const qrReaderWrapper =
        document.getElementById('qrReaderWrapper');

    const scanResult =
        document.getElementById('scanResult');


    // =========================
    // VARIABLE
    // =========================

    let balance = 0;

    let html5QrCode = null;

    let scannerRunning = false;


    // =========================
    // FORMAT RUPIAH
    // =========================

    function formatRupiah(number) {

        return 'Rp ' +
            Number(number).toLocaleString('id-ID');

    }

    function formatAmountInput(value) {
        const digits = value.replace(/\D/g, '');

        return digits
            ? Number(digits).toLocaleString('id-ID')
            : '';
    }


    // =========================
    // UPDATE CUSTOMER
    // =========================

    function updateCustomer() {

        const option =
            customerSelect.options[
                customerSelect.selectedIndex
            ];


        if (!option || !option.value) {

            customerInfo.style.display = 'none';
            pinResetCard.style.display = 'none';
            pinResetSendCustomerId.value = '';
            pinResetConfirmCustomerId.value = '';

            balancePreview.style.display = 'none';

            balance = 0;

            return;

        }


        balance =
            Number(
                option.getAttribute('data-balance')
            ) || 0;

        const customerIsActive =
            option.getAttribute('data-active') === '1';

        pinResetCard.style.display = 'block';
        pinResetSendCustomerId.value = option.value;
        pinResetConfirmCustomerId.value = option.value;

        const withdrawalOption =
            transactionType.querySelector('option[value="penarikan"]');

        withdrawalOption.disabled = !customerIsActive;

        if (!customerIsActive) {
            transactionType.value = 'setoran';
        }


        accountNumber.textContent =
            option.getAttribute('data-account') || '-';


        customerName.textContent =
            option.getAttribute('data-name') || '-';


        currentBalance.textContent =
            formatRupiah(balance);


        customerInfo.style.display =
            'block';

        updateCustomerPinRequirement();

        calculateBalance();

    }


    // =========================
    // HITUNG SALDO
    // =========================

    function calculateBalance() {

        const amountValue =
            Number(amount.value.replace(/\D/g, '')) || 0;


        if (
            !customerSelect.value ||
            amountValue <= 0
        ) {

            balancePreview.style.display =
                'none';

            return;

        }

        let result;


        if (
            transactionType.value === 'setoran'
        ) {

            result =
                balance + amountValue;

        } else {

            result =
                balance - amountValue;

        }


        endingBalance.textContent =
            formatRupiah(result);


        // =========================
        // VALIDASI PENARIKAN
        // =========================

        if (
            transactionType.value === 'penarikan' &&
            result < 10000
        ) {

            balancePreview.className =
                'alert alert-danger';


            endingBalance.textContent =
                formatRupiah(result);


            const warning =
                document.createElement('div');

            warning.className =
                'small mt-1';

            warning.textContent =
                'Saldo tidak boleh kurang dari Rp 10.000.';


            // Hapus warning lama

            const oldWarning =
                balancePreview.querySelector(
                    '.small'
                );

            if (oldWarning) {
                oldWarning.remove();
            }


            balancePreview.appendChild(
                warning
            );

        } else {

            balancePreview.className =
                'alert alert-info';


            const oldWarning =
                balancePreview.querySelector(
                    '.small'
                );

            if (oldWarning) {
                oldWarning.remove();
            }

        }


        balancePreview.style.display =
            'block';

    }

    function updateCustomerPinRequirement() {
        const withdrawalSelected =
            transactionType.value === 'penarikan';

        customerPinGroup.style.display =
            withdrawalSelected ? 'block' : 'none';

        customerPin.disabled = !withdrawalSelected;
        customerPin.required = withdrawalSelected;

        if (!withdrawalSelected) {
            customerPin.value = '';
        }
    }


    // =========================
    // CARI CUSTOMER DENGAN
    // NOMOR REKENING
    // =========================

    function selectCustomerByAccount(account) {

        account =
            account.trim();


        let found = false;


        for (
            let i = 0;
            i < customerSelect.options.length;
            i++
        ) {

            const option =
                customerSelect.options[i];


            const optionAccount =
                option.getAttribute(
                    'data-account'
                );


            if (
                optionAccount &&
                optionAccount.trim() === account
            ) {

                customerSelect.value =
                    option.value;


                updateCustomer();


                found = true;


                break;

            }

        }


        return found;

    }


    // =========================
    // START SCANNER
    // =========================

    startScanner.addEventListener(
        'click',
        async function () {

            qrReaderWrapper.style.display =
                'block';


            startScanner.style.display =
                'none';


            stopScanner.style.display =
                'inline-block';


            scanResult.style.display =
                'block';


            scanResult.className =
                'alert alert-info mt-3';


            scanResult.innerHTML =
                '<i class="bi bi-camera"></i> ' +
                'Silakan arahkan kamera ke QR Code customer...';


            try {

                html5QrCode =
                    new Html5Qrcode('qrReader');


                await html5QrCode.start(

                    {
                        facingMode: 'environment'
                    },

                    {
                        fps: 10,

                        qrbox: {
                            width: 250,
                            height: 250
                        }

                    },

                    async function(decodedText) {

                        const account =
                            decodedText.trim();


                        const found =
                            selectCustomerByAccount(
                                account
                            );


                        if (found) {

                            scanResult.className =
                                'alert alert-success mt-3';


                            scanResult.innerHTML =
                                '<i class="bi bi-check-circle"></i> ' +
                                'Customer berhasil ditemukan.' +
                                '<br>' +
                                '<strong>Nomor Rekening:</strong> ' +
                                account;


                            await stopScannerFunction();

                        } else {

                            scanResult.className =
                                'alert alert-danger mt-3';


                            scanResult.innerHTML =
                                '<i class="bi bi-x-circle"></i> ' +
                                'Customer dengan nomor rekening ' +
                                '<strong>' +
                                account +
                                '</strong> tidak ditemukan.';

                        }

                    },

                    function(errorMessage) {

                        // Error scanning diabaikan

                    }

                );


                scannerRunning = true;


            } catch (error) {

                console.error(error);


                scanResult.className =
                    'alert alert-danger mt-3';


                scanResult.innerHTML =
                    '<i class="bi bi-exclamation-triangle"></i> ' +
                    'Tidak dapat mengakses kamera. ' +
                    'Pastikan browser memiliki izin kamera.';


                qrReaderWrapper.style.display =
                    'none';


                startScanner.style.display =
                    'inline-block';


                stopScanner.style.display =
                    'none';

            }

        }
    );


    // =========================
    // STOP SCANNER
    // =========================

    async function stopScannerFunction() {

        if (
            html5QrCode &&
            scannerRunning
        ) {

            try {

                await html5QrCode.stop();

                await html5QrCode.clear();

            } catch (error) {

                console.error(error);

            }

        }


        scannerRunning = false;


        qrReaderWrapper.style.display =
            'none';


        startScanner.style.display =
            'inline-block';


        stopScanner.style.display =
            'none';

    }


    stopScanner.addEventListener(
        'click',
        stopScannerFunction
    );


    // =========================
    // EVENT CUSTOMER
    // =========================

    customerSelect.addEventListener(
        'change',
        updateCustomer
    );


    // =========================
    // EVENT TIPE TRANSAKSI
    // =========================

    transactionType.addEventListener(
        'change',
        function () {
            updateCustomerPinRequirement();
            calculateBalance();
        }
    );


    // =========================
    // EVENT NOMINAL
    // =========================

    amount.addEventListener(
        'input',
        function () {
            amount.value = formatAmountInput(amount.value);
            calculateBalance();
        }
    );

    transactionForm.addEventListener('submit', function (event) {
        const selectedCustomer =
            customerSelect.options[customerSelect.selectedIndex];
        const transactionLabel =
            transactionType.value === 'setoran' ? 'Setoran' : 'Penarikan';
        const formattedAmount =
            formatRupiah(Number(amount.value.replace(/\D/g, '')) || 0);
        const confirmationMessage =
            `Apakah Anda yakin ingin menyimpan transaksi ini?\n\n` +
            `Jenis: ${transactionLabel}\n` +
            `Nasabah: ${selectedCustomer.dataset.name}\n` +
            `Jumlah: ${formattedAmount}`;

        if (!window.confirm(confirmationMessage)) {
            event.preventDefault();
            return;
        }

        amount.value = amount.value.replace(/\D/g, '');
    });


    // =========================
    // CUSTOMER DARI DASHBOARD
    // OTOMATIS DIPILIH
    // =========================

    updateCustomer();
    updateCustomerPinRequirement();

    if (new URLSearchParams(window.location.search).get('scan') === '1') {
        startScanner.click();
    }

});

</script>

@endsection