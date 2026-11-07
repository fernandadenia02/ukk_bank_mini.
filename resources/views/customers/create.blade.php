{{-- Formulir untuk menambahkan data rekening nasabah baru. --}}
@extends('layouts.app')

@section('title', 'Create Customer')

@section('content')

<h2>Create Customer</h2>

<hr>

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card shadow">

    <div class="card-body">

        <form action="{{ route('customers.store') }}" method="POST">

            @csrf

            <div class="mb-3">
                <label>Account Number</label>

                <input
                    type="text"
                    name="account_number"
                    class="form-control"
                    value="{{ $accountNumber }}"
                    readonly>
            </div>

            <div class="mb-3">
                <label>NIS</label>

                <input
                    type="text"
                    name="nis"
                    class="form-control"
                    required>
            </div>

            <div class="mb-3">
                <label>Customer Name</label>

                <input
                    type="text"
                    name="name"
                    class="form-control"
                    required>
            </div>

            <div class="mb-3">
                <label for="email">Email</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    value="{{ old('email') }}"
                    maxlength="255"
                    autocomplete="email"
                    required>

                <small class="text-muted">
                    Email ini digunakan untuk menerima kode OTP saat lupa password.
                </small>
            </div>

            <div class="mb-3">
                <label>Class</label>

                <input
                    type="text"
                    name="class"
                    class="form-control"
                    required>
            </div>

            <div class="mb-3">
                <label>Location</label>

                <textarea
                    name="location"
                    class="form-control"
                    rows="3"
                    required></textarea>
            </div>

            <div class="mb-3">
                <label>Telephone Number</label>

                <input
                    type="text"
                    name="telephone_number"
                    class="form-control"
                    required>
            </div>

            <div class="mb-3">
                <label for="password">Password Login</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    minlength="4"
                    autocomplete="new-password"
                    required>

                <small class="text-muted">
                    Password ini digunakan untuk login customer dan berbeda dari PIN transaksi.
                    Minimal 4 karakter.
                </small>
            </div>

            <div class="mb-3">
                <label>PIN</label>

                <input
                    type="password"
                    name="pin"
                    class="form-control"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    minlength="6"
                    maxlength="6"
                    autocomplete="new-password"
                    required>

                <small class="text-muted">
                    PIN harus terdiri dari 6 angka.
                </small>
            </div>

            <div class="alert alert-info">
                Customer baru dibuat dalam status nonaktif dengan saldo Rp 0.
                Akun akan aktif setelah setoran pertama minimal Rp 10.000.
            </div>

            <button class="btn btn-success">
                Save Customer
            </button>

            <a href="{{ route('customers.index') }}"
               class="btn btn-secondary">
                Back
            </a>

        </form>

    </div>

</div>

@endsection