@extends('layouts.app')

@section('title', 'Dashboard Supervisor')
@section('page-title', 'Dashboard Supervisor')
@section('page-subtitle', 'Verifikasi laporan harian para teller')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Dashboard Verifikasi</h1>
                <div class="page-header-sub">
                    Laporan kas teller — {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                </div>
            </div>
            <div class="header-actions">
                <span class="status-badge green">Sistem Aktif</span>
                <a href="{{ route('supervisor.verifikasi.index') }}" class="quick-action">
                    <i class="fa-solid fa-clipboard-check"></i><span>Antrean Verifikasi</span>
                </a>
                <a href="{{ route('supervisor.laporan.kartu-simpanan') }}" class="quick-action">
                    <i class="fa-solid fa-id-card"></i><span>Kartu Simpanan</span>
                </a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Menunggu Verifikasi</span>
                        <span class="stat-icon bg-warning-subtle text-warning"><i
                                class="fa-solid fa-hourglass-half"></i></span>
                    </div>
                    <div class="stat-value">{{ number_format($laporanPending, 0, ',', '.') }}</div>
                    <div class="stat-hint">Dalam antrean approval</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Disetujui</span>
                        <span class="stat-icon bg-success-subtle text-success"><i
                                class="fa-solid fa-circle-check"></i></span>
                    </div>
                    <div class="stat-value">{{ number_format($laporanDisetujui, 0, ',', '.') }}</div>
                    <div class="stat-hint">Rekonsiliasi sesuai</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Ditolak</span>
                        <span class="stat-icon bg-danger-subtle text-danger"><i class="fa-solid fa-circle-xmark"></i></span>
                    </div>
                    <div class="stat-value">{{ number_format($laporanDitolak, 0, ',', '.') }}</div>
                    <div class="stat-hint">Perlu koreksi teller</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Pengajuan PIN</span>
                        <span class="stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-key"></i></span>
                    </div>
                    <div class="stat-value">{{ number_format($permintaanPinPending, 0, ',', '.') }}</div>
                    <div class="stat-hint">Menunggu persetujuan</div>
                </div>
            </div>
        </div>

        <div class="data-panel mb-3">
            <div class="panel-head">
                <div class="panel-title"><span class="dot" style="background:var(--orange)"></span>Persetujuan Ubah PIN
                    Nasabah</div>
                <a href="{{ route('permintaan-pin.index') }}" class="small fw-semibold text-primary">Lihat semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Nasabah</th>
                            <th>No. Rekening</th>
                            <th>Keterangan</th>
                            <th>Dibuat</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftarPermintaanPin as $permintaan)
                            <tr>
                                <td class="fw-semibold small">{{ $permintaan->nasabah?->nama_siswa ?? '-' }}</td>
                                <td><code class="small">{{ $permintaan->nasabah?->no_rekening ?? '-' }}</code></td>
                                <td class="small text-muted">{{ $permintaan->keterangan }}</td>
                                <td class="small text-muted">{{ $permintaan->created_at?->format('d/m/Y H:i') }}</td>
                                <td><span
                                        class="status-badge {{ $permintaan->status_badge }}">{{ $permintaan->status_label }}</span>
                                </td>
                                <td class="text-end">
                                    @if ($permintaan->bisa_diproses && $permintaan->ada_pin_baru)
                                        <form action="{{ route('permintaan-pin.setujui', $permintaan->id) }}" method="POST"
                                            data-confirm="Setujui pengajuan perubahan PIN {{ $permintaan->nasabah?->nama_siswa ?? '' }}? PIN baru akan langsung berlaku."
                                            data-confirm-title="Setujui Pengajuan PIN">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fa-solid fa-check me-1"></i>Setujui
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Menunggu data PIN baru</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-key"></i></div>
                                        <div class="empty-title">Belum ada pengajuan perubahan PIN</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="data-panel">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot"></span>Antrean Verifikasi Laporan</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Teller</th>
                                    <th>Setoran</th>
                                    <th>Penarikan</th>
                                    <th>Selisih</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($daftarMenunggu as $l)
                                    <tr>
                                        <td class="small">{{ $l->tanggal->format('d M Y') }}</td>
                                        <td class="fw-semibold small">{{ $l->teller->nama_petugas }}</td>
                                        <td class="num small">Rp {{ number_format($l->total_setoran, 0, ',', '.') }}</td>
                                        <td class="num small">Rp {{ number_format($l->total_penarikan, 0, ',', '.') }}</td>
                                        <td>
                                            @if ($l->selisih === null)
                                                <span class="text-muted small">-</span>
                                            @elseif ($l->selisih == 0)
                                                <span class="status-badge green">Sesuai</span>
                                            @else
                                                <span class="status-badge red">Selisih</span>
                                            @endif
                                        </td>
                                        <td><span class="status-badge orange">Menunggu</span></td>
                                        <td class="text-end">
                                            <a href="{{ route('supervisor.verifikasi.show', $l->id) }}"
                                                class="btn btn-sm btn-outline-primary">Verifikasi</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <div class="empty-icon"><i class="fa-solid fa-inbox"></i></div>
                                                <div class="empty-title">Tidak ada laporan menunggu verifikasi</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="panel-body p-2 border-top d-flex justify-content-end">
                        <a href="{{ route('supervisor.verifikasi.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-list me-1"></i>Lihat Semua Verifikasi
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="data-panel mb-3">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot" style="background:var(--purple)"></span>Grafik Verifikasi
                        </div>
                    </div>
                    <div class="panel-body p-3">
                        <div class="chart-container chart-sm">
                            <canvas id="chartVerifikasi"></canvas>
                        </div>
                    </div>
                </div>

                <div class="data-panel">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot"></span>Riwayat Verifikasi</div>
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse ($history as $h)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <div class="small fw-semibold">{{ $h->teller->nama_petugas }}</div>
                                    <small class="text-muted">{{ $h->tanggal->format('d M Y') }} ·
                                        {{ $h->approver?->nama_petugas }}</small>
                                </div>
                                <span
                                    class="status-badge {{ $h->status === 'disetujui' ? 'green' : 'red' }}">{{ $h->status_label }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted small py-4">Belum ada riwayat</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const data = @json($grafik);

        new Chart(document.getElementById('chartVerifikasi'), {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.data,
                    backgroundColor: ['#64748b', '#F59E0B', '#16A34A', '#DC2626'],
                    borderWidth: 0,
                }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } },
        });
    </script>
@endpush