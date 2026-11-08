@extends('layouts.app')

@section('title', 'Pengaturan Sistem & Database')
@section('page-title', 'Pengaturan Sistem & Database')
@section('page-subtitle', 'Kelola parameter operasional bank mini dan salinan cadangan data terpusat')

@section('content')
    <div class="page-animate settings-backup-page">
        @if (session('success'))
            <div class="alert alert-success small"><i class="fa-solid fa-circle-check me-1"></i>{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger small"><i class="fa-solid fa-circle-exclamation me-1"></i>{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger small">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="data-panel settings-backup-panel mb-3">
            <div class="panel-head">
                <div>
                    <div class="panel-title"><span class="dot"></span>Daftar Backup Database <span class="badge badge-soft-secondary ms-2">{{ count($files) }} Arsip</span></div>
                    <div class="small text-muted mt-1"><i class="fa-solid fa-database me-1"></i>Tersimpan di <code>storage/app/backups</code> &nbsp;|&nbsp; Skedul cron: <code>php artisan bankmini:backup</code></div>
                </div>
                <form action="{{ route('admin.backup.store') }}" method="POST">
                    @csrf
                    <button class="btn btn-success btn-sm"><i class="fa-solid fa-shield-halved me-1"></i>Buat Backup Sekarang</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0">
                    <thead><tr><th>Nama File</th><th class="text-end">Ukuran</th><th class="text-end">Dibuat</th><th class="text-end">Integritas</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($files as $file)
                            <tr>
                                <td class="fw-semibold small"><i class="fa-solid fa-file-shield me-1 text-success"></i>{{ $file->getFilename() }}</td>
                                <td class="num small text-end">{{ number_format($file->getSize() / 1024, 1) }} KB</td>
                                <td class="small text-end">{{ \Carbon\Carbon::createFromTimestamp($file->getMTime())->format('d/m/Y H:i') }}</td>
                                <td class="text-end"><span class="status-badge green">Tersedia</span></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.backup.download', $file->getFilename()) }}" class="btn btn-sm btn-outline-primary" title="Unduh"><i class="fa-solid fa-download"></i></a>
                                    <form action="{{ route('admin.backup.destroy', $file->getFilename()) }}" method="POST" class="d-inline" data-confirm="Hapus backup ini?" data-confirm-title="Hapus Backup">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-server"></i></div><div class="empty-title">Belum ada backup</div><div class="empty-desc">Buat backup pertama untuk mengamankan data.</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="settings-section-heading">
            <div><strong>Parameter &amp; Kebijakan Operasional</strong><span>Atur parameter operasional bank mini (diterapkan langsung ke seluruh modul)</span></div>
            <span class="badge badge-soft-success">Mode Sinkronisasi Aktif</span>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <form method="POST" action="{{ route('admin.konfigurasi.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="data-panel mb-3">
                        <div class="panel-head">
                            <div class="panel-title"><span class="dot"></span>Saldo & Penarikan</div>
                        </div>
                        <div class="panel-body p-3">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Saldo Mengendap Minimum (Rp)</label>
                                <input type="number" name="saldo_minimum" class="form-control" min="0"
                                    value="{{ old('saldo_minimum', \App\Models\Konfigurasi::saldoMinimum()) }}" required>
                                <div class="form-text">Batasan saldo yang harus tersisa setelah penarikan.</div>
                            </div>
                            <div class="mb-3 mb-0">
                                <label class="form-label fw-semibold">Batas Penarikan Besar (Rp)</label>
                                <input type="number" name="batas_penarikan_besar" class="form-control" min="0"
                                    value="{{ old('batas_penarikan_besar', \App\Models\Konfigurasi::batasPenarikanBesar()) }}" required>
                                <div class="form-text">Penarikan di atas nilai ini wajib mendapat konfirmasi nasabah (H-1)
                                    terlebih dahulu.</div>
                            </div>
                        </div>
                    </div>

                    <div class="data-panel mb-3">
                        <div class="panel-head">
                            <div class="panel-title"><span class="dot" style="background:var(--green)"></span>Kas Laci</div>
                        </div>
                        <div class="panel-body p-3">
                            <div class="mb-3 mb-0">
                                <label class="form-label fw-semibold">Referensi Kas Awal Harian (Rp)</label>
                                <input type="number" name="kas_awal_harian" class="form-control" min="0"
                                    value="{{ old('kas_awal_harian', \App\Models\Konfigurasi::kasAwalHarian()) }}" required>
                                <div class="form-text">Perkiraan kas laci yang disiapkan setiap piket. Dipakai sebagai acuan
                                    saat teller mengisi laporan harian.</div>
                            </div>
                        </div>
                    </div>

                    <div class="data-panel mb-3">
                        <div class="panel-head">
                            <div class="panel-title"><span class="dot" style="background:var(--orange)"></span>Kode Jurusan</div>
                        </div>
                        <div class="panel-body p-3">
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Kode yang Sah untuk Nomor Rekening</label>
                                <input type="text" name="kode_jurusan" class="form-control"
                                    value="{{ old('kode_jurusan', implode(', ', \App\Models\Konfigurasi::kodeJurusan())) }}"
                                    placeholder="RPL, DKV, AK, TKJ, GURU" required maxlength="500">
                                <div class="form-text">Pisahkan dengan koma. Format rekening = KODE-nomor urut, misal
                                    <code>RPL-001</code>, <code>GURU-007</code>. Jurusan baru yang belum terdaftar akan ditolak
                                    saat pembuatan rekening.</div>
                            </div>
                            <div class="small text-muted">
                                Terdaftar saat ini:
                                @foreach (\App\Models\Konfigurasi::kodeJurusan() as $kode)
                                    <span class="badge badge-soft-primary">{{ $kode }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Simpan Konfigurasi
                    </button>
                </form>

                <div class="text-center small text-muted mt-3">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i>Semua perubahan tercatat pada audit trail.
                </div>
            </div>
        </div>
    </div>
@endsection
