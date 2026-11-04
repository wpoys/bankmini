@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard Admin')
@section('page-subtitle', 'Panel administrasi sistem Bank Mini Sekolah')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Dashboard Administrasi</h1>
                <div class="page-header-sub">
                    Ringkasan master data & transaksi — {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                </div>
            </div>
            <div class="header-actions">
                <span class="status-badge green">Sistem Aktif</span>
                <a href="{{ route('admin.users.index', ['modal' => 'petugas']) }}" class="quick-action">
                    <i class="fa-solid fa-user-plus"></i><span>Petugas Baru</span>
                </a>
                <a href="{{ route('admin.nasabah.index', ['modal' => 'nasabah']) }}" class="quick-action">
                    <i class="fa-solid fa-user-graduate"></i><span>Nasabah Baru</span>
                </a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Total Nasabah</span>
                        <span class="stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-user-graduate"></i></span>
                    </div>
                    <div class="stat-value">{{ number_format($totalNasabah, 0, ',', '.') }}</div>
                    <div class="stat-hint">Seluruh rekening aktif</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Total User</span>
                        <span class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-users"></i></span>
                    </div>
                    <div class="stat-value">{{ number_format($totalUser, 0, ',', '.') }}</div>
                    <div class="stat-hint">Pengguna sistem</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Transaksi Hari Ini</span>
                        <span class="stat-icon bg-warning-subtle text-warning"><i class="fa-solid fa-money-bill-transfer"></i></span>
                    </div>
                    <div class="stat-value">{{ number_format($transaksiHariIni, 0, ',', '.') }}</div>
                    <div class="stat-hint">Seluruh loket</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Laporan Pending</span>
                        <span class="stat-icon bg-danger-subtle text-danger"><i class="fa-solid fa-hourglass-half"></i></span>
                    </div>
                    <div class="stat-value">{{ number_format($laporanPending, 0, ',', '.') }}</div>
                    <div class="stat-hint">Menunggu verifikasi</div>
                </div>
            </div>
        </div>

        <div class="alert alert-info small py-2 d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <span>
                <i class="fa-solid fa-calendar-check me-2"></i>Jadwal hari ini:
                <strong>{{ number_format($jadwalPetugasHariIni, 0, ',', '.') }}</strong> petugas terjadwal
                &middot; Nasabah baru hari ini:
                <strong>{{ number_format($nasabahBaruHariIni, 0, ',', '.') }}</strong>.
            </span>
            <a href="{{ route('admin.jadwal.index') }}" class="fw-semibold small">Kelola Jadwal <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>

        <div class="data-panel mb-3" id="reset-pin">
            <div class="panel-head">
                <div class="panel-title"><span class="dot" style="background:var(--orange)"></span>Persetujuan Ubah PIN Nasabah</div>
                <div class="d-flex align-items-center gap-3">
                    @if ($permintaanResetPending > 0)
                        <span class="status-badge orange">{{ $permintaanResetPending }} menunggu</span>
                    @else
                        <span class="status-badge green">Tidak ada antrean</span>
                    @endif
                    <a href="{{ route('permintaan-pin.index') }}" class="small fw-semibold text-primary">Lihat semua</a>
                </div>
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
                        @forelse ($permintaanReset as $p)
                            <tr>
                                <td class="fw-semibold small">{{ $p->nasabah?->nama_siswa ?? '-' }}</td>
                                <td><code class="small">{{ $p->nasabah?->no_rekening ?? '-' }}</code></td>
                                <td class="small text-muted">{{ $p->keterangan }}</td>
                                <td class="small text-muted">{{ $p->created_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="status-badge {{ $p->status_badge }}">{{ $p->status_label }}</span>
                                </td>
                                <td class="text-end">
                                    @if ($p->bisa_diproses)
                                        @if ($p->ada_pin_baru)
                                            <form action="{{ route('admin.nasabah.reset-pin.proses', $p->id) }}" method="POST"
                                                  data-confirm="Setujui pengajuan perubahan PIN {{ $p->nasabah?->nama_siswa ?? '' }}? PIN baru akan langsung berlaku."
                                                  data-confirm-title="Setujui Pengajuan PIN">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Setujui">
                                                    <i class="fa-solid fa-check me-1"></i>Setujui
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted small">Menunggu nasabah mengajukan PIN baru</span>
                                        @endif
                                    @else
                                        <span class="text-muted small">&mdash;</span>
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
                        <div class="panel-title"><span class="dot"></span>Grafik Transaksi Mingguan</div>
                        <span class="status-badge blue">{{ now()->format('d M Y') }}</span>
                    </div>
                    <div class="panel-body p-3">
                        <div class="chart-container">
                            <canvas id="chartTransaksi"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="data-panel h-100">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot" style="background:var(--purple)"></span>User Terbaru</div>
                        <a href="{{ route('admin.users.index') }}" class="small fw-semibold text-primary">Lihat semua</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($userTerbaru as $u)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar rounded-circle" style="width:30px;height:30px;font-size:.7rem;">{{ strtoupper(substr($u->nama_petugas, 0, 1)) }}</div>
                                                <div>
                                                    <div class="fw-semibold small">{{ $u->nama_petugas }}</div>
                                                    <div class="text-muted" style="font-size:.68rem;"><code>{{ $u->username }}</code></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $roleCls = match ($u->role) {
                                                    'admin' => 'danger', 'teller' => 'info', 'supervisor' => 'warning',
                                                    default => 'gray',
                                                };
                                            @endphp
                                            <span class="badge badge-soft-{{ $roleCls }}">{{ $u->role_label }}</span>
                                        </td>
                                        <td>
                                            <span class="status-badge {{ $u->status === 'active' ? 'green' : 'gray' }}">{{ ucfirst($u->status) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-users-slash"></i></div><div class="empty-title">Belum ada user</div></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const labels = @json($grafikTransaksi['labels']);
        const setoran = @json($grafikTransaksi['setoran']);
        const penarikan = @json($grafikTransaksi['penarikan']);

        new Chart(document.getElementById('chartTransaksi'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Setoran', data: setoran, backgroundColor: '#16A34A', borderRadius: 4, barPercentage: 0.6 },
                    { label: 'Penarikan', data: penarikan, backgroundColor: '#DC2626', borderRadius: 4, barPercentage: 0.6 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
                scales: { y: { beginAtZero: true, grid: { color: '#EEF1F7' }, ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID'), font: { size: 11 } } } },
            },
        });
    </script>
@endpush
