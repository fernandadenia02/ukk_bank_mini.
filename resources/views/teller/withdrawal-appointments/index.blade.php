@extends('layouts.app')

@section('title', 'Riwayat Janji Penarikan')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h2 class="fw-bold">Riwayat Janji Penarikan</h2>
        <p class="text-muted mb-0">Kelola janji penarikan nasabah di atas Rp 1.000.000.</p>
    </div>

    <div class="card shadow-sm border-warning">
        <div class="card-header bg-warning-subtle">
            <h5 class="mb-0">Daftar Pengajuan</h5>
        </div>
        <div class="card-body">
            @if($withdrawalAppointments->isEmpty())
                <p class="text-muted mb-0">Belum ada pengajuan janji penarikan.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nasabah</th>
                                <th>Tanggal Pengambilan</th>
                                <th>Nominal</th>
                                <th>Waktu Pengajuan</th>
                                <th>Status</th>
                                <th>Catatan Penolakan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($withdrawalAppointments as $appointment)
                                <tr>
                                    <td>
                                        <strong>{{ $appointment->customer->name }}</strong>
                                        <div class="small text-muted">{{ $appointment->customer->account_number }}</div>
                                    </td>
                                    <td>{{ $appointment->withdrawal_date->format('d-m-Y') }}</td>
                                    <td>Rp {{ number_format($appointment->amount, 0, ',', '.') }}</td>
                                    <td>{{ $appointment->created_at->timezone('Asia/Jakarta')->format('d-m-Y H:i') }} WIB</td>
                                    <td>
                                        @if($appointment->status === 'pending')
                                            <span class="badge bg-warning text-dark">Menunggu</span>
                                        @elseif($appointment->status === 'approved')
                                            <span class="badge bg-success">Disetujui</span>
                                            @if($appointment->reviewer)
                                                <div class="small text-muted">oleh {{ $appointment->reviewer->officer_name }}</div>
                                            @endif
                                        @elseif($appointment->status === 'rejected')
                                            <span class="badge bg-danger">Ditolak</span>
                                        @elseif($appointment->status === 'expired')
                                            <span class="badge bg-secondary">Kedaluwarsa</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($appointment->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($appointment->status === 'rejected')
                                            {{ $appointment->rejection_reason ?: '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($appointment->status === 'pending')
                                            <div class="d-flex flex-wrap gap-2">
                                                <form action="{{ route('teller.withdrawal-appointments.approve', $appointment->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm">Setujui</button>
                                                </form>
                                                <form action="{{ route('teller.withdrawal-appointments.reject', $appointment->id) }}" method="POST"
                                                    class="d-flex flex-column gap-1">
                                                    @csrf
                                                    <textarea name="rejection_reason" class="form-control form-control-sm" rows="2"
                                                        minlength="5" maxlength="2000" placeholder="Catatan penolakan..." required>{{ old('rejection_reason') }}</textarea>
                                                    @error('rejection_reason')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Tolak</button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $withdrawalAppointments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
