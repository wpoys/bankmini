@extends('layouts.app')

@section('title', 'Konfigurasi Sistem')
@section('page-title', 'Konfigurasi Sistem')
@section('page-subtitle', 'Atur parameter operasional bank mini (diterapkan langsung ke seluruh modul)')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Konfigurasi Sistem</h1>
                <div class="page-header-sub">Atur parameter operasional bank mini (diterapkan langsung ke seluruh modul)</div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
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

                    <div class="data-panel mb-3">
                        <div class="panel-head">
                            <div class="panel-title"><span class="dot" style="background:var(--red)"></span>Keamanan Koreksi</div>
                        </div>
                        <div class="panel-body p-3">
                            <div class="mb-3 mb-0">
                                <label class="form-label fw-semibold">Kode Sandi Persetujuan Koreksi</label>
                                <input type="password" name="kode_sandi_koreksi" class="form-control" autocomplete="new-password"
                                    minlength="4" maxlength="64" placeholder="Masukkan kode sandi baru...">
                                <div class="form-text">
                                    Diperlukan Supervisor/Accounting saat menyetujui koreksi transaksi.
                                    Disimpan sebagai <strong>hash</strong> (tidak pernah plaintext).
                                    @if ($kodeSandiTersetel)
                                        <span class="badge badge-soft-success ms-1">Sudah disetel</span>
                                        Kosongkan kolom ini untuk mempertahankan kode sandi saat ini.
                                    @else
                                        <span class="badge badge-soft-warning ms-1">Belum disetel</span>
                                        Saat belum disetel, persetujuan koreksi belum memerlukan kode sandi.
                                    @endif
                                </div>
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
