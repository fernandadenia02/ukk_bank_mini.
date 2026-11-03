@extends('layouts.app')

@section('title', 'Naik Kelas')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="mb-1">Naik Kelas</h2>
        <p class="text-muted mb-0">
            Siswa kelas X naik ke XI, XI naik ke XII, dan XII menjadi ALUMNI.
            Centang siswa yang ingin tetap di kelas saat ini.
        </p>
    </div>
    <span class="badge bg-primary fs-6">{{ $members->count() }} siswa dapat diproses</span>
</div>

<div class="alert alert-warning">
    <strong>Perhatian:</strong> Proses ini mengubah kelas semua siswa yang tidak dicentang.
    Akun nasabah yang menjadi alumni otomatis dinonaktifkan dan token API-nya dicabut.
</div>

@if($errors->has('stay_ids'))
    <div class="alert alert-danger">{{ $errors->first('stay_ids') }}</div>
@endif

<form action="{{ route('members.promote') }}" method="POST"
    onsubmit="return confirm('Lanjutkan proses naik kelas untuk semua siswa yang tidak dicentang?');">
    @csrf

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <p class="text-muted mb-0">Siswa yang dicentang akan tetap di kelasnya.</p>
        <button type="submit" class="btn btn-primary" @disabled($members->isEmpty())>
            
            Naikkan Kelas
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Kelas Saat Ini</th>
                    <th>Menjadi</th>
                    <th class="text-center">Tetap di kelas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                    <tr>
                        <td>{{ $member->nis }}</td>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->class }}</td>
                        <td>{{ $nextClasses[$member->id] }}</td>
                        <td class="text-center">
                            <input class="form-check-input" type="checkbox" name="stay_ids[]"
                                value="{{ $member->id }}" aria-label="Tetapkan {{ $member->name }} di kelas {{ $member->class }}">
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            Tidak ada siswa di kelas X, XI, atau XII yang perlu diproses.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</form>
@endsection
