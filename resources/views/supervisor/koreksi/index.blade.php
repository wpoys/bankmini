@extends('layouts.app')

@section('title', 'Verifikasi Koreksi')
@section('page-title', 'Verifikasi Koreksi')
@section('page-subtitle', 'Persetujuan koreksi transaksi oleh Accounting')
@section('list-page-class', 'list-page')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Verifikasi Koreksi</h1>
                <div class="page-header-sub">Persetujuan koreksi transaksi oleh Accounting</div>
            </div>
            <div class="header-actions">
                <span class="status-badge orange">{{ number_format($menunggu, 0, ',', '.') }} menunggu</span>
            </div>
        </div>

        <ul class="nav nav-tabs mb-3">
            @php
                $status = request('status', 'menunggu_approval');
                $tabs = [
                    'menunggu_approval' => 'Menunggu Persetujuan',
                    'disetujui' => 'Disetujui',
                    'ditolak' => 'Ditolak',
                    'all' => 'Semua',
                ];
            @endphp
            @foreach ($tabs as $key => $label)
                <li class="nav-item">
                    <a class="nav-link {{ $status === $key ? 'active' : '' }}"
                        href="{{ route('supervisor.koreksi.index', ['status' => $key]) }}">
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="alert alert-info small d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-info"></i>
            <span><strong>{{ number_format($menunggu, 0, ',', '.') }}</strong> koreksi sedang menunggu persetujuan.
                Menyetujui koreksi akan membuat transaksi pembalik dan memperbarui saldo nasabah beserta jurnal akuntansinya.</span>
        </div>

        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Daftar Pengajuan Koreksi</div>
                <div class="table-toolbar">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" class="form-control form-control-sm" data-table-filter placeholder="Cari struk / nasabah / alasan...">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0" data-filterable>
                    <thead>
                        <tr>
                            <th>Diajukan</th>
                            <th>Struk Asli</th>
                            <th>Nasabah</th>
                            <th class="text-end">Nominal</th>
                            <th>Pengaju</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($koreksi as $k)
                            @php
                                $t = $k->transaksi;
                                $jenisPembalik = $k->jenis === 'koreksi_setoran' ? 'SETORAN' : 'PENARIKAN';
                                $arah = $jenisPembalik === 'SETORAN' ? 'bertambah' : 'berkurang';
                                $nominalEfektif = $k->nominal_efektif;
                                $kekuranganSaldo = $jenisPembalik === 'PENARIKAN'
                                    && (int) $k->nasabah?->saldo < $nominalEfektif;
                            @endphp
                            <tr data-status="{{ $k->status }}"
                                data-search="{{ $t?->no_struk }} {{ $k->nasabah->nama_siswa }} {{ $k->alasan }}">
                                <td class="small text-muted">{{ $k->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    @if ($t)
                                        <code class="small">{{ $t->no_struk }}</code>
                                    @else
                                        <span class="badge badge-soft-warning">Tanpa asli (temuan)</span>
                                    @endif
                                </td>
                                <td class="fw-semibold small">{{ $k->nasabah->nama_siswa }}<br>
                                    <span class="text-muted"><code>{{ $k->nasabah->no_rekening }}</code></span>
                                </td>
                                <td class="num fw-semibold text-end small">Rp {{ number_format($nominalEfektif, 0, ',', '.') }}</td>
                                <td class="small">{{ $k->pengaju->nama_petugas }}</td>
                                <td class="small text-muted" style="max-width:220px;">
                                    <span class="d-inline-block text-truncate w-100" title="{{ $k->alasan }}">{{ $k->alasan }}</span>
                                    @if ($k->jenis_label)
                                        <br><span class="badge badge-soft-info">{{ $k->jenis_label }}</span>
                                    @endif
                                    @if ($k->nominal && $t && $k->nominal !== $t->nominal)
                                        <br><span class="badge badge-soft-dark">Nominal koreksi: Rp {{ number_format($k->nominal, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $badge = match ($k->status) {
                                            'menunggu_approval' => 'warning',
                                            'disetujui' => 'success',
                                            'ditolak' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge badge-soft-{{ $badge }}">{{ $k->status_label }}</span>
                                </td>
                                <td class="text-end">
                                    @if ($k->status === 'menunggu_approval')
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailKoreksi{{ $k->id }}">
                                            <i class="fa-solid fa-eye me-1"></i>Verifikasi
                                        </button>
                                    @else
                                        <span class="text-muted small">Selesai</span>
                                    @endif
                                </td>
                            </tr>

                            @if ($k->status === 'menunggu_approval')
                                <div class="modal fade" id="detailKoreksi{{ $k->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h6 class="modal-title">Verifikasi Koreksi — {{ $t ? 'Struk '.$t->no_struk : 'Tanpa Transaksi Asli' }}</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body small">
                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-6"><strong>Nasabah:</strong> {{ $k->nasabah->nama_siswa }} (<code>{{ $k->nasabah->no_rekening }}</code>)</div>
                                                    <div class="col-md-6"><strong>Kelas:</strong> {{ $k->nasabah->kelas }}</div>
                                                    <div class="col-md-6"><strong>Transaksi asli:</strong>
                                                        @if ($t)
                                                            <span class="badge badge-soft-{{ $t->jenis_trans === 'setoran' ? 'success' : 'danger' }}">{{ $t->jenis_label }}</span>
                                                            pada {{ $t->tanggal?->format('d/m/Y') }}
                                                        @else
                                                            <span class="badge badge-soft-warning">Tidak ada (uang rusak/palsu/temuan)</span>
                                                        @endif
                                                    </div>
                                                    <div class="col-md-6"><strong>Arah koreksi:</strong>
                                                        <span class="badge badge-soft-{{ $jenisPembalik === 'SETORAN' ? 'success' : 'danger' }}">{{ $k->jenis_label ?? $jenisPembalik }}</span>
                                                    </div>
                                                    <div class="col-md-6"><strong>Nominal transaksi asli:</strong> Rp {{ number_format((int) ($t?->nominal ?? 0), 0, ',', '.') }}<br>
                                                        @if ($k->nominal && (!$t || $k->nominal !== $t->nominal))
                                                            <strong>Nominal koreksi:</strong> <span class="text-primary">Rp {{ number_format($k->nominal, 0, ',', '.') }}</span>
                                                        @endif</div>
                                                    <div class="col-md-6"><strong>Saldo nasabah saat ini:</strong> {{ $k->nasabah->saldo_rupiah }}</div>
                                                    <div class="col-md-6"><strong>Diajukan oleh:</strong> {{ $k->pengaju->nama_petugas }} — {{ $k->created_at->diffForHumans() }}</div>
                                                    <div class="col-12"><strong>Alasan:</strong> {{ $k->alasan }}</div>
                                                </div>
                                                <div class="alert alert-warning small mb-3">
                                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                                    Persetujuan membuat transaksi <strong>{{ $jenisPembalik }}</strong>
                                                    @if ($t)
                                                        (pembalik dari {{ $t->jenis_label }})
                                                    @else
                                                        (koreksi tanpa transaksi asli)
                                                    @endif
                                                    senilai <strong>Rp {{ number_format($nominalEfektif, 0, ',', '.') }}</strong> dan memperbarui saldo nasabah
                                                    ({{ $arah }}).
                                                    @if ($kekuranganSaldo)
                                                        <br><strong class="text-danger">Saldo nasabah tidak mencukupi untuk pembalik penarikan.</strong>
                                                    @endif
                                                </div>

                                                <form action="{{ route('supervisor.koreksi.approve', $k->id) }}" method="POST">
                                                    @csrf
                                                    <div class="mb-2">
                                                        <label class="form-label fw-semibold">Catatan Persetujuan <small class="text-muted">(opsional)</small></label>
                                                        <textarea name="catatan_approver" rows="2" class="form-control" placeholder="Catatan untuk persetujuan..."></textarea>
                                                    </div>
                                                    @if ($kodeSandiTersetel)
                                                        <div class="mb-2">
                                                            <label class="form-label fw-semibold">Kode Sandi Koreksi <span class="text-danger">*</span></label>
                                                            <input type="password" name="kode_sandi_koreksi" class="form-control" maxlength="64"
                                                                autocomplete="off" required placeholder="Masukkan kode sandi persetujuan...">
                                                            <div class="form-text">Kode sandi diatur Admin pada halaman Konfigurasi Sistem.</div>
                                                        </div>
                                                    @endif
                                                    <button class="btn btn-sm btn-success"
                                                        {{ $kekuranganSaldo ? 'disabled title="Saldo nasabah tidak mencukupi untuk pembalik penarikan"' : '' }}>
                                                        <i class="fa-solid fa-check me-1"></i>Setujui &amp; Buat Transaksi
                                                    </button>
                                                </form>
                                                <hr>
                                                <form action="{{ route('supervisor.koreksi.tolak', $k->id) }}" method="POST">
                                                    @csrf
                                                    <div class="mb-2">
                                                        <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                                                        <textarea name="catatan_approver" rows="2" class="form-control" required placeholder="Wajib diisi untuk menolak pengajuan..."></textarea>
                                                    </div>
                                                    <button class="btn btn-sm btn-danger"><i class="fa-solid fa-xmark me-1"></i>Tolak</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-rotate-left"></i></div>
                                        <div class="empty-title">Tidak ada pengajuan koreksi</div>
                                        <div class="empty-desc">Pada filter status ini belum ada pengajuan koreksi.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($koreksi->hasPages())
                <div class="p-2 border-top">{{ $koreksi->links() }}</div>
            @endif
        </div>
    </div>
@endsection
