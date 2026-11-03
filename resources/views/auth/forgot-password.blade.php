@extends('layouts.auth')

@section('title', 'Lupa Password')

@section('content')
<div class="container mt-5" style="max-width: 450px;">
    <div class="card shadow">
        <div class="card-header bg-primary text-white text-center">
            <h4>Lupa Password</h4>
            <p class="mb-0">Pemulihan akun Bank Mini</p>
        </div>

        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-info">
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
                Masukkan ID database admin/teller atau nomor rekening customer,
                beserta email yang terdaftar pada akun.
            </p>

            <form action="{{ route('password.otp.send') }}" method="POST">
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

                <button type="submit" class="btn btn-primary w-100">
                    Kirim Kode OTP
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('login') }}">Kembali ke Login</a>
            </div>
        </div>
    </div>
</div>
@endsection
