
    document.addEventListener('DOMContentLoaded', function () {
        const physicalMoney = document.getElementById('physical_money');
        if (!physicalMoney) {
            return;
        }

        physicalMoney.addEventListener('input', function () {
            const digits = physicalMoney.value.replace(/\D/g, '');
            physicalMoney.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
    });
