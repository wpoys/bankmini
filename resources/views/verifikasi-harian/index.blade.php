@extends('layouts.app')

@section('title', 'Verifikasi Transaksi Harian')
@section('page-title', 'Verifikasi Transaksi Harian')
@section('page-subtitle', 'Tinjau transaksi harian, terima verifikasi, atau tolak dengan catatan untuk ditindaklanjuti.')

@section('list-page-class', 'list-page')
@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Verifikasi Transaksi per Hari</h1>
                <div class="page-header-sub">
                    Pilih tanggal untuk meninjau ringkasan dan seluruh transaksi pada hari tersebut.
                </div>
            </div>
            <div class="header-actions">
                <a href="{{ route('supervisor.verifikasi.index') }}" class="quick-action">
                    <i class="fa-solid fa-file-lines"></i><span>Verifikasi Laporan Kas</span>
                </a>
            </div>
        </div>

        @if (! $hubContext)
            <div class="data-panel mb-3">
                <div class="panel-head">
                    <div class="panel-title"><span class="dot"></span>Daftar Tanggal Transaksi</div>
                <form method="GET" class="d-flex gap-2">
                    @if ($hubContext)
                        <input type="hidden" name="hub" value="1">
                        <input type="hidden" name="tab" value="transaksi-harian">
                        <input type="hidden" name="status" value="{{ $hubContext['status'] }}">
                        <input type="hidden" name="cari" value="{{ $hubContext['cari'] }}">
                    @endif
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
                                $sudah = $v?->sudah_diverifikasi ?? false;
                                $ditolak = $v?->ditolak ?? false;
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
                                        <span class="status-badge green">Diterima</span>
                                        <div class="small text-muted">
                                            {{ $v->verifikasi_at?->format('d/m/Y H:i') }} · {{ $v->verifikator?->nama_petugas ?? '-' }}
                                        </div>
                                    @elseif ($ditolak)
                                        <span class="status-badge red">Ditolak</span>
                                        <div class="small text-muted">Perlu ditinjau ulang</div>
                                    @else
                                        <span class="status-badge orange">Menunggu</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('verifikasi.index', array_merge(['tanggal' => $row['tanggal']], $hubContext ? ['hub' => 1, 'tab' => 'transaksi-harian', 'status' => $hubContext['status'], 'cari' => $hubContext['cari']] : [])) }}"
                                       class="btn btn-sm {{ $sudah ? 'btn-outline-secondary' : ($ditolak ? 'btn-outline-danger' : 'btn-outline-primary') }}">
                                        {{ $sudah ? 'Lihat Riwayat' : ($ditolak ? 'Tinjau Ulang' : 'Verifikasi') }}
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
        @endif

        @if ($terpilih)
            <div class="data-panel">
                <div class="panel-head">
                    <div class="panel-title">
                        <span class="dot" style="background:var(--purple)"></span>
                        {{ $terpilih->translatedFormat('l, d F Y') }}
                    </div>
                    @if ($verifikasi?->sudah_diverifikasi)
                        <span class="status-badge green"><i class="fa-solid fa-circle-check me-1"></i>Diterima · Sudah Diverifikasi</span>
                    @elseif ($verifikasi?->ditolak)
                        <span class="status-badge red"><i class="fa-solid fa-circle-xmark me-1"></i>Ditolak</span>
                    @else
                        <span class="status-badge orange">Menunggu</span>
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

                    @if ($verifikasi?->sudah_diverifikasi)
                        <div class="alert alert-success small py-2">
                            <i class="fa-solid fa-circle-check me-1"></i>
                            Tanggal ini diterima pada <strong>{{ $verifikasi->verifikasi_at?->format('d/m/Y H:i:s') }}</strong>
                            oleh <strong>{{ $verifikasi->verifikator?->nama_petugas ?? '-' }}</strong>.
                            Verifikasi ulang tidak diizinkan. Data di bawah bersifat riwayat.
                        </div>
                        @if ($verifikasi->catatan)
                            <div class="small text-muted mb-3">Catatan penolakan sebelumnya: {{ $verifikasi->catatan }}</div>
                        @endif
                    @else
                        @if ($verifikasi?->ditolak)
                            <div class="alert alert-danger small py-2">
                                <i class="fa-solid fa-circle-xmark me-1"></i>
                                Ditolak pada <strong>{{ $verifikasi->verifikasi_at?->format('d/m/Y H:i:s') }}</strong>
                                oleh <strong>{{ $verifikasi->verifikator?->nama_petugas ?? '-' }}</strong>.
                                <div class="mt-1"><strong>Catatan:</strong> {{ $verifikasi->catatan }}</div>
                                <div class="mt-1">Setelah tindak lanjut teller, tinjau kembali transaksi lalu pilih Terima.</div>
                            </div>
                        @endif

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <form method="POST" action="{{ route('verifikasi.proses', $terpilih->toDateString()) }}"
                                  data-confirm="Terima verifikasi seluruh transaksi tanggal {{ $terpilih->format('d/m/Y') }}?"
                                  data-confirm-title="Terima Verifikasi">
                                @csrf
                                <input type="hidden" name="keputusan" value="diterima">
                                @if ($hubContext)
                                    <input type="hidden" name="hub" value="1">
                                    <input type="hidden" name="tab" value="transaksi-harian">
                                    <input type="hidden" name="status" value="{{ $hubContext['status'] }}">
                                    <input type="hidden" name="cari" value="{{ $hubContext['cari'] }}">
                                @endif
                                <button type="submit" class="btn btn-success btn-submit-loading">
                                    <i class="fa-solid fa-circle-check me-1"></i>Terima
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalTolakVerifikasi">
                                <i class="fa-solid fa-circle-xmark me-1"></i>Tolak
                            </button>
                        </div>

                        <div class="modal fade" id="modalTolakVerifikasi" tabindex="-1" aria-labelledby="modalTolakVerifikasiLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('verifikasi.proses', $terpilih->toDateString()) }}">
                                        @csrf
                                        <input type="hidden" name="keputusan" value="ditolak">
                                        @if ($hubContext)
                                            <input type="hidden" name="hub" value="1">
                                            <input type="hidden" name="tab" value="transaksi-harian">
                                            <input type="hidden" name="status" value="{{ $hubContext['status'] }}">
                                            <input type="hidden" name="cari" value="{{ $hubContext['cari'] }}">
                                        @endif
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalTolakVerifikasiLabel">Tolak Verifikasi</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="small text-muted">Catatan penolakan akan dikirimkan kepada teller terkait dan tersimpan di riwayat.</p>
                                            <label for="catatanPenolakan" class="form-label">Catatan penolakan <span class="text-danger">*</span></label>
                                            <textarea name="catatan" id="catatanPenolakan" class="form-control" rows="4" maxlength="500" required placeholder="Jelaskan masalah yang perlu ditindaklanjuti">{{ old('catatan', $verifikasi?->ditolak ? $verifikasi->catatan : '') }}</textarea>
                                            @error('catatan')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-danger btn-submit-loading">Kirim Penolakan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="small text-muted mb-2">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        {{ $verifikasi?->sudah_diverifikasi ? 'Riwayat transaksi tanggal ini:' : 'Transaksi yang ditinjau:' }}
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

@push('scripts')
    @if ($errors->has('catatan'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalElement = document.getElementById('modalTolakVerifikasi');
                if (modalElement && window.bootstrap?.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            });
        </script>
    @endif
@endpush
