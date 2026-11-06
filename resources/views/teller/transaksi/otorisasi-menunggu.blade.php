@extends('layouts.app')

@section('title', 'Otorisasi Transaksi')
@section('page-title', 'Menunggu Otorisasi Nasabah')
@section('page-subtitle', 'Permintaan transaksi telah dikirim ke aplikasi nasabah')

@section('content')
    <section class="data-panel mx-auto" style="max-width:780px;">
        <div class="panel-body p-4 p-md-5 text-center">
            <div class="mb-3 text-primary" id="otorisasiIcon"><i class="fa-solid fa-mobile-screen-button fa-3x"></i></div>
            <span class="badge text-bg-warning mb-3" id="otorisasiStatus"><span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Menunggu PIN Nasabah</span>
            <h1 class="h4 fw-bold">Konfirmasi transaksi di aplikasi nasabah</h1>
            <p class="text-muted">Nasabah harus menyetujui dengan PIN dalam waktu 5 menit. Halaman ini akan diperbarui otomatis.</p>
            <div class="row g-3 my-4 text-start">
                <div class="col-sm-6"><div class="p-3 rounded border h-100"><small class="text-muted d-block">Nasabah</small><strong>{{ $transaksi->nasabah?->nama_siswa ?? '-' }}</strong></div></div>
                <div class="col-sm-6"><div class="p-3 rounded border h-100"><small class="text-muted d-block">Rekening</small><strong>{{ $transaksi->nasabah?->no_rekening ?? '-' }}</strong></div></div>
                <div class="col-sm-6"><div class="p-3 rounded border h-100"><small class="text-muted d-block">Jenis transaksi</small><strong>{{ $transaksi->jenis_label }}</strong></div></div>
                <div class="col-sm-6"><div class="p-3 rounded border h-100"><small class="text-muted d-block">Nominal</small><strong>{{ $transaksi->nominal_rupiah }}</strong></div></div>
                <div class="col-12"><div class="p-3 rounded border"><small class="text-muted d-block">Nomor referensi</small><strong>{{ $transaksi->no_struk }}</strong></div></div>
            </div>
            <div class="text-muted small mb-4" id="otorisasiCountdown" data-expiry="{{ $transaksi->kadaluarsa_pada?->toISOString() }}"></div>
            <div class="alert d-none" id="otorisasiResult" role="status"></div>
            <div class="d-flex justify-content-center gap-2">
                <a href="{{ route('teller.dashboard') }}" class="btn btn-outline-secondary" id="otorisasiBack">Kembali ke Transaksi</a>
                <a href="{{ route('teller.struk', $transaksi) }}" class="btn btn-success d-none" id="otorisasiReceipt">Lihat Struk</a>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const statusUrl = @json(route('teller.transaksi.otorisasi.status', $transaksi));
            const statusEl = document.getElementById('otorisasiStatus');
            const resultEl = document.getElementById('otorisasiResult');
            const countdownEl = document.getElementById('otorisasiCountdown');
            const receiptEl = document.getElementById('otorisasiReceipt');
            const expiry = new Date(countdownEl.dataset.expiry).getTime();
            let timer;

            const updateCountdown = () => {
                const remaining = Math.max(0, Math.ceil((expiry - Date.now()) / 1000));
                countdownEl.textContent = remaining > 0 ? `Sisa waktu: ${Math.floor(remaining / 60)}:${String(remaining % 60).padStart(2, '0')}` : 'Batas waktu terlewati, menyelesaikan status permintaan...';
            };

            const poll = async () => {
                updateCountdown();
                try {
                    const response = await fetch(statusUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    if (!response.ok) throw new Error('Tidak dapat memeriksa status permintaan.');
                    const result = await response.json();
                    if (result.status === 'menunggu') return;
                    clearInterval(timer);
                    statusEl.classList.remove('text-bg-warning');
                    statusEl.textContent = result.status === 'berhasil' ? 'Berhasil' : result.status === 'ditolak' ? 'Ditolak' : result.status === 'kedaluwarsa' ? 'Kedaluwarsa' : 'Gagal';
                    statusEl.classList.add(result.status === 'berhasil' ? 'text-bg-success' : 'text-bg-danger');
                    resultEl.className = `alert ${result.status === 'berhasil' ? 'alert-success' : 'alert-danger'}`;
                    resultEl.textContent = result.message;
                    if (result.status === 'berhasil' && result.receipt_url) {
                        receiptEl.href = result.receipt_url;
                        receiptEl.classList.remove('d-none');
                    }
                } catch (error) {
                    resultEl.className = 'alert alert-warning';
                    resultEl.textContent = error.message || 'Koneksi terputus. Halaman akan mencoba kembali.';
                }
            };

            poll();
            timer = setInterval(poll, 2500);
            setInterval(updateCountdown, 1000);
        })();
    </script>
@endpush
