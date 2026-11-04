@extends('layouts.app')

@section('title', 'Detail Laporan')
@section('page-title', 'Detail & Verifikasi Laporan')
@section('page-subtitle', 'Laporan tanggal ' . $laporan->tanggal->format('d M Y') . ' oleh ' . $laporan->teller->nama_petugas)

@section('content')
    @php
        $color = match ($laporan->status) {
            'draft' => 'secondary',
            'menunggu_approval' => 'warning',
            'pending' => 'info',
            'disetujui' => 'success',
            'ditolak' => 'danger',
            default => 'secondary',
        };
    @endphp

    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Detail Laporan</h1>
                <div class="page-header-sub">{{ $laporan->tanggal->format('l, d M Y') }}</div>
            </div>
            <div class="header-actions">
                <span class="badge badge-soft-{{ $color }}">{{ $laporan->status_label }}</span>
                <a href="{{ route('supervisor.verifikasi.index') }}" class="quick-action">
                    <i class="fa-solid fa-arrow-left"></i><span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="data-panel mb-3">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot" style="background:var(--orange)"></span>Rekapitulasi</div>
                    </div>
                    <div class="panel-body p-3">
                        <dl class="row mb-0 small">
                            <dt class="col-6 fw-semibold text-muted">Teller</dt>
                            <dd class="col-6">{{ $laporan->teller->nama_petugas }}</dd>
                            <dt class="col-6 fw-semibold text-muted">Tanggal</dt>
                            <dd class="col-6">{{ $laporan->tanggal->format('d M Y') }}</dd>
                            <dt class="col-6 fw-semibold text-muted">Saldo Awal Kas</dt>
                            <dd class="col-6 num">Rp {{ number_format($laporan->saldo_awal_kas, 0, ',', '.') }}</dd>
                            <dt class="col-6 fw-semibold text-success">Setoran</dt>
                            <dd class="col-6 num text-success">+ Rp {{ number_format($laporan->total_setoran, 0, ',', '.') }}</dd>
                            <dt class="col-6 fw-semibold text-danger">Penarikan</dt>
                            <dd class="col-6 num text-danger">- Rp {{ number_format($laporan->total_penarikan, 0, ',', '.') }}</dd>
                        </dl>
                        <hr>
                        <dl class="row mb-0 small">
                            <dt class="col-6 fw-bold">Saldo Akhir Sistem</dt>
                            <dd class="col-6 num fw-bold">Rp {{ number_format($laporan->saldo_akhir_sistem, 0, ',', '.') }}</dd>
                            <dt class="col-6 fw-semibold text-muted">Saldo Akhir Fisik</dt>
                            <dd class="col-6 num">{{ $laporan->saldo_akhir_fisik !== null ? 'Rp ' . number_format($laporan->saldo_akhir_fisik, 0, ',', '.') : '-' }}</dd>
                            <dt class="col-6 fw-semibold text-muted">Selisih</dt>
                            <dd class="col-6">
                                @if ($laporan->selisih === null)
                                    -
                                @elseif ($laporan->selisih == 0)
                                    <span class="status-badge green">Selaras</span>
                                @else
                                    <span class="status-badge red">Selisih {{ $laporan->selisih }}</span>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>

                @include('laporan.partials.rincian-saldo-awal', ['laporan' => $laporan])

                @if ($laporan->kas_pindah_brankas)
                    <div class="alert alert-info small">
                        <i class="fa-solid fa-vault me-1"></i>
                        Kas dipindahkan ke brankas: <strong>Rp {{ number_format($laporan->kas_dipindahkan, 0, ',', '.') }}</strong>
                        <span class="text-muted">— jurnal pemindahan Kas → Brankas akan dicatat saat laporan disetujui.</span>
                    </div>
                @endif

                @if ($laporan->saldo_akhir_fisik !== null)
                    <div class="data-panel mb-3">
                        <div class="panel-head">
                            <div class="panel-title"><span class="dot" style="background:var(--blue)"></span>Komparasi Kas Fisik vs Sistem</div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Pecahan</th>
                                        <th class="text-end">Lembar</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($laporan->rekonsiliasi?->detail ?? [] as $d)
                                        <tr>
                                            <td class="small num">Rp {{ number_format($d->pecahan, 0, ',', '.') }}</td>
                                            <td class="text-end small">{{ $d->jumlah_lembar }}</td>
                                            <td class="text-end small num">Rp {{ number_format($d->subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="small text-muted text-center py-2">
                                                Teller tidak menyimpan rincian pecahan — hanya total kas fisik.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th class="small">Total Kas Fisik (Teller)</th>
                                        <td colspan="2" class="text-end small num fw-bold">Rp {{ number_format($laporan->saldo_akhir_fisik, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <th class="small">Saldo Akhir Sistem</th>
                                        <td colspan="2" class="text-end small num fw-bold">Rp {{ number_format($laporan->saldo_akhir_sistem, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <th class="small">Selisih</th>
                                        <td colspan="2" class="text-end">
                                            @if ($laporan->selisih == 0)
                                                <span class="status-badge green">Selaras (Rp 0)</span>
                                            @else
                                                <span class="status-badge red">{{ $laporan->selisih > 0 ? '+' : '' }}Rp {{ number_format($laporan->selisih, 0, ',', '.') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="small">Status Rekonsiliasi</th>
                                        <td colspan="2" class="text-end">
                                            @if ($laporan->rekonsiliasi)
                                                <span class="status-badge {{ $laporan->rekonsiliasi->status === 'cocok' ? 'green' : 'red' }}">{{ ucfirst($laporan->rekonsiliasi->status) }}</span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif

                @if ($laporan->approved_by)
                    <div class="alert alert-success small">
                        <i class="fa-solid fa-user-check me-1"></i>
                        Diproses oleh <strong>{{ $laporan->approver?->nama_petugas }}</strong>
                        pada {{ $laporan->approved_at?->format('d M Y H:i') }}
                    </div>
                @endif

                @if ($laporan->catatan_supervisor)
                    <div class="alert alert-warning small">
                        <strong><i class="fa-solid fa-sticky-note me-1"></i>Catatan Supervisor:</strong><br>
                        {{ $laporan->catatan_supervisor }}
                    </div>
                @endif
            </div>

            <div class="col-lg-8">
                <div class="data-panel mb-3">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot"></span>Detail Transaksi ({{ $laporan->detailLaporan->count() }})</div>
                        <div class="table-toolbar">
                            <div class="search-box">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" class="form-control form-control-sm" data-table-filter placeholder="Cari struk / nasabah...">
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-nowrap mb-0" data-filterable>
                            <thead>
                                <tr>
                                    <th>No. Struk</th>
                                    <th>Waktu</th>
                                    <th>Nasabah</th>
                                    <th>Jenis</th>
                                    <th>Petugas</th>
                                    <th class="text-end">Nominal</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($laporan->detailLaporan as $detail)
                                    @php $t = $detail->transaksi; @endphp
                                    <tr data-search="{{ $t->no_struk }} {{ $t->nasabah->nama_siswa }}">
                                        <td><code class="small">{{ $t->no_struk }}</code></td>
                                        <td class="small text-muted">{{ $t->created_at->format('H:i') }}</td>
                                        <td class="fw-semibold small">{{ $t->nasabah->nama_siswa }}</td>
                                        <td><span class="badge badge-soft-{{ $t->jenis_trans === 'setoran' ? 'success' : 'danger' }}">{{ $t->jenis_label }}</span></td>
                                        <td class="small">{{ $t->user?->nama_petugas ?? '-' }}</td>
                                        <td class="num fw-semibold text-end small">{{ $t->nominal_rupiah }}</td>
                                        <td>@include('transaksi.partials.aksi', ['transaksi' => $t])</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-receipt"></i></div><div class="empty-title">Tidak ada transaksi</div></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($laporan->status === 'menunggu_approval')
                    <div class="data-panel">
                        <div class="panel-head">
                            <div class="panel-title"><span class="dot" style="background:var(--green)"></span>Keputusan Verifikasi</div>
                        </div>
                        <div class="panel-body p-3">
                            @php $adaSelisih = $laporan->selisih !== null && $laporan->selisih != 0; @endphp

                            @if ($adaSelisih)
                                <div class="alert alert-danger small py-2">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                    Terdapat selisih kas <strong>Rp {{ number_format(abs($laporan->selisih), 0, ',', '.') }}</strong>.
                                    Pengesahan dinonaktifkan — gunakan <strong>Tahan (Pending)</strong> agar teller memperbarui laporan, atau <strong>Tolak</strong> dengan catatan.
                                </div>
                            @endif

                            <form method="POST" action="{{ route('supervisor.verifikasi.approve', $laporan->id) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Catatan <small class="text-muted">(opsional)</small></label>
                                    <textarea name="catatan_supervisor" rows="2" class="form-control" placeholder="Catatan untuk laporan ini..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Kas Dipindahkan ke Brankas</label>
                                    <div class="form-control bg-light">
                                        @if ($laporan->kas_pindah_brankas)
                                            <i class="fa-solid fa-vault me-1 text-primary"></i>
                                            <strong>Rp {{ number_format($laporan->kas_dipindahkan, 0, ',', '.') }}</strong>
                                            <span class="text-muted small">— ditetapkan Teller saat membuat laporan. Sistem akan mencatat jurnal pemindahan dari Kas ke Brankas.</span>
                                        @else
                                            <span class="text-muted"><i class="fa-solid fa-circle-info me-1"></i>Tidak ada kas yang dipindahkan ke brankas.</span>
                                        @endif
                                    </div>
                                </div>
                                <button class="btn btn-success" type="submit" @disabled($adaSelisih) data-confirm="Setujui laporan ini? Saldo kas akan disesuaikan dan jurnal pemindahan brankas akan dicatat." data-confirm-title="Setujui Laporan">
                                    <i class="fa-solid fa-circle-check me-1"></i>Setujui
                                </button>
                            </form>

                            <hr>

                            <form method="POST" action="{{ route('supervisor.verifikasi.pending', $laporan->id) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Catatan Penahanan <span class="text-danger">*</span></label>
                                    <textarea name="catatan_supervisor" rows="2" class="form-control" required placeholder="Wajib diisi — jelaskan selisih yang harus diperbaiki teller..."></textarea>
                                    <div class="form-text">Laporan ditahan (pending); teller tetap dapat piket dan memperbarui laporan keesokan hari.</div>
                                </div>
                                <button class="btn btn-warning" type="submit" data-confirm="Tahan laporan ini? Teller akan menerima notifikasi untuk memperbaiki rekonsiliasi, tanpa mengunci akun." data-confirm-title="Tahan Laporan">
                                    <i class="fa-solid fa-pause me-1"></i>Tahan (Pending)
                                </button>
                            </form>

                            <hr>

                            <form method="POST" action="{{ route('supervisor.verifikasi.reject', $laporan->id) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                                    <textarea name="catatan_supervisor" rows="2" class="form-control" required placeholder="Wajib diisi untuk penolakan..."></textarea>
                                </div>
                                <button class="btn btn-danger" type="submit" data-confirm="Tolak laporan ini? Laporan akan dikembalikan ke status Ditolak." data-confirm-title="Tolak Laporan">
                                    <i class="fa-solid fa-circle-xmark me-1"></i>Tolak
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
