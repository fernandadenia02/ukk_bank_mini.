
    document.addEventListener('DOMContentLoaded', function () {
        const openingDeposit = document.getElementById('opening_deposit');

        function formatAmount(value) {
            const digits = value.replace(/\D/g, '');
            return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        openingDeposit.value = formatAmount(openingDeposit.value);
        openingDeposit.addEventListener('input', function () {
            const cursor = openingDeposit.selectionStart;
            const digitsBeforeCursor = openingDeposit.value
                .slice(0, cursor)
                .replace(/\D/g, '')
                .length;
            const formatted = formatAmount(openingDeposit.value);
            openingDeposit.value = formatted;

            let nextCursor = 0;
            let digitsCount = 0;
            while (nextCursor < formatted.length && digitsCount < digitsBeforeCursor) {
                if (/\d/.test(formatted[nextCursor])) {
                    digitsCount++;
                }
                nextCursor++;
            }
            openingDeposit.setSelectionRange(nextCursor, nextCursor);
        });
    });
