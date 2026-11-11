{{-- Formulir untuk mengubah data anggota dan menyinkronkan identitas rekening terkait. --}}
@extends('layouts.app')

@section('title', 'Edit Anggota')

@section('content')
<h2>Edit Anggota</h2>
<p class="text-muted">
    Perubahan NIS, nama, kelas, dan lokasi juga diterapkan pada rekening anggota jika sudah dibuka.
</p>

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
        <form action="{{ route('members.update', $member->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="nis" class="form-label">NIS</label>
                <input type="text" id="nis" name="nis" class="form-control"
                    value="{{ old('nis', $member->nis) }}" maxlength="30" required>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Nama</label>
                <input type="text" id="name" name="name" class="form-control"
                    value="{{ old('name', $member->name) }}" maxlength="100" required>
            </div>

            <div class="mb-3">
                <label for="class" class="form-label">Kelas</label>
                <input type="text" id="class" name="class" class="form-control"
                    value="{{ old('class', $member->class) }}" maxlength="20" required>
            </div>

            <div class="mb-3">
                <label for="location" class="form-label">Lokasi</label>
                <textarea id="location" name="location" class="form-control" rows="3" required>{{ old('location', $member->location) }}</textarea>
            </div>

            <button type="submit" class="btn btn-success">Simpan Perubahan</button>
            <a href="{{ route('members.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>
@endsection
