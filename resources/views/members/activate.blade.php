{{-- Formulir pembukaan rekening anggota dengan pengisian data akses dan setoran awal. --}}
@extends('layouts.app')

@section('title', 'Buat Rekening')

@section('content')
<h2>Buka Rekening dari Anggota</h2>
<p class="text-muted">Lengkapi data rekening dan setoran awal calon nasabah.</p>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-3"><strong>NIS</strong><div>{{ $member->nis }}</div></div>
            <div class="col-md-3"><strong>Nama</strong><div>{{ $member->name }}</div></div>
            <div class="col-md-3"><strong>Kelas</strong><div>{{ $member->class }}</div></div>
            <div class="col-md-3"><strong>Lokasi</strong><div>{{ $member->location }}</div></div>
        </div>

        <form action="{{ route('teller.members.open', $member->id) }}" method="POST"
            data-ui-confirm-title="Konfirmasi pembukaan rekening"
            data-ui-confirm="Buka rekening dan catat setoran awal?"
            data-ui-confirm-value="#opening_deposit"
            data-ui-confirm-value-label="Setoran awal"
            data-ui-confirm-accept="Buka rekening">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                    value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
            </div>

            <div class="mb-3">
                <label for="telephone_number" class="form-label">Nomor Telepon</label>
                <input type="text" id="telephone_number" name="telephone_number" class="form-control"
                    value="{{ old('telephone_number') }}" maxlength="20" inputmode="tel" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password Login</label>
                <input type="password" id="password" name="password" class="form-control"
                    minlength="12" maxlength="255" data-strong-password autocomplete="new-password" required>
                @include('components.password-requirements')
            </div>

            <div class="mb-3">
                <label for="pin" class="form-label">PIN Transaksi</label>
                <input type="password" id="pin" name="pin" class="form-control"
                    inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6"
                    autocomplete="new-password" required>
                <small class="text-muted">PIN harus terdiri dari 6 angka.</small>
            </div>

            <div class="mb-3">
                <label for="opening_deposit" class="form-label">Setoran Awal</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="text" id="opening_deposit" name="opening_deposit" class="form-control"
                        value="{{ old('opening_deposit', '10.000') }}" inputmode="numeric"
                        pattern="[0-9.]*" autocomplete="off" required>
                </div>
                <small class="text-muted">Setoran awal minimal Rp 10.000 dan akan tercatat sebagai transaksi setoran.</small>
            </div>

            <div class="alert alert-info">
                Setelah setoran berhasil dicatat, rekening langsung aktif dengan saldo sebesar setoran awal.
                Data anggota tetap tersimpan di daftar Anggota untuk pemeriksaan.
            </div>

            <button type="submit" class="btn btn-success">Buka Rekening dan Simpan Setoran</button>
            <a href="{{ route('teller.members.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>

<script>
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
</script>
@endsection
