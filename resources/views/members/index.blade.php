@extends('layouts.app')

@section('title', 'Anggota')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="mb-1">Data Anggota</h2>
        <p class="text-muted mb-0">
            {{ $forTeller ? 'Pilih calon nasabah untuk membuka rekening dengan setoran awal minimal Rp 10.000.' : 'Daftar calon nasabah dan riwayat pembukaan rekening.' }}
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary fs-6">{{ $members->total() }} anggota</span>
        @unless($forTeller)
            <a href="{{ route('members.create') }}" class="btn btn-primary">
                
                Add Anggota
            </a>
        @endunless
    </div>
</div>

@if(session('import_errors'))
    <div class="alert alert-danger">
        <strong>Import dibatalkan. Tidak ada anggota yang disimpan:</strong>
        <ul class="mb-0 mt-2">
            @foreach(session('import_errors') as $importError)
                <li>{{ $importError }}</li>
            @endforeach
        </ul>
    </div>
@endif

@unless($forTeller)
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5>Import Calon Nasabah dari Excel</h5>
        <p class="text-muted">
            File anggota hanya memuat NIS, nama, kelas, dan lokasi. Email, nomor telepon,
            password login, serta PIN dimasukkan teller saat membuka rekening.
        </p>
        <div class="d-flex flex-wrap gap-2 align-items-start">
            <a href="{{ route('members.import.template') }}" class="btn btn-outline-primary">
                
                Unduh Template Anggota
            </a>
            <form action="{{ route('members.import') }}" method="POST" enctype="multipart/form-data"
                class="d-flex flex-wrap gap-2">
                @csrf
                <input type="file" name="file" class="form-control" accept=".xlsx" required>
                <button type="submit" class="btn btn-success">
                    
                    Upload Anggota
                </button>
            </form>
        </div>
        @error('file')
            <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>
</div>
@endunless

<form action="{{ $forTeller ? route('teller.members.index') : route('members.index') }}"
    method="GET" class="d-flex gap-2 mb-3">
    <input type="search" name="keyword" class="form-control" placeholder="Cari NIS, nama, atau kelas..."
        value="{{ $keyword }}">
    <button class="btn btn-primary">Cari</button>
    <a href="{{ route('members.index') }}" class="btn btn-secondary">Reset</a>
</form>

<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Lokasi</th>
                @if($forTeller)
                    <th>Aksi</th>
                @else
                    <th>Nomor Rekening</th>
                    <th>Status Rekening</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($members as $member)
                <tr>
                    <td>{{ ($members->currentPage() - 1) * $members->perPage() + $loop->iteration }}</td>
                    <td>{{ $member->nis }}</td>
                    <td>{{ $member->name }}</td>
                    <td>{{ $member->class }}</td>
                    <td>{{ $member->location }}</td>
                    @if($forTeller)
                        <td>
                            <a href="{{ route('teller.members.open.form', $member->id) }}" class="btn btn-success btn-sm">
                                
                                Buka Rekening
                            </a>
                        </td>
                    @else
                        <td>{{ $member->customer->account_number ?? '-' }}</td>
                        <td>
                            @if($member->customer)
                                @if($member->customer->status === 'aktif')
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            @else
                                <span class="badge bg-danger text-white">Belum dibuka</span>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $forTeller ? 6 : 7 }}" class="text-center py-4">
                        {{ $forTeller ? 'Tidak ada calon nasabah yang menunggu pembukaan rekening.' : 'Belum ada data anggota.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center">
    {{ $members->links() }}
</div>
@endsection
