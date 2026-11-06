@extends('layouts.auth')

@section('title', 'Masuk | Bank Mini Sekolah')

@push('styles')
    <link href="{{ asset('css/login.css') }}" rel="stylesheet">
@endpush

@section('content')
<main class="login-page">
    <section class="login-card" aria-labelledby="login-title">
        <header class="login-brand">
            <img
                src="{{ asset('images/logo-smkn-1-bantul.png') }}"
                alt="Logo SMK Negeri 1 Bantul"
                class="login-logo">
            <div>
                <p class="login-brand-name">BANK MINI SEKOLAH</p>
                <p class="login-brand-caption">SMK Negeri 1 Bantul</p>
            </div>
        </header>

        <div class="login-content">
            <div class="login-heading">
                <span class="login-eyebrow">PORTAL PETUGAS</span>
                <h1 id="login-title">Selamat Datang</h1>
                <p>Masuk untuk melanjutkan ke sistem Bank Mini.</p>
            </div>

            @if(session('error'))
                <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
            @endif

            @if(session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('login.authenticate') }}" method="POST" class="login-form">
                @csrf

                <div class="mb-3">
                    <label for="username" class="form-label">Username Petugas</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control login-input"
                        value="{{ old('username') }}"
                        placeholder="Masukkan username"
                        autocomplete="username"
                        required
                        autofocus>
                </div>

                <div class="mb-2">
                    <div class="login-password-label">
                        <label for="password" class="form-label">Kata Sandi</label>
                        <a href="{{ route('password.request') }}">Lupa kata sandi?</a>
                    </div>
                    <div class="login-password-input">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control login-input"
                            placeholder="Masukkan kata sandi"
                            autocomplete="current-password"
                            required>
                        <button
                            type="button"
                            class="login-password-toggle"
                            data-password-toggle
                            aria-label="Lihat kata sandi"
                            aria-pressed="false">
                            Lihat
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn login-submit w-100">
                    Masuk ke Sistem
                </button>
            </form>

            <section class="login-download" aria-labelledby="download-title">
                <div class="login-download-copy">
                    <span class="login-download-mark" aria-hidden="true">↓</span>
                    <div>
                        <h2 id="download-title">Untuk Nasabah Siswa</h2>
                        <p>Akses layanan tabungan secara mandiri melalui aplikasi nasabah.</p>
                    </div>
                </div>
                <a
                    href="https://bank-mini-fernanda.web.id/downloads/app-release.apk"
                    class="btn login-download-button w-100"
                    download>
                    Unduh Aplikasi Android (.APK)
                </a>
            </section>
        </div>

        <footer class="login-footer">
            <span>Bank Mini Sekolah</span>
            <span>&copy; {{ date('Y') }} SMK Negeri 1 Bantul</span>
        </footer>
    </section>
</main>

<script>
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.querySelector('[data-password-toggle]');

    if (passwordInput instanceof HTMLInputElement && passwordToggle instanceof HTMLButtonElement) {
        passwordToggle.addEventListener('click', () => {
            const isVisible = passwordInput.type === 'text';
            passwordInput.type = isVisible ? 'password' : 'text';
            passwordToggle.textContent = isVisible ? 'Lihat' : 'Sembunyikan';
            passwordToggle.setAttribute('aria-label', isVisible ? 'Lihat kata sandi' : 'Sembunyikan kata sandi');
            passwordToggle.setAttribute('aria-pressed', String(!isVisible));
        });
    }
</script>

@endsection