@php
    $__user = auth()->user();
    $__role = $__user->role;
    $__dashboardRoute = $__role . '.dashboard';

    $__active = request()->route() ? request()->route()->getName() : '';

    $__laporanAktif = false;
    if ($__role === 'teller') {
        $__laporanAktif = request()->routeIs('teller.laporan.index') || request()->routeIs('teller.koreksi.*');
    } elseif ($__role === 'supervisor') {
        $__laporanAktif = request()->routeIs(
            'supervisor.laporan.laporan-harian',
            'supervisor.laporan.buku-besar',
            'supervisor.laporan.mutasi-nasabah',
            'supervisor.laporan.saldo-produk',
            'supervisor.laporan.laporan-tahunan',
        );
    } elseif ($__role === 'admin') {
        $__laporanAktif = request()->routeIs(
            'supervisor.jurnal',
            'supervisor.laporan.*',
            'supervisor.verifikasi.*',
            'supervisor.koreksi.*',
            'supervisor.audit',
        );
    }
@endphp
<aside class="sidebar" id="appSidebar" aria-label="Navigasi utama">

    <div class="brand">
        <a href="{{ route($__dashboardRoute) }}" class="brand-link" aria-label="Beranda BANK MINI">
            <span class="logo"><img src="{{ asset('images/logo smkn 1 bantul.png') }}"
                    alt="Logo SMK Negeri 1 Bantul"></span>
            <span class="brand-text">
                <span class="brand-name">BANK MINI</span>
                <span class="brand-sub">Sistem Informasi E-Teller</span>
            </span>
        </a>
        <button type="button" class="sidebar-toggle" id="sidebarCollapse" aria-expanded="true"
            aria-label="Minimalkan sidebar" title="Minimalkan menu">
            <i class="fa-solid fa-angles-left" aria-hidden="true"></i>
        </button>
    </div>

    <div class="nav-scroll">

        <div class="nav-section">Overview</div>
        <a class="nav-link {{ request()->routeIs($__dashboardRoute) ? 'active' : '' }}"
            href="{{ route($__dashboardRoute) }}" title="Dashboard">
            <i class="fa-solid fa-gauge-high"></i><span class="nav-label">Dashboard</span>
        </a>

        @if ($__role === 'supervisor')
            <a class="nav-link {{ request()->routeIs('supervisor.jadwal.index') ? 'active' : '' }}"
                href="{{ route('supervisor.jadwal.index') }}" title="Jadwal Petugas">
                <i class="fa-solid fa-calendar-check"></i><span class="nav-label">Jadwal Petugas</span>
            </a>

            <div class="nav-section">Laporan</div>
            <a class="nav-link {{ request()->routeIs('supervisor.verifikasi.*') ? 'active' : '' }}"
                href="{{ route('supervisor.verifikasi.index') }}" title="Verifikasi Laporan Harian">
                <i class="fa-solid fa-clipboard-check"></i><span class="nav-label">Verifikasi Laporan Harian</span>
            </a>
            <a class="nav-link {{ request()->routeIs('supervisor.koreksi.*') ? 'active' : '' }}"
                href="{{ route('supervisor.koreksi.index') }}" title="Verifikasi Koreksi">
                <i class="fa-solid fa-rotate-left"></i><span class="nav-label">Verifikasi Koreksi</span>
            </a>
            <a class="nav-link {{ request()->routeIs('supervisor.jurnal', 'supervisor.laporan.jurnal') ? 'active' : '' }}"
                href="{{ route('supervisor.jurnal') }}" title="Buku Jurnal">
                <i class="fa-solid fa-scale-balanced"></i><span class="nav-label">Buku Jurnal</span>
            </a>
            <button type="button" class="nav-link nav-dropdown-toggle {{ $__laporanAktif ? 'active' : '' }}"
                data-bs-toggle="collapse" data-bs-target="#navLaporanAudit" aria-controls="navLaporanAudit"
                aria-expanded="{{ $__laporanAktif ? 'true' : 'false' }}" title="Rekap &amp; Unduh">
                <i class="fa-solid fa-folder-open"></i>
                <span class="nav-label">Rekap &amp; Unduh</span>
                <i class="fa-solid fa-chevron-down nav-arrow" aria-hidden="true"></i>
            </button>
            <div class="collapse submenu {{ $__laporanAktif ? 'show' : '' }}" id="navLaporanAudit">
                <a class="nav-link {{ request()->routeIs('supervisor.laporan.laporan-harian') ? 'active' : '' }}"
                    href="{{ route('supervisor.laporan.laporan-harian') }}" title="Laporan Harian">
                    <i class="fa-solid fa-calendar-day"></i><span class="nav-label">Laporan Harian</span>
                </a>
                <a class="nav-link {{ request()->routeIs('supervisor.laporan.laporan-tahunan') ? 'active' : '' }}"
                    href="{{ route('supervisor.laporan.laporan-tahunan') }}" title="Laporan Tahunan">
                    <i class="fa-solid fa-calendar"></i><span class="nav-label">Laporan Tahunan</span>
                </a>
            </div>

            <a class="nav-link {{ request()->routeIs('supervisor.audit') ? 'active' : '' }}"
                href="{{ route('supervisor.audit') }}" title="Audit Trail">
                <i class="fa-solid fa-list-check"></i><span class="nav-label">Audit Trail</span>
            </a>

        @elseif ($__role === 'admin')
            <div class="nav-section">Akun</div>
            <a class="nav-link {{ request()->routeIs('admin.pengguna.*', 'admin.users.*', 'admin.nasabah.index', 'admin.nasabah.create', 'admin.nasabah.edit', 'admin.nasabah.restore', 'admin.nasabah.arsip') ? 'active' : '' }}"
                href="{{ route('admin.pengguna.index') }}" title="Manajemen Pengguna">
                <i class="fa-solid fa-users"></i><span class="nav-label">Manajemen Pengguna</span>
            </a>
            <a class="nav-link {{ request()->routeIs('admin.nasabah.import*') ? 'active' : '' }}"
                href="{{ route('admin.nasabah.import') }}" title="Import Nasabah">
                <i class="fa-solid fa-file-import"></i><span class="nav-label">Import Nasabah</span>
            </a>

            <div class="nav-section">Penjadwalan</div>
            <a class="nav-link {{ request()->routeIs('admin.jadwal.*') ? 'active' : '' }}"
                href="{{ route('admin.jadwal.index') }}" title="Jadwal Petugas">
                <i class="fa-solid fa-calendar-check"></i><span class="nav-label">Jadwal Petugas</span>
            </a>

            <button type="button" class="nav-link nav-dropdown-toggle {{ $__laporanAktif ? 'active' : '' }}"
                data-bs-toggle="collapse" data-bs-target="#navLaporanKeuangan" aria-controls="navLaporanKeuangan"
                aria-expanded="{{ $__laporanAktif ? 'true' : 'false' }}" title="Laporan Keuangan &amp; Jurnal (Read-Only)">
                <i class="fa-solid fa-scale-balanced"></i>
                <span class="nav-label">Laporan Keuangan &amp; Jurnal</span>
                <i class="fa-solid fa-chevron-down nav-arrow" aria-hidden="true"></i>
            </button>
            <div class="collapse submenu {{ $__laporanAktif ? 'show' : '' }}" id="navLaporanKeuangan">
                <a class="nav-link {{ request()->routeIs('supervisor.jurnal') ? 'active' : '' }}"
                    href="{{ route('supervisor.jurnal') }}" title="Buku Jurnal">
                    <i class="fa-solid fa-file-lines"></i><span class="nav-label">Buku Jurnal</span>
                </a>
                <a class="nav-link {{ request()->routeIs('supervisor.verifikasi.*') ? 'active' : '' }}"
                    href="{{ route('supervisor.verifikasi.index') }}" title="Verifikasi Laporan Harian">
                    <i class="fa-solid fa-clipboard-check"></i><span class="nav-label">Verifikasi Laporan Harian</span>
                </a>
                <a class="nav-link {{ request()->routeIs('supervisor.koreksi.*') ? 'active' : '' }}"
                    href="{{ route('supervisor.koreksi.index') }}" title="Verifikasi Koreksi">
                    <i class="fa-solid fa-rotate-left"></i><span class="nav-label">Verifikasi Koreksi</span>
                </a>
                <a class="nav-link {{ request()->routeIs('supervisor.audit') ? 'active' : '' }}"
                    href="{{ route('supervisor.audit') }}" title="Audit Trail">
                    <i class="fa-solid fa-list-check"></i><span class="nav-label">Audit Trail</span>
                </a>
            </div>

            <div class="nav-section">Sistem</div>
            <a class="nav-link {{ request()->routeIs('admin.konfigurasi.*') ? 'active' : '' }}"
                href="{{ route('admin.konfigurasi.index') }}" title="Konfigurasi">
                <i class="fa-solid fa-gear"></i><span class="nav-label">Konfigurasi</span>
            </a>
            <a class="nav-link {{ request()->routeIs('admin.backup.*') ? 'active' : '' }}"
                href="{{ route('admin.backup.index') }}" title="Backup Database">
                <i class="fa-solid fa-server"></i><span class="nav-label">Backup Database</span>
            </a>

        @elseif ($__role === 'teller')
            <div class="nav-section">Transaksi</div>
            <a class="nav-link {{ request()->routeIs('teller.transaksi.*') ? 'active' : '' }}"
                href="{{ route('teller.transaksi.index') }}" title="Transaksi">
                <i class="fa-solid fa-arrow-right-arrow-left"></i><span class="nav-label">Transaksi</span>
            </a>

            <button type="button" class="nav-link nav-dropdown-toggle {{ $__laporanAktif ? 'active' : '' }}"
                data-bs-toggle="collapse" data-bs-target="#navLaporanAudit" aria-controls="navLaporanAudit"
                aria-expanded="{{ $__laporanAktif ? 'true' : 'false' }}" title="Laporan &amp; Audit">
                <i class="fa-solid fa-folder-open"></i>
                <span class="nav-label">Laporan &amp; Audit</span>
                <i class="fa-solid fa-chevron-down nav-arrow" aria-hidden="true"></i>
            </button>
            <div class="collapse submenu {{ $__laporanAktif ? 'show' : '' }}" id="navLaporanAudit">
                <a class="nav-link {{ request()->routeIs('teller.laporan.index') ? 'active' : '' }}"
                    href="{{ route('teller.laporan.index') }}" title="Laporan Kas Harian">
                    <i class="fa-solid fa-file-lines"></i><span class="nav-label">Laporan Kas Harian</span>
                </a>
                <a class="nav-link {{ request()->routeIs('teller.koreksi.*') ? 'active' : '' }}"
                    href="{{ route('teller.koreksi.index') }}" title="Pengajuan Koreksi">
                    <i class="fa-solid fa-rotate-left"></i><span class="nav-label">Pengajuan Koreksi</span>
                </a>
            </div>

            <div class="nav-section">Penjadwalan</div>
            <a class="nav-link {{ request()->routeIs('teller.jadwal.*') ? 'active' : '' }}"
                href="{{ route('teller.jadwal.index') }}" title="Jadwal Petugas">
                <i class="fa-solid fa-calendar-check"></i><span class="nav-label">Jadwal Petugas</span>
            </a>

        @endif
    </div>
    
    <div class="sidebar-foot">
        <a class="nav-link" href="#" title="Keluar"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            aria-label="Keluar dari aplikasi">
            <i class="fa-solid fa-right-from-bracket"></i><span class="nav-label">Keluar</span>
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
        <div class="status-item">
            <span class="status-dot"></span>
            <span class="foot-label">Loket {{ $__role === 'teller' ? 'Terhubung' : 'Operasional' }}</span>
        </div>
    </div>
</aside>
