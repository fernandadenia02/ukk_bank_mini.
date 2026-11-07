{{-- Menampilkan status buka/tutup bank dan kontrol perubahan status jika diizinkan. --}}
<div class="card shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2" role="status" aria-live="polite">
            <span
                class="rounded-circle d-inline-block flex-shrink-0"
                style="width: 16px; height: 16px; background-color: {{ $bankIsOpen ? '#198754' : '#dc3545' }};"
                aria-hidden="true"></span>
            <strong>{{ $bankIsOpen ? 'Bank Buka' : 'Bank Tutup / Istirahat' }}</strong>
        </div>

        @if($showControls)
            <form action="{{ route('teller.bank-status.update') }}" method="POST">
                @csrf
                <input type="hidden" name="is_open" value="{{ $bankIsOpen ? '0' : '1' }}">
                <button type="submit" class="btn {{ $bankIsOpen ? 'btn-warning' : 'btn-success' }}">
                    {{ $bankIsOpen ? 'Tutup Bank / Istirahat' : 'Buka Bank' }}
                </button>
            </form>
        @endif
    </div>
</div>
