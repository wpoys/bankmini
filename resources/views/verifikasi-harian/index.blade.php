@extends('layouts.app')

@section('title', 'Verifikasi Transaksi Harian')
@section('page-title', 'Verifikasi Transaksi Harian')
@section('page-subtitle', 'Transaksi dikelompokkan per tanggal. Verifikasi satu kali per hari, tidak dapat diulang.')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Verifikasi Transaksi per Hari</h1>
                <div class="page-header-sub">
                    Pilih tanggal untuk melihat transaksi yang belum diverifikasi beserta ringkasannya.
                </div>
            </div>
            <div class="header-actions">
                <a href="{{ route('supervisor.verifikasi.index') }}" class="quick-action">
                    <i class="fa-solid fa-file-lines"></i><span>Verifikasi Laporan Kas</span>
                </a>
            </div>
        </div>

        <div class="data-panel mb-3">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Daftar Tanggal Transaksi</div>
                <form method="GET" class="d-flex gap-2">
                    <input type="date" name="tanggal" class="form-control form-control-sm"
                           value="{{ request('tanggal') }}" onchange="this.form.submit()">
                    <button class="btn btn-sm btn-primary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th class="text-end">Jumlah Transaksi</th>
                            <th class="text-end">Total Setoran</th>
                            <th class="text-end">Total Penarikan</th>
                            <th class="text-end">Total Kas</th>
                            <th>Selisih</th>
                            <th>Status Verifikasi</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftar as $row)
                            @php
                                $r = $row['ringkasan'];
                                $v = $row['sudah'];
                                $sudah = $v && $v->sudah_diverifikasi;
                            @endphp
                            <tr class="{{ request('tanggal') === $row['tanggal'] ? 'table-active' : '' }}">
                                <td class="fw-semibold small">
                                    {{ \Carbon\Carbon::parse($row['tanggal'])->translatedFormat('l, d M Y') }}
                                </td>
                                <td class="num small text-end">{{ number_format($r['jumlah'], 0, ',', '.') }}</td>
                                <td class="num small text-end text-success">Rp {{ number_format($r['total_setoran'], 0, ',', '.') }}</td>
                                <td class="num small text-end text-danger">Rp {{ number_format($r['total_penarikan'], 0, ',', '.') }}</td>
                                <td class="num small text-end fw-semibold">Rp {{ number_format($r['total_kas'], 0, ',', '.') }}</td>
                                <td>
                                    @if ($r['selisih'] === null)
                                        <span class="status-badge gray">Tidak ada laporan</span>
                                    @elseif ($r['selisih'] == 0)
                                        <span class="status-badge green">Sesuai</span>
                                    @else
                                        <span class="status-badge red">Ada Selisih</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($sudah)
                                        <span class="status-badge green">Selesai</span>
                                        <div class="small text-muted">
                                            {{ $v->verifikasi_at?->format('d/m/Y H:i') }} · {{ $v->verifikator?->nama_petugas ?? '-' }}
                                        </div>
                                    @else
                                        <span class="status-badge orange">Belum Diverifikasi</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('verifikasi.index', ['tanggal' => $row['tanggal']]) }}"
                                       class="btn btn-sm {{ $sudah ? 'btn-outline-secondary' : 'btn-outline-primary' }}">
                                        {{ $sudah ? 'Lihat Riwayat' : 'Verifikasi' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-calendar-day"></i></div>
                                        <div class="empty-title">Belum ada transaksi</div>
                                        <div class="empty-desc">Transaksi teller akan dikelompokkan per tanggal di sini.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($terpilih)
            <div class="data-panel">
                <div class="panel-head">
                    <div class="panel-title">
                        <span class="dot" style="background:var(--purple)"></span>
                        {{ $terpilih->translatedFormat('l, d F Y') }}
                    </div>
                    @if ($verifikasi && $verifikasi->sudah_diverifikasi)
                        <span class="status-badge green">
                            <i class="fa-solid fa-circle-check me-1"></i>Sudah Diverifikasi
                        </span>
                    @else
                        <span class="status-badge orange">Belum Diverifikasi</span>
                    @endif
                </div>

                <div class="panel-body p-3">
                    @if ($ringkasan)
                        <div class="balance-summary mb-3">
                            <div class="bs-box">
                                <div class="bs-label">Jumlah Transaksi</div>
                                <div class="bs-value" style="font-size:1.05rem;">{{ number_format($ringkasan['jumlah'], 0, ',', '.') }}</div>
                            </div>
                            <div class="bs-box">
                                <div class="bs-label">Total Setoran</div>
                                <div class="bs-value text-success" style="font-size:1.05rem;">+Rp {{ number_format($ringkasan['total_setoran'], 0, ',', '.') }}</div>
                            </div>
                            <div class="bs-box">
                                <div class="bs-label">Total Penarikan</div>
                                <div class="bs-value text-danger" style="font-size:1.05rem;">-Rp {{ number_format($ringkasan['total_penarikan'], 0, ',', '.') }}</div>
                            </div>
                            <div class="bs-box">
                                <div class="bs-label">Total Kas</div>
                                <div class="bs-value" style="font-size:1.05rem;">Rp {{ number_format($ringkasan['total_kas'], 0, ',', '.') }}</div>
                            </div>
                            <div class="bs-box">
                                <div class="bs-label">Selisih</div>
                                @if ($ringkasan['selisih'] === null)
                                    <div class="bs-value text-muted" style="font-size:1.05rem;">-</div>
                                @elseif ($ringkasan['selisih'] == 0)
                                    <div class="bs-value text-success" style="font-size:1.05rem;">Sesuai</div>
                                @else
                                    <div class="bs-value text-danger" style="font-size:1.05rem;">
                                        Rp {{ number_format(abs($ringkasan['selisih']), 0, ',', '.') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($verifikasi && $verifikasi->sudah_diverifikasi)
                        <div class="alert alert-success small py-2">
                            <i class="fa-solid fa-circle-check me-1"></i>
                            Verifikasi tanggal <strong>{{ $verifikasi->tanggal->format('d/m/Y') }}</strong>
                            telah selesai pada <strong>{{ $verifikasi->verifikasi_at->format('d/m/Y H:i:s') }}</strong>
                            oleh <strong>{{ $verifikasi->verifikator?->nama_petugas ?? '-' }}</strong>.
                            Verifikasi ulang tidak diizinkan. Data di bawah bersifat riwayat.
                        </div>
                    @else
                        <form method="POST" action="{{ route('verifikasi.proses', $terpilih->toDateString()) }}"
                              class="row g-2 align-items-end mb-3"
                              data-confirm="Verifikasi semua transaksi tanggal {{ $terpilih->format('d/m/Y') }}? Tindakan ini tidak dapat dibatalkan."
                              data-confirm-title="Verifikasi Transaksi Harian">
                            @csrf
                            <div class="col-md-8">
                                <label class="form-label small" for="catatanVerifikasi">Catatan (opsional)</label>
                                <input type="text" name="catatan" id="catatanVerifikasi" class="form-control form-control-sm"
                                       maxlength="500" placeholder="Contoh: Rekap sesuai dengan buku kas">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-success w-100 btn-submit-loading">
                                    <i class="fa-solid fa-circle-check me-1"></i>Verifikasi Tanggal Ini
                                </button>
                            </div>
                        </form>
                    @endif

                    <div class="small text-muted mb-2">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        {{ $verifikasi && $verifikasi->sudah_diverifikasi ? 'Riwayat transaksi tanggal ini:' : 'Transaksi yang belum diverifikasi:' }}
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>No. Struk</th>
                                    <th>Nasabah</th>
                                    <th>Jenis</th>
                                    <th class="text-end">Nominal</th>
                                    <th>Teller</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transaksi as $t)
                                    <tr>
                                        <td class="small">{{ $t->jam_transaksi }}</td>
                                        <td><code class="small">{{ $t->no_struk }}</code></td>
                                        <td class="small fw-semibold">{{ $t->nasabah?->nama_siswa ?? '-' }}</td>
                                        <td><span class="badge badge-soft-{{ $t->is_setoran ? 'success' : 'danger' }}">{{ $t->jenis_label }}</span></td>
                                        <td class="num small text-end">{{ $t->nominal_rupiah }}</td>
                                        <td class="small">{{ $t->user?->nama_petugas ?? '-' }}</td>
                                        <td>
                                            <span class="status-badge {{ $t->status === 'approved' ? 'green' : 'gray' }}">
                                                {{ $t->status_label }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <div class="empty-icon"><i class="fa-solid fa-inbox"></i></div>
                                                <div class="empty-title">Tidak ada transaksi pada tanggal ini</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
