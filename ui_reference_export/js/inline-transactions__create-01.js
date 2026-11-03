

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

