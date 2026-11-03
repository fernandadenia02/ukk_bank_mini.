@extends('layouts.app')

@section('title', 'Daily Reports')

@section('content')

<h2>Daily Reports</h2>

<hr>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Gagal memproses laporan:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">

        <thead class="table-dark">
            <tr>
                <th>No</th>
                <th>Date</th>
                <th>Teller</th>
                <th>Deposit</th>
                <th>Withdrawal</th>
                <th>Ending Balance</th>
                <th>Kas</th>
                <th>Status</th>
                <th>Alasan Reject</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
        @forelse($reports as $report)
            <tr>
                <td>{{ $loop->iteration }}</td>

                <td>{{ $report->date }}</td>

                <td>{{ $report->teller->officer_name ?? '-' }}</td>

                <td>
                    Rp {{ number_format($report->total_deposit, 0, ',', '.') }}
                </td>

                <td>
                    Rp {{ number_format($report->total_withdrawals, 0, ',', '.') }}
                </td>

                <td>
                    Rp {{ number_format($report->ending_balance, 0, ',', '.') }}
                </td>

                <td>
                    @if($report->closed_at)
                        <span class="badge bg-secondary">Ditutup</span>
                        <div class="small text-muted">{{ $report->closed_at->format('d-m-Y H:i') }}</div>
                        <div class="small">
                            Fisik Rp {{ number_format($report->physical_money, 0, ',', '.') }}
                            <br>
                            Selisih Rp {{ number_format($report->difference, 0, ',', '.') }}
                        </div>
                    @else
                        <span class="badge bg-warning text-dark">Menunggu teller tutup kas</span>
                    @endif
                </td>

                {{-- STATUS --}}
                <td>
                    @if($report->status === 'pending')
                        <span class="badge bg-warning text-dark">
                            Pending
                        </span>

                    @elseif($report->status === 'approved')
                        <span class="badge bg-success">
                            Approved
                        </span>

                    @elseif($report->status === 'rejected')
                        <a
                            href="{{ auth()->user()->role === 'teller'
                                ? route('teller.daily-reports.show', $report->id)
                                : route('daily-reports.show', $report->id) }}"
                            class="badge bg-danger text-white text-decoration-none"
                            title="Klik untuk melihat alasan penolakan"
                        >
                            Rejected
                        </a>

                    @else
                        <span class="badge bg-secondary">
                            {{ $report->status }}
                        </span>
                    @endif
                </td>

                {{-- ALASAN PENOLAKAN --}}
                <td style="min-width: 200px;">
                    @if($report->status === 'rejected')
                        @if(filled($report->rejection_reason))
                            <span style="white-space: pre-line;">{{ $report->rejection_reason }}</span>
                        @else
                            <span class="text-muted">
                                Alasan belum tersedia.
                            </span>
                        @endif
                    @else
                        -
                    @endif
                </td>

                {{-- ACTION --}}
                <td style="min-width: 220px;">

                    <a
                        href="{{ auth()->user()->role === 'teller'
                            ? route('teller.daily-reports.show', $report->id)
                            : route('daily-reports.show', $report->id) }}"
                        class="btn btn-info btn-sm text-white mb-1"
                    >
                        Detail
                    </a>

                    @if(auth()->user()->role === 'admin')

                        @if($report->status === 'pending' && $report->closed_at)

                            <form
                                action="{{ route('daily-reports.approve', $report->id) }}"
                                method="POST"
                                class="d-inline"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="btn btn-success btn-sm mb-1"
                                    onclick="return confirm('Apakah Anda yakin ingin menyetujui laporan ini?')"
                                >
                                    Approve
                                </button>
                            </form>

                            <form
                                action="{{ route('daily-reports.reject', $report->id) }}"
                                method="POST"
                                class="mt-2"
                            >
                                @csrf

                                <label
                                    for="reason-{{ $report->id }}"
                                    class="form-label"
                                >
                                    Alasan penolakan
                                </label>

                                <textarea
                                    id="reason-{{ $report->id }}"
                                    name="rejection_reason"
                                    class="form-control form-control-sm mb-2"
                                    rows="3"
                                    minlength="5"
                                    maxlength="2000"
                                    placeholder="Tuliskan alasan reject..."
                                    required
                                >{{ old('rejection_reason') }}</textarea>

                                <button
                                    type="submit"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Yakin menolak laporan ini dengan alasan tersebut?')"
                                >
                                    Reject
                                </button>
                            </form>

                        @elseif($report->status === 'pending')
                            <div class="text-muted small mt-1">
                                Menunggu kas ditutup teller. Aksi review dan catatan admin belum tersedia.
                            </div>
                        @else
                            <div class="text-muted small mt-1">
                                Sudah diproses
                            </div>
                        @endif

                    @endif

                </td>
            </tr>

        @empty
            <tr>
                <td colspan="10" class="text-center">
                    Belum ada Daily Report.
                </td>
            </tr>
        @endforelse
        </tbody>

    </table>
</div>

@endsection
