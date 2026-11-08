@php
    $__user = auth()->user();
    $__role = $__user->role;
    $__dashboardRoute = $__role . '.dashboard';

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
            <div class="nav-section">Persetujuan</div>
            <a class="nav-link {{ request()->routeIs('supervisor.pusat-verifikasi') ? 'active' : '' }}"
                href="{{ route('supervisor.pusat-verifikasi') }}" title="Pusat Verifikasi &amp; Pengawasan">
                <i class="fa-solid fa-shield-halved"></i><span class="nav-label">Pusat Verifikasi</span>
            </a>
            <a class="nav-link {{ request()->routeIs('supervisor.verifikasi.*') ? 'active' : '' }}"
                href="{{ route('supervisor.verifikasi.index') }}" title="Verifikasi Laporan Kas">
                <i class="fa-solid fa-clipboard-check"></i><span class="nav-label">Verifikasi Laporan Kas</span>
            </a>

            <div class="nav-section">Akuntansi</div>
            <a class="nav-link {{ request()->routeIs('supervisor.jurnal', 'supervisor.laporan.jurnal') ? 'active' : '' }}"
                href="{{ route('supervisor.jurnal') }}" title="Buku Jurnal">
                <i class="fa-solid fa-scale-balanced"></i><span class="nav-label">Buku Jurnal</span>
            </a>
            <a class="nav-link {{ request()->routeIs('supervisor.laporan.operasional') ? 'active' : '' }}"
                href="{{ route('supervisor.laporan.operasional') }}" title="Laporan Operasional">
                <i class="fa-solid fa-chart-column"></i><span class="nav-label">Laporan Operasional</span>
            </a>

            <div class="nav-section">Pengawasan</div>
            <a class="nav-link {{ request()->routeIs('supervisor.audit') ? 'active' : '' }}"
                href="{{ route('supervisor.audit') }}" title="Audit Trail">
                <i class="fa-solid fa-list-check"></i><span class="nav-label">Audit Trail</span>
            </a>

            <div class="nav-section">Penjadwalan</div>
            <a class="nav-link {{ request()->routeIs('supervisor.jadwal.index') ? 'active' : '' }}"
                href="{{ route('supervisor.jadwal.index') }}" title="Jadwal Petugas">
                <i class="fa-solid fa-calendar-check"></i><span class="nav-label">Jadwal Petugas</span>
            </a>

        @elseif ($__role === 'admin')
            <div class="nav-section">Persetujuan</div>
            <a class="nav-link {{ request()->routeIs('supervisor.pusat-verifikasi') ? 'active' : '' }}"
                href="{{ route('supervisor.pusat-verifikasi') }}" title="Pusat Verifikasi &amp; Pengawasan">
                <i class="fa-solid fa-shield-halved"></i><span class="nav-label">Pusat Verifikasi</span>
            </a>

            <div class="nav-section">Akun</div>
            <a class="nav-link {{ request()->routeIs('admin.pengguna.*', 'admin.users.*', 'admin.nasabah.index', 'admin.nasabah.create', 'admin.nasabah.edit', 'admin.nasabah.restore', 'admin.nasabah.arsip', 'admin.nasabah.import*') ? 'active' : '' }}"
                href="{{ route('admin.pengguna.index') }}" title="Manajemen Pengguna">
                <i class="fa-solid fa-users"></i><span class="nav-label">Manajemen Pengguna</span>
            </a>
            <div class="nav-section">Penjadwalan</div>
            <a class="nav-link {{ request()->routeIs('admin.jadwal.*') ? 'active' : '' }}"
                href="{{ route('admin.jadwal.index') }}" title="Jadwal Petugas">
                <i class="fa-solid fa-calendar-check"></i><span class="nav-label">Jadwal Petugas</span>
            </a>

            <div class="nav-section">Laporan &amp; Pengawasan</div>
            <a class="nav-link {{ request()->routeIs('supervisor.jurnal') ? 'active' : '' }}"
                href="{{ route('supervisor.jurnal') }}" title="Buku Jurnal">
                <i class="fa-solid fa-file-lines"></i><span class="nav-label">Buku Jurnal</span>
            </a>
            <a class="nav-link {{ request()->routeIs('supervisor.verifikasi.*') ? 'active' : '' }}"
                href="{{ route('supervisor.verifikasi.index') }}" title="Verifikasi Laporan Kas">
                <i class="fa-solid fa-clipboard-check"></i><span class="nav-label">Verifikasi Laporan Kas</span>
            </a>
            <a class="nav-link {{ request()->routeIs('supervisor.audit') ? 'active' : '' }}"
                href="{{ route('supervisor.audit') }}" title="Audit Trail">
                <i class="fa-solid fa-list-check"></i><span class="nav-label">Audit Trail</span>
            </a>

            <div class="nav-section">Sistem</div>
            <a class="nav-link {{ request()->routeIs('admin.konfigurasi.*', 'admin.backup.*') ? 'active' : '' }}"
                href="{{ route('admin.konfigurasi.index') }}" title="Pengaturan &amp; Backup">
                <i class="fa-solid fa-gear"></i><span class="nav-label">Pengaturan &amp; Backup</span>
            </a>

        @elseif ($__role === 'teller')
            <div class="nav-section">Operasional</div>
            <a class="nav-link {{ request()->routeIs('teller.laporan.index') ? 'active' : '' }}"
                href="{{ route('teller.laporan.index') }}" title="Laporan Kas Harian">
                <i class="fa-solid fa-file-lines"></i><span class="nav-label">Laporan Kas Harian</span>
            </a>
            <a class="nav-link {{ request()->routeIs('teller.koreksi.*') ? 'active' : '' }}"
                href="{{ route('teller.koreksi.index') }}" title="Pengajuan Koreksi">
                <i class="fa-solid fa-rotate-left"></i><span class="nav-label">Pengajuan Koreksi</span>
            </a>

            <div class="nav-section">Penjadwalan</div>
            <a class="nav-link {{ request()->routeIs('teller.jadwal.*') ? 'active' : '' }}"
                href="{{ route('teller.jadwal.index') }}" title="Jadwal Petugas">
                <i class="fa-solid fa-calendar-check"></i><span class="nav-label">Jadwal Petugas</span>
            </a>

        @endif
    </div>
    
    <div class="sidebar-foot">
        <div class="status-item">
            <span class="status-dot"></span>
            <span class="foot-label">Loket {{ $__role === 'teller' ? 'Terhubung' : 'Operasional' }}</span>
        </div>
    </div>
</aside>
