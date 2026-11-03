@extends('layouts.auth')

@section('title', 'Login')

@section('content')

<div class="container mt-5" style="max-width:450px;">

    <div class="card shadow">

        <div class="card-header text-center bg-primary text-white">
            <h4>Mini Bank Login</h4>
            <p class="mb-0">Sistem Informasi Bank Mini</p>
        </div>

        <div class="card-body">

            {{-- Pesan login gagal --}}
            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Pesan berhasil reset password --}}
            @if(session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Pesan validasi --}}
            @if($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Form Login --}}
            <form action="{{ route('login.authenticate') }}" method="POST">

                @csrf

                <div class="mb-3">
                    <label for="username" class="form-label">
                        Username
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        value="{{ old('username') }}"
                        placeholder="Masukkan username"
                        autocomplete="username"
                        required
                        autofocus>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Login
                </button>

            </form>

            {{-- Lupa Password --}}
            <div class="text-center mt-3">
                <a href="{{ route('password.request') }}"
                   class="text-decoration-none">
                    Lupa Password?
                </a>
            </div>

        </div>

        <div class="card-footer text-center text-muted">
            <small>Mini Bank &copy; {{ date('Y') }}</small>
        </div>

    </div>

</div>

@endsection