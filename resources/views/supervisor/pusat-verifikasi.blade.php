@extends('layouts.app')

@section('title', 'Pusat Verifikasi & Pengawasan')
@section('page-title', 'Pusat Verifikasi & Pengawasan')
@section('page-subtitle', 'Pusat pemeriksaan, persetujuan, dan pengawasan aktivitas Bank Mini')

@section('content')
    @php
        $kategori = [
            'laporan' => ['label' => 'Verifikasi Laporan', 'icon' => 'fa-file-lines', 'count' => $laporanPending],
            'transaksi-harian' => ['label' => 'Transaksi Harian', 'icon' => 'fa-calendar-check', 'count' => $tanggalBelumVerifikasi],
            'koreksi' => ['label' => 'Verifikasi Koreksi', 'icon' => 'fa-rotate-left', 'count' => $koreksiPending],
            'pin' => ['label' => 'Pengajuan PIN', 'icon' => 'fa-key', 'count' => $pinPending],
            'aktivitas' => ['label' => 'Riwayat Aktivitas', 'icon' => 'fa-list-check', 'count' => null],
        ];
        $statusOptions = match ($tab) {
            'laporan' => ['all' => 'Semua Status', 'menunggu_approval' => 'Menunggu Persetujuan', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'pending' => 'Ditahan', 'draft' => 'Draft'],
            'koreksi' => ['all' => 'Semua Status', 'menunggu_approval' => 'Menunggu Persetujuan', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'],
            'pin' => ['all' => 'Semua Status', 'pending' => 'Menunggu Persetujuan', 'diproses' => 'Disetujui', 'ditolak' => 'Ditolak'],
            default => [],
        };
    @endphp
    <div class="page-animate review-center">
        <div class="review-section-caption"><strong>Kategori Pemeriksaan</strong><span>Pilih modul untuk meninjau data dan tindak lanjut</span></div>
        <nav class="review-category-tabs" aria-label="Kategori pemeriksaan">
            @foreach ($kategori as $key => $item)
                <a class="review-category-tab {{ $tab === $key ? 'active' : '' }}" href="{{ route('supervisor.pusat-verifikasi', ['tab' => $key, 'status' => match ($key) {'transaksi-harian' => 'belum', 'laporan', 'koreksi' => 'menunggu_approval', 'pin' => 'pending', default => null}]) }}">
                    <span class="review-tab-icon"><i class="fa-solid {{ $item['icon'] }}"></i></span>
                    <span class="review-tab-label">{{ $item['label'] }}</span>
                    @if ($item['count'] !== null)
                        <span class="review-tab-count {{ $item['count'] > 0 ? 'has-pending' : '' }}">{{ number_format($item['count'], 0, ',', '.') }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <section class="data-panel review-queue-panel">
            <div class="panel-head review-queue-head">
                <div><div class="panel-title"><span class="dot"></span>{{ $kategori[$tab]['label'] }}</div><p>{{ $tab === 'aktivitas' ? '100 aktivitas terakhir, hanya-baca' : 'Tinjau data dan buka detail untuk melakukan tindakan' }}</p></div>
                @if ($tab !== 'aktivitas')
                    <div class="review-queue-summary"><span class="status-badge orange">{{ number_format($kategori[$tab]['count'], 0, ',', '.') }} {{ $tab === 'transaksi-harian' ? 'perlu tindakan' : 'menunggu' }}</span></div>
                @endif
            </div>
            <div class="review-queue-filters">
                <form method="GET" action="{{ route('supervisor.pusat-verifikasi') }}" class="review-filter-form">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <label class="review-search"><i class="fa-solid fa-magnifying-glass"></i><input name="cari" value="{{ $search }}" type="search" class="form-control form-control-sm" placeholder="Cari berdasarkan nama, tanggal, alasan..." aria-label="Cari data pemeriksaan"></label>
                    @if ($tab !== 'aktivitas')
                        <select name="status" class="form-select form-select-sm" aria-label="Filter status">
                            @if ($tab === 'transaksi-harian')
                                <option value="belum" @selected($status === 'belum')>Menunggu / Perlu Ditinjau Ulang</option>
                                <option value="diterima" @selected($status === 'diterima')>Diterima</option>
                                <option value="ditolak" @selected($status === 'ditolak')>Ditolak</option>
                            @else
                                @foreach ($statusOptions as $statusValue => $label)
                                    <option value="{{ $statusValue }}" @selected($status === $statusValue)>{{ $label }}</option>
                                @endforeach
                            @endif
                        </select>
                    @endif
                    <button class="btn btn-sm btn-primary" type="submit"><i class="fa-solid fa-filter me-1"></i>Filter</button>
                </form>
                @if ($tab === 'transaksi-harian')
                    <a href="{{ route('verifikasi.index', ['tanggal' => today()->toDateString(), 'hub' => 1, 'tab' => 'transaksi-harian', 'status' => $status, 'cari' => $search]) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-calendar-day me-1"></i>Buka Hari Ini</a>
                @endif
            </div>
            <div class="table-responsive">
                @if ($tab === 'laporan')
                    <table class="table table-hover align-middle review-table mb-0">
                        <thead><tr><th>Tanggal</th><th>Teller</th><th class="text-end">Setoran</th><th class="text-end">Penarikan</th><th class="text-end">Saldo Akhir</th><th>Selisih</th><th>Status</th><th>Dikirim</th><th class="text-end">Tindakan</th></tr></thead>
                        <tbody>
                            @forelse ($laporan as $item)
                                <tr><td>{{ $item->tanggal->format('d M Y') }}</td><td>{{ $item->teller?->nama_petugas ?? '-' }}</td><td class="num text-end">Rp {{ number_format($item->total_setoran, 0, ',', '.') }}</td><td class="num text-end">Rp {{ number_format($item->total_penarikan, 0, ',', '.') }}</td><td class="num text-end">Rp {{ number_format($item->saldo_akhir_sistem, 0, ',', '.') }}</td><td>@if ($item->selisih === null) — @elseif ((int) $item->selisih === 0)<span class="status-badge green">Selaras</span>@else<span class="status-badge red">Selisih</span>@endif</td><td>{{ $item->status_label }}</td><td>{{ $item->submitted_at?->format('d/m H:i') ?? '-' }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('supervisor.verifikasi.show', ['laporan' => $item, 'hub' => 1, 'tab' => 'laporan', 'status' => $status, 'cari' => $search]) }}">{{ $item->status === 'menunggu_approval' ? 'Verifikasi' : 'Detail' }}</a></td></tr>
                            @empty
                                <tr><td colspan="9"><div class="empty-state"><div class="empty-title">Tidak ada laporan pada filter ini</div></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if ($laporan->hasPages())<div class="review-pagination">{{ $laporan->links() }}</div>@endif
                @elseif ($tab === 'transaksi-harian')
                    <table class="table table-hover align-middle review-table mb-0">
                        <thead><tr><th>Tanggal</th><th class="text-end">Transaksi</th><th class="text-end">Setoran</th><th class="text-end">Penarikan</th><th class="text-end">Total Kas</th><th>Selisih Laporan</th><th>Status Verifikasi</th><th class="text-end">Tindakan</th></tr></thead>
                        <tbody>
                            @forelse ($tanggalList as $item)
                                @php
                                    $verifikasiHarian = $item['verifikasi'];
                                    $diterima = $verifikasiHarian?->status === 'diterima';
                                    $ditolak = $verifikasiHarian?->status === 'ditolak';
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $item['tanggal_obj']->translatedFormat('d M Y') }}</td>
                                    <td class="text-end">{{ number_format($item['ringkasan']['jumlah'], 0, ',', '.') }}</td>
                                    <td class="num text-end">Rp {{ number_format($item['ringkasan']['total_setoran'], 0, ',', '.') }}</td>
                                    <td class="num text-end">Rp {{ number_format($item['ringkasan']['total_penarikan'], 0, ',', '.') }}</td>
                                    <td class="num text-end">Rp {{ number_format($item['ringkasan']['total_kas'], 0, ',', '.') }}</td>
                                    <td>{{ $item['ringkasan']['selisih'] === null ? 'Belum direkonsiliasi' : 'Rp '.number_format($item['ringkasan']['selisih'], 0, ',', '.') }}</td>
                                    <td>
                                        <span class="status-badge {{ $diterima ? 'green' : ($ditolak ? 'red' : 'orange') }}">{{ $diterima ? 'Diterima' : ($ditolak ? 'Ditolak' : 'Menunggu') }}</span>
                                        @if ($ditolak && $verifikasiHarian->catatan)
                                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($verifikasiHarian->catatan, 80) }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reviewHarianModal-{{ $item['tanggal_obj']->format('Ymd') }}">{{ $diterima ? 'Riwayat' : ($ditolak ? 'Tinjau Ulang' : 'Verifikasi') }}</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8"><div class="empty-state"><div class="empty-title">Tidak ada tanggal pada filter ini</div></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @foreach ($tanggalList as $item)
                        @php
                            $modalVerifikasi = $item['verifikasi'];
                            $modalDiterima = $modalVerifikasi?->status === 'diterima';
                            $modalDitolak = $modalVerifikasi?->status === 'ditolak';
                            $modalId = 'reviewHarianModal-'.$item['tanggal_obj']->format('Ymd');
                        @endphp
                        <div class="modal fade review-daily-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-xl">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <div>
                                            <h5 class="modal-title" id="{{ $modalId }}Label">Detail Verifikasi & Audit Transaksi</h5>
                                            <div class="small text-muted">{{ $item['tanggal_obj']->translatedFormat('l, d F Y') }} · {{ number_format($item['ringkasan']['jumlah'], 0, ',', '.') }} transaksi</div>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                    </div>
                                    <div class="modal-body">
                                        @if ($modalDitolak)
                                            <div class="alert alert-danger py-2 small"><strong>Ditolak:</strong> {{ $modalVerifikasi->catatan }}<br><span class="text-muted">{{ $modalVerifikasi->verifikator?->nama_petugas ?? '-' }} · {{ $modalVerifikasi->verifikasi_at?->format('d/m/Y H:i') }}</span></div>
                                        @elseif ($modalDiterima)
                                            <div class="alert alert-success py-2 small">Tanggal ini telah diterima oleh {{ $modalVerifikasi->verifikator?->nama_petugas ?? '-' }} pada {{ $modalVerifikasi->verifikasi_at?->format('d/m/Y H:i') }}.</div>
                                        @endif
                                        <div class="table-responsive review-daily-transactions">
                                            <table class="table table-sm table-hover align-middle mb-0">
                                                <thead><tr><th>Waktu</th><th>No. Struk</th><th>Nasabah</th><th>Jenis Mutasi</th><th class="text-end">Nominal (Rp)</th><th>Petugas Teller</th></tr></thead>
                                                <tbody>
                                                    @forelse ($item['transaksi'] as $transaksiHarian)
                                                        <tr>
                                                            <td class="small">{{ $transaksiHarian->jam_transaksi }}</td>
                                                            <td><code class="small">{{ $transaksiHarian->no_struk }}</code></td>
                                                            <td class="small fw-semibold">{{ $transaksiHarian->nasabah?->nama_siswa ?? '-' }}</td>
                                                            <td><span class="badge badge-soft-{{ $transaksiHarian->is_setoran ? 'success' : 'danger' }}">{{ $transaksiHarian->jenis_label }}</span></td>
                                                            <td class="num small text-end">{{ number_format($transaksiHarian->nominal, 0, ',', '.') }}</td>
                                                            <td class="small">{{ $transaksiHarian->user?->nama_petugas ?? '-' }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="6" class="text-center text-muted py-3">Tidak ada transaksi pada tanggal ini.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="review-daily-summary">
                                            <span>Jumlah: <strong>{{ number_format($item['ringkasan']['jumlah'], 0, ',', '.') }}</strong></span>
                                            <span>Setoran: <strong class="text-success">Rp {{ number_format($item['ringkasan']['total_setoran'], 0, ',', '.') }}</strong></span>
                                            <span>Penarikan: <strong class="text-danger">Rp {{ number_format($item['ringkasan']['total_penarikan'], 0, ',', '.') }}</strong></span>
                                            <span>Total Kas: <strong>Rp {{ number_format($item['ringkasan']['total_kas'], 0, ',', '.') }}</strong></span>
                                            <span>Selisih: <strong>{{ $item['ringkasan']['selisih'] === null ? 'Belum direkonsiliasi' : 'Rp '.number_format($item['ringkasan']['selisih'], 0, ',', '.') }}</strong></span>
                                        </div>
                                        @if (! $modalDiterima)
                                            <div class="review-daily-authorization">
                                                <div class="review-daily-authorization-title"><i class="fa-solid fa-lock me-2"></i>Otorisasi & Catatan Rekonsiliasi Supervisor</div>
                                                <p class="small text-muted mb-2">Jika ditemukan kesalahan, minta tindak lanjut teller dan isi catatan wajib.</p>
                                                <div class="review-daily-actions">
                                                    <details class="review-daily-rejection">
                                                        <summary class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-rotate-left me-1"></i>Minta Koreksi ke Teller / Tolak</summary>
                                                        <form method="POST" action="{{ route('verifikasi.proses', $item['tanggal_obj']->toDateString()) }}" class="review-daily-rejection-form">
                                                            @csrf
                                                            <input type="hidden" name="keputusan" value="ditolak">
                                                            <input type="hidden" name="hub" value="1">
                                                            <input type="hidden" name="tab" value="transaksi-harian">
                                                            <input type="hidden" name="status" value="{{ $status }}">
                                                            <input type="hidden" name="cari" value="{{ $search }}">
                                                            <input type="hidden" name="modal_tanggal" value="{{ $item['tanggal_obj']->toDateString() }}">
                                                            <label for="catatanHarian-{{ $item['tanggal_obj']->format('Ymd') }}" class="form-label small fw-semibold mt-2">Alasan / catatan koreksi <span class="text-danger">*</span></label>
                                                            <textarea id="catatanHarian-{{ $item['tanggal_obj']->format('Ymd') }}" name="catatan" class="form-control form-control-sm" rows="3" maxlength="500" required placeholder="Jelaskan transaksi yang perlu diperiksa atau dikoreksi">{{ old('modal_tanggal') === $item['tanggal_obj']->toDateString() ? old('catatan') : '' }}</textarea>
                                                            @if (old('modal_tanggal') === $item['tanggal_obj']->toDateString())
                                                                @error('catatan')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                                            @endif
                                                            <button type="submit" class="btn btn-sm btn-danger mt-2"><i class="fa-solid fa-paper-plane me-1"></i>Kirim Koreksi ke Teller</button>
                                                        </form>
                                                    </details>
                                                    <form method="POST" action="{{ route('verifikasi.proses', $item['tanggal_obj']->toDateString()) }}" class="review-daily-accept-form">
                                                        @csrf
                                                        <input type="hidden" name="keputusan" value="diterima">
                                                        <input type="hidden" name="hub" value="1">
                                                        <input type="hidden" name="tab" value="transaksi-harian">
                                                        <input type="hidden" name="status" value="{{ $status }}">
                                                        <input type="hidden" name="cari" value="{{ $search }}">
                                                        <input type="hidden" name="modal_tanggal" value="{{ $item['tanggal_obj']->toDateString() }}">
                                                        <button type="submit" class="btn btn-success btn-sm" @disabled($item['ringkasan']['jumlah'] === 0)><i class="fa-solid fa-circle-check me-1"></i>Verifikasi & Kunci Buku Kas</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="modal-footer justify-content-start">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-1"></i>Tutup / Batal</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @if ($tanggalList->hasPages())<div class="review-pagination">{{ $tanggalList->links() }}</div>@endif
                @elseif ($tab === 'koreksi')
                    <table class="table table-hover align-middle review-table mb-0">
                        <thead><tr><th>Diajukan</th><th>Nasabah</th><th>Transaksi</th><th>Jenis / Alasan</th><th class="text-end">Nominal</th><th>Pengaju</th><th>Status</th><th class="text-end">Tindakan</th></tr></thead>
                        <tbody>
                            @forelse ($koreksi as $item)
                                @php
                                    $transaksiAsli = $item->transaksi;
                                    $jenisPembalik = $item->jenis
                                        ? ($item->jenis === \App\Models\Transaksi::JENIS_KOREKSI_SETORAN ? 'SETORAN' : 'PENARIKAN')
                                        : ($transaksiAsli?->jenis_trans === \App\Models\Transaksi::JENIS_SETORAN ? 'PENARIKAN' : 'SETORAN');
                                    $arahKoreksi = $jenisPembalik === 'SETORAN' ? 'bertambah' : 'berkurang';
                                    $kekuranganSaldo = $jenisPembalik === 'PENARIKAN' && (int) $item->nasabah?->saldo < $item->nominal_efektif;
                                    $statusBadge = match ($item->status) { 'menunggu_approval' => 'orange', 'disetujui' => 'green', 'ditolak' => 'red', default => 'gray' };
                                @endphp
                                <tr>
                                    <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $item->nasabah?->nama_siswa ?? '-' }}<br><code>{{ $item->nasabah?->no_rekening ?? '-' }}</code></td>
                                    <td>{{ $transaksiAsli?->no_struk ?? 'Temuan' }}</td>
                                    <td>{{ $item->jenis_label ?? $item->alasan }}<div class="small text-muted">{{ \Illuminate\Support\Str::limit($item->alasan, 70) }}</div></td>
                                    <td class="num text-end">Rp {{ number_format($item->nominal_efektif, 0, ',', '.') }}</td>
                                    <td>{{ $item->pengaju?->nama_petugas ?? '-' }}</td>
                                    <td><span class="status-badge {{ $statusBadge }}">{{ $item->status_label }}</span></td>
                                    <td class="text-end">
                                        @if ($item->status === 'menunggu_approval')
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailKoreksi-{{ $item->id }}"><i class="fa-solid fa-eye me-1"></i>Verifikasi</button>
                                        @else
                                            <span class="text-muted small">Selesai</span>
                                        @endif
                                    </td>
                                </tr>
                                @if ($item->status === 'menunggu_approval')
                                    <div class="modal fade review-correction-modal" id="detailKoreksi-{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
                                            <div class="modal-header"><div><h5 class="modal-title">Verifikasi Koreksi — {{ $transaksiAsli ? 'Struk '.$transaksiAsli->no_struk : 'Tanpa Transaksi Asli' }}</h5><div class="small text-muted">{{ $item->created_at->translatedFormat('d F Y, H:i') }}</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                                            <div class="modal-body small">
                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-6"><strong>Nasabah</strong><div>{{ $item->nasabah?->nama_siswa ?? '-' }} (<code>{{ $item->nasabah?->no_rekening ?? '-' }}</code>)</div></div>
                                                    <div class="col-md-6"><strong>Kelas</strong><div>{{ $item->nasabah?->kelas ?? '-' }}</div></div>
                                                    <div class="col-md-6"><strong>Transaksi asli</strong><div>{{ $transaksiAsli?->jenis_label ?? 'Tidak ada transaksi asli' }}{{ $transaksiAsli ? ' · '.$transaksiAsli->tanggal?->format('d/m/Y') : '' }}</div></div>
                                                    <div class="col-md-6"><strong>Arah koreksi</strong><div>{{ $item->jenis_label ?? $jenisPembalik }}</div></div>
                                                    <div class="col-md-6">
                                                        <strong>Nominal transaksi asli</strong>
                                                        <div>Rp {{ number_format((int) ($transaksiAsli?->nominal ?? 0), 0, ',', '.') }}</div>
                                                        @if ($item->nominal && (!$transaksiAsli || $item->nominal !== $transaksiAsli->nominal))
                                                            <div><strong>Nominal koreksi:</strong> Rp {{ number_format($item->nominal, 0, ',', '.') }}</div>
                                                        @endif
                                                    </div>
                                                    <div class="col-md-6"><strong>Saldo nasabah saat ini</strong><div>{{ $item->nasabah?->saldo_rupiah ?? '-' }}</div></div>
                                                    <div class="col-md-6"><strong>Diajukan oleh</strong><div>{{ $item->pengaju?->nama_petugas ?? '-' }} — {{ $item->created_at->diffForHumans() }}</div></div>
                                                    <div class="col-12"><strong>Alasan</strong><div>{{ $item->alasan }}</div></div>
                                                </div>
                                                <div class="alert alert-warning small">
                                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                                    Persetujuan membuat transaksi <strong>{{ $jenisPembalik }}</strong>
                                                    @if ($transaksiAsli)
                                                        (pembalik dari {{ $transaksiAsli->jenis_label }})
                                                    @else
                                                        (koreksi tanpa transaksi asli)
                                                    @endif
                                                    senilai <strong>Rp {{ number_format($item->nominal_efektif, 0, ',', '.') }}</strong> dan membuat saldo {{ $arahKoreksi }}.
                                                    @if ($kekuranganSaldo)
                                                        <br><strong class="text-danger">Saldo nasabah tidak mencukupi untuk koreksi penarikan.</strong>
                                                    @endif
                                                </div>
                                                <form action="{{ route('supervisor.koreksi.approve', $item->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="hub" value="1"><input type="hidden" name="tab" value="koreksi"><input type="hidden" name="status" value="{{ $status }}"><input type="hidden" name="cari" value="{{ $search }}">
                                                    <div class="mb-2"><label class="form-label fw-semibold">Catatan persetujuan <span class="text-muted">(opsional)</span></label><textarea name="catatan_approver" rows="2" class="form-control" maxlength="1000" placeholder="Catatan untuk persetujuan..."></textarea></div>
                                                    @if ($kodeSandiTersetel)
                                                        <div class="mb-2">
                                                            <label class="form-label fw-semibold">Kode sandi koreksi <span class="text-danger">*</span></label>
                                                            <input type="password" name="kode_sandi_koreksi" class="form-control" maxlength="64" autocomplete="off" required>
                                                            <div class="form-text">Kode sandi diatur Admin pada halaman Konfigurasi Sistem.</div>
                                                        </div>
                                                    @endif
                                                    <button class="btn btn-sm btn-success" @disabled($kekuranganSaldo)><i class="fa-solid fa-check me-1"></i>Setujui &amp; Buat Transaksi</button>
                                                </form>
                                                <hr>
                                                <form action="{{ route('supervisor.koreksi.tolak', $item->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="hub" value="1"><input type="hidden" name="tab" value="koreksi"><input type="hidden" name="status" value="{{ $status }}"><input type="hidden" name="cari" value="{{ $search }}">
                                                    <div class="mb-2"><label class="form-label fw-semibold">Alasan penolakan <span class="text-danger">*</span></label><textarea name="catatan_approver" rows="2" class="form-control" maxlength="1000" required placeholder="Wajib diisi untuk menolak pengajuan..."></textarea></div>
                                                    <button class="btn btn-sm btn-danger"><i class="fa-solid fa-xmark me-1"></i>Tolak Pengajuan</button>
                                                </form>
                                            </div>
                                        </div></div>
                                    </div>
                                @endif
                            @empty
                                <tr><td colspan="8"><div class="empty-state"><div class="empty-title">Tidak ada pengajuan koreksi pada filter ini</div></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if ($koreksi->hasPages())<div class="review-pagination">{{ $koreksi->links() }}</div>@endif
                @elseif ($tab === 'pin')
                    <table class="table table-hover align-middle review-table mb-0">
                        <thead><tr><th>Diajukan</th><th>Nasabah</th><th>Keterangan</th><th>Pengaju</th><th>Status</th><th class="text-end">Tindakan</th></tr></thead>
                        <tbody>
                            @forelse ($permintaanPin as $item)
                                <tr>
                                    <td>{{ ($item->diajukan_pada ?? $item->created_at)?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td>{{ $item->nasabah?->nama_siswa ?? '-' }}<br><code>{{ $item->nasabah?->no_rekening ?? '-' }}</code></td>
                                    <td>{{ $item->keterangan ?: '-' }}</td>
                                    <td>{{ $item->teller?->nama_petugas ?? $item->diajukan_oleh_role }}</td>
                                    <td>
                                        {{ $item->status_label }}
                                        @if ($item->bisa_diproses && ! $item->ada_pin_baru)
                                            <div class="small text-danger">PIN baru tidak tersimpan</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($item->bisa_diproses)
                                            <div class="d-inline-flex gap-1 align-items-center">
                                                @if ($item->ada_pin_baru)
                                                    <form method="POST" action="{{ route('permintaan-pin.setujui', $item) }}">
                                                        @csrf
                                                        <input type="hidden" name="hub" value="1">
                                                        <input type="hidden" name="tab" value="pin">
                                                        <input type="hidden" name="status" value="{{ $status }}">
                                                        <input type="hidden" name="cari" value="{{ $search }}">
                                                        <button type="submit" class="btn btn-sm btn-success" data-confirm="Setujui pengajuan PIN {{ $item->nomor_tiket ?? '' }}? PIN baru akan langsung berlaku."><i class="fa-solid fa-check me-1"></i>Setuju</button>
                                                    </form>
                                                @else
                                                    <span class="small text-danger">Tidak bisa disetujui</span>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#pinRejectModal-{{ $item->id }}"><i class="fa-solid fa-xmark me-1"></i>Tolak</button>
                                            </div>
                                        @else
                                            <span class="small text-muted">Sudah diproses</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="empty-state"><div class="empty-title">Tidak ada pengajuan PIN pada filter ini</div></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @foreach ($permintaanPin as $item)
                        @if ($item->bisa_diproses)
                            <div class="modal fade pin-rejection-modal" id="pinRejectModal-{{ $item->id }}" tabindex="-1" aria-labelledby="pinRejectModal-{{ $item->id }}Label" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('permintaan-pin.tolak', $item) }}">
                                            @csrf
                                            <input type="hidden" name="hub" value="1">
                                            <input type="hidden" name="tab" value="pin">
                                            <input type="hidden" name="status" value="{{ $status }}">
                                            <input type="hidden" name="cari" value="{{ $search }}">
                                            <input type="hidden" name="modal_pin_id" value="{{ $item->id }}">
                                            <div class="modal-header">
                                                <div>
                                                    <h5 class="modal-title" id="pinRejectModal-{{ $item->id }}Label">Tolak Pengajuan PIN</h5>
                                                    <div class="small text-muted">{{ $item->nasabah?->nama_siswa ?? '-' }} · {{ $item->nomor_tiket ?? 'Pengajuan #'.$item->id }}</div>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                            </div>
                                            <div class="modal-body">
                                                <label for="alasanPenolakan-{{ $item->id }}" class="form-label">Alasan penolakan <span class="text-danger">*</span></label>
                                                <textarea id="alasanPenolakan-{{ $item->id }}" name="alasan_penolakan" class="form-control" rows="4" maxlength="500" required placeholder="Jelaskan alasan penolakan kepada nasabah">{{ old('modal_pin_id') == $item->id ? old('alasan_penolakan') : '' }}</textarea>
                                                @if (old('modal_pin_id') == $item->id)
                                                    @error('alasan_penolakan')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-xmark me-1"></i>Tolak Pengajuan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                    @if ($permintaanPin->hasPages())<div class="review-pagination">{{ $permintaanPin->links() }}</div>@endif
                @else
                    <table class="table table-hover align-middle review-table mb-0">
                        <thead><tr><th>Waktu</th><th>User</th><th>Aktivitas</th><th>Deskripsi</th><th>IP Address</th></tr></thead>
                        <tbody>
                            @forelse ($logs as $item)
                                <tr><td>{{ $item->created_at?->format('d M Y H:i:s') }}</td><td>{{ $item->user?->nama_petugas ?? 'Sistem' }}</td><td><span class="badge badge-soft-primary">{{ $item->aktivitas }}</span></td><td>{{ $item->deskripsi }}</td><td><code>{{ $item->ip_address }}</code></td></tr>
                            @empty
                                <tr><td colspan="5"><div class="empty-state"><div class="empty-title">Tidak ada aktivitas yang cocok</div></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
            <div class="review-queue-foot"><i class="fa-solid fa-shield-halved"></i><span>Setiap persetujuan tetap diproses dan divalidasi oleh modul terkait. Tindakan massal tidak tersedia.</span></div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-bs-toggle="modal"][data-bs-target]');
            if (!trigger) return;

            const modalElement = document.querySelector(trigger.getAttribute('data-bs-target'));
            if (modalElement && modalElement.matches('.review-daily-modal, .review-correction-modal, .pin-rejection-modal')) {
                document.body.appendChild(modalElement);
            }
        }, true);

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.review-daily-modal, .review-correction-modal, .pin-rejection-modal').forEach(function (modalElement) {
                document.body.appendChild(modalElement);
            });

            @if ($errors->has('catatan') && old('modal_tanggal'))
                const modalElement = document.getElementById(@js('reviewHarianModal-'.str_replace('-', '', (string) old('modal_tanggal'))));
                const correctionForm = modalElement?.querySelector('.review-daily-rejection');
                if (correctionForm) correctionForm.open = true;
                if (modalElement && window.bootstrap?.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            @endif

            @if ($errors->has('alasan_penolakan') && old('modal_pin_id'))
                const pinModal = document.getElementById(@js('pinRejectModal-'.(string) old('modal_pin_id')));
                if (pinModal && window.bootstrap?.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(pinModal).show();
                }
            @endif
        });
    </script>
@endpush
