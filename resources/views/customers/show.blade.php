@extends('layouts.app')

@section('title', 'Detail Customer')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Detail Customer</h2>
        <p class="text-muted mb-0">Informasi rekening dan profil customer.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
            
            Kembali
        </a>
        <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-primary">
            
            Edit Customer
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <section class="card h-100">
            <div class="card-header bg-primary text-white">
                
                Informasi Customer
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 py-2">Nama</dt>
                    <dd class="col-sm-8 py-2">{{ $customer->name }}</dd>

                    <dt class="col-sm-4 py-2">NIS</dt>
                    <dd class="col-sm-8 py-2">{{ $customer->nis }}</dd>

                    <dt class="col-sm-4 py-2">Kelas</dt>
                    <dd class="col-sm-8 py-2">{{ $customer->class }}</dd>

                    <dt class="col-sm-4 py-2">Email</dt>
                    <dd class="col-sm-8 py-2">{{ $customer->email ?: '-' }}</dd>

                    <dt class="col-sm-4 py-2">Nomor Telepon</dt>
                    <dd class="col-sm-8 py-2">{{ $customer->telephone_number ?: '-' }}</dd>

                    <dt class="col-sm-4 py-2">Lokasi</dt>
                    <dd class="col-sm-8 py-2">{{ $customer->location ?: '-' }}</dd>
                </dl>
            </div>
        </section>
    </div>

    <div class="col-lg-4">
        <section class="card h-100">
            <div class="card-header">Informasi Rekening</div>
            <div class="card-body text-center">
                <div class="small text-muted mb-1">Nomor Rekening</div>
                <div class="fs-4 fw-bold mb-3">{{ $customer->account_number }}</div>

                <div class="small text-muted mb-1">Saldo</div>
                <div class="fs-4 fw-bold text-primary mb-3">
                    Rp {{ number_format($customer->balance, 0, ',', '.') }}
                </div>

                <div class="mb-3">
                    @if($customer->status === 'aktif')
                        <span class="badge bg-success">Aktif</span>
                    @else
                        <span class="badge bg-secondary">Nonaktif</span>
                    @endif
                </div>

                @if($customer->qrcode)
                    <img
                        src="{{ asset('qrcode/'.$customer->qrcode) }}"
                        alt="QR rekening {{ $customer->account_number }}"
                        class="img-fluid border rounded p-2"
                        width="180"
                    >
                @else
                    <p class="text-muted mb-0">QR rekening belum tersedia.</p>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
