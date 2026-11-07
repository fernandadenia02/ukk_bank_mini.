{{-- Daftar laporan kas harian teller untuk ditinjau dan diproses oleh admin. --}}
@extends('layouts.app')

@section('title', 'Daily Reports')

@section('content')

<style>
    /* Styling for the daily report list, status labels, and responsive table. */
    .daily-report-page {
        background: #eef3f7;
        border-radius: 0;
        padding: 0;
    }

    .daily-report-header {
        background: #edf1f5;
        border: 1px solid #dfe7ee;
        border-left: none;
        border-right: none;
        padding: 18px 18px 20px;
        margin-bottom: 0;
    }

    .daily-report-header h2 {
        margin: 0;
        font-weight: 800;
        font-size: clamp(2.2rem, 3vw, 3.2rem);
        color: #1a2b3d;
        letter-spacing: -0.04em;
    }

    .report-table-wrap {
        background: #eef3f7;
        border: 1px solid #dfe7ee;
        border-top: none;
        overflow: hidden;
    }

    .report-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
        background: transparent;
    }

    .report-table thead th {
        background: #122b45;
        color: #f5f7fb;
        font-weight: 700;
        font-size: 0.95rem;
        padding: 16px 12px;
        text-align: center;
        border: 1px solid rgba(255,255,255,0.12);
        vertical-align: middle;
    }

    .report-table tbody td {
        background: rgba(255,255,255,0.05);
        color: #1f2d3d;
        padding: 14px 12px;
        border: 1px solid #dfe7ee;
        vertical-align: middle;
        text-align: center;
        font-size: 0.96rem;
    }

    .report-table tbody tr:nth-child(even) td {
        background: rgba(255,255,255,0.12);
    }

    .report-table td.date-cell {
        text-align: left;
        min-width: 110px;
    }

    .report-table td.action-cell {
        min-width: 170px;
        text-align: center;
    }

    .status-pill {
        display: inline-block;
        min-width: 120px;
        padding: 0.38rem 0.9rem;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 700;
        line-height: 1.2;
        text-align: center;
    }

    .status-pending {
        background: #f0c54d;
        color: #1a2b3d;
    }

    .status-approved {
        background: #2bb673;
        color: white;
    }

    .status-rejected {
        background: #e55353;
        color: white;
    }

    .status-closed {
        background: #5d7a99;
        color: white;
    }

    .detail-button {
        display: inline-block;
        min-width: 96px;
        padding: 0.7rem 1.15rem;
        border: none;
        border-radius: 10px;
        background: linear-gradient(180deg, #2cc5ef, #1eaed8);
        color: #fff;
        font-weight: 700;
        font-size: 0.9rem;
        text-decoration: none;
        box-shadow: 0 8px 18px rgba(30, 174, 216, 0.24);
    }

    .detail-button:hover {
        color: #fff;
        text-decoration: none;
        filter: brightness(1.02);
    }

    .report-meta {
        color: #4c5d72;
        font-size: 0.84rem;
    }

    .report-empty {
        padding: 30px 16px;
        text-align: center;
        color: #60738a;
        background: #f5f8fb;
    }

    @media (max-width: 991.98px) {
        .report-table-wrap {
            overflow-x: auto;
        }

        .report-table {
            min-width: 1100px;
        }
    }
</style>

<div class="daily-report-page">
    <div class="daily-report-header">
        <h2>Daily Reports</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success mt-3 mx-3 mb-0">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger mt-3 mx-3 mb-0">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mt-3 mx-3 mb-0">
            <strong>Gagal memproses laporan:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="report-table-wrap mt-3">
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 70px;">No</th>
                    <th style="width: 120px;">Date</th>
                    <th style="width: 150px;">Teller</th>
                    <th style="width: 130px;">Modal Awal</th>
                    <th style="width: 110px;">Deposit</th>
                    <th style="width: 140px;">Withdrawal</th>
                    <th style="width: 150px;">Ending Balance</th>
                    <th style="width: 150px;">Kas</th>
                    <th style="width: 130px;">Status</th>
                    <th style="width: 180px;">Alasan Reject</th>
                    <th style="width: 120px;">Action</th>
                </tr>
            </thead>

            <tbody>
            @forelse($reports as $report)
                <tr>
                    <td>{{ $loop->iteration }}</td>

                    <td class="date-cell">{{ $report->date }}</td>

                    <td>{{ $report->teller->officer_name ?? '-' }}</td>

                    <td>Rp {{ number_format($report->beginning_balance, 0, ',', '.') }}</td>

                    <td>Rp {{ number_format($report->total_deposit, 0, ',', '.') }}</td>

                    <td>Rp {{ number_format($report->total_withdrawals, 0, ',', '.') }}</td>

                    <td>Rp {{ number_format($report->ending_balance, 0, ',', '.') }}</td>

                    <td>
                        @if($report->closed_at)
                            <div class="status-pill status-closed">Ditutup</div>
                            <div class="report-meta mt-2">{{ $report->closed_at->timezone('Asia/Jakarta')->format('d-m-Y H:i') }} WIB</div>
                            <div class="report-meta mt-1">Fisik Rp {{ number_format($report->physical_money, 0, ',', '.') }}</div>
                            <div class="report-meta mt-1">Selisih Rp {{ number_format($report->difference, 0, ',', '.') }}</div>
                        @else
                            <span class="status-pill status-pending">Menunggu teller tutup kas</span>
                        @endif
                    </td>

                    <td>
                        @if($report->status === 'pending')
                            <span class="status-pill status-pending">Pending</span>
                        @elseif($report->status === 'approved')
                            <span class="status-pill status-approved">Approved</span>
                        @elseif($report->status === 'rejected')
                            <span class="status-pill status-rejected">Rejected</span>
                        @else
                            <span class="status-pill status-closed">{{ $report->status }}</span>
                        @endif
                    </td>

                    <td>
                        @if($report->status === 'rejected')
                            @if(filled($report->rejection_reason))
                                <span style="white-space: pre-line;">{{ $report->rejection_reason }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>

                    <td class="action-cell">
                        <a
                            href="{{ auth()->user()->role === 'teller'
                                ? route('teller.daily-reports.show', $report->id)
                                : route('daily-reports.show', $report->id) }}"
                            class="detail-button"
                        >
                            Detail
                        </a>

                        @if(auth()->user()->role === 'admin' && $report->status === 'pending' && $report->closed_at)
                            <div class="mt-3">
                                <form action="{{ route('daily-reports.approve', $report->id) }}" method="POST" class="d-inline-block mb-2">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Apakah Anda yakin ingin menyetujui laporan ini?')">
                                        Approve
                                    </button>
                                </form>
                            </div>

                            <form action="{{ route('daily-reports.reject', $report->id) }}" method="POST" class="mt-2 text-start">
                                @csrf
                                <label for="reason-{{ $report->id }}" class="form-label small mb-1">Alasan penolakan</label>
                                <textarea id="reason-{{ $report->id }}" name="rejection_reason" class="form-control form-control-sm mb-2" rows="3" minlength="5" maxlength="2000" placeholder="Tuliskan alasan reject..." required>{{ old('rejection_reason') }}</textarea>
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin menolak laporan ini dengan alasan tersebut?')">
                                    Reject
                                </button>
                            </form>
                        @elseif(auth()->user()->role === 'admin' && $report->status === 'pending')
                            <div class="report-meta mt-3 text-start">
                                Menunggu kas ditutup teller. Aksi review dan catatan admin belum tersedia.
                            </div>
                        @elseif(auth()->user()->role === 'admin')
                            <div class="report-meta mt-3 text-start">Sudah diproses</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="report-empty">
                        Belum ada Daily Report.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
