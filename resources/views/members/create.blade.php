{{-- Formulir untuk mendaftarkan data calon nasabah/anggota. --}}
@extends('layouts.app')

@section('title', 'Add Anggota')

@section('content')
<h2>Tambah Anggota</h2>
<p class="text-muted">Masukkan data calon nasabah. Informasi kontak dan rekening diisi saat pembuatan rekening.</p>

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
        <form action="{{ route('members.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="nis" class="form-label">NIS</label>
                <input type="text" id="nis" name="nis" class="form-control"
                    value="{{ old('nis') }}" maxlength="30" required>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Nama</label>
                <input type="text" id="name" name="name" class="form-control"
                    value="{{ old('name') }}" maxlength="100" required>
            </div>

            <div class="mb-3">
                <label for="class" class="form-label">Kelas</label>
                <input type="text" id="class" name="class" class="form-control"
                    value="{{ old('class') }}" maxlength="20" required>
            </div>

            <div class="mb-3">
                <label for="location" class="form-label">Lokasi</label>
                <textarea id="location" name="location" class="form-control" rows="3" required>{{ old('location') }}</textarea>
            </div>

            <button type="submit" class="btn btn-success">Simpan Anggota</button>
            <a href="{{ route('members.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>
@endsection
