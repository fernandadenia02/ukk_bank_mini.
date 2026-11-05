@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<div class="container mt-5" style="max-width: 450px;">
    <div class="card shadow">
        <div class="card-header bg-primary text-white text-center">
            <h4>Reset Password</h4>
        </div>

        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p>
                Masukkan kembali ID admin/teller atau nomor rekening customer,
                email terdaftar, kode OTP 6 digit dari email, dan password baru minimal 4 karakter.
            </p>

            <form action="{{ route('password.update') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="identity" class="form-label">
                        ID Admin/Teller atau Nomor Rekening Customer
                    </label>

                    <input
                        type="text"
                        id="identity"
                        name="identity"
                        class="form-control"
                        value="{{ old('identity') }}"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">
                        Alamat Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="{{ old('email') }}"
                        maxlength="255"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="otp" class="form-label">Kode OTP</label>

                    <input
                        type="text"
                        id="otp"
                        name="otp"
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
                    <label for="password" class="form-label">
                        Password Baru
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        minlength="4"
                        autocomplete="new-password"
                        required
                    >
                    <small class="text-muted">Minimal 4 karakter.</small>
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">
                        Konfirmasi Password Baru
                    </label>

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        class="form-control"
                        minlength="4"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-success w-100">
                    Ubah Password
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('password.request') }}">
                    Minta OTP baru
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
