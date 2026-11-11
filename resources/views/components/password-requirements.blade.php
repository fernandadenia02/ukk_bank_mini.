@php($inputId = $inputId ?? 'password')

<div class="mt-2" id="{{ $inputId }}-requirements" aria-live="polite">
    <small class="text-muted d-block">Password harus memenuhi semua ketentuan:</small>
    <ul class="list-unstyled mb-0 small">
        <li data-password-rule="length">Minimal 12 karakter</li>
        <li data-password-rule="uppercase">Memiliki huruf kapital (A-Z)</li>
        <li data-password-rule="number">Memiliki angka (0-9)</li>
        <li data-password-rule="symbol">Memiliki simbol khusus (contoh: !@#$%)</li>
    </ul>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('input[type="password"][data-strong-password]').forEach(function (input) {
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });

        document.addEventListener('input', function (event) {
            if (!event.target.matches('input[type="password"][data-strong-password]')) {
                return;
            }

            const password = event.target.value;
            const requirements = document.getElementById(event.target.id + '-requirements');
            if (!requirements) {
                return;
            }

            const checks = {
                length: password.length >= 12,
                uppercase: /[A-Z]/.test(password),
                number: /[0-9]/.test(password),
                symbol: /[^A-Za-z0-9]/.test(password),
            };

            Object.entries(checks).forEach(function ([rule, passed]) {
                const item = requirements.querySelector('[data-password-rule="' + rule + '"]');
                item.classList.toggle('text-success', passed);
                item.classList.toggle('text-danger', password.length > 0 && !passed);
            });
        });
    </script>
@endonce
