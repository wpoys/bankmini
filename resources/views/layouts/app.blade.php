<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BANK MINI — Sistem Informasi E-Teller')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <script>
        try {
            if (localStorage.getItem('bankmini.sidebar.mini') === '1') {
                document.documentElement.classList.add('sidebar-mini');
            }
        } catch (e) {}
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="@yield('body-class', auth()->check() ? 'role-' . auth()->user()->role : '')">
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    @yield('auth-body')

    @hasSection('standalone-layout')
        <main class="standalone-page">
            @yield('content')
        </main>
    @else
    @unless (in_array(Route::currentRouteName(), ['login', 'password.request', 'password.buat-password']))
        @include('layouts.sidebar')

        @php
            $user = auth()->user();
            $profilUrl = route('profil');
            $notifikasiCount = $user->userNotifications()->belumDibaca()->count();
            $notifikasiTerbaru = $user->userNotifications()->latest()->take(6)->get();
        @endphp

        <div class="main-content">
            <header class="topbar">
                <div class="d-flex align-items-center gap-3 flex-grow-1">
                    <button class="icon-btn d-lg-none" id="sidebarToggle" type="button" aria-label="Buka menu navigasi">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <div class="page-title">@yield('page-title', 'Dashboard')</div>
                        <div class="page-subtitle">@yield('page-subtitle', '')</div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 header-extra">
                    <div class="d-none d-xl-flex align-items-center gap-2 header-date">
                        <i class="fa-solid fa-calendar-days"></i>
                        <span>{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
                    </div>

                    <div class="topbar-divider d-none d-md-block"></div>

                    <div class="dropdown">
                        <button class="icon-btn position-relative" type="button" data-bs-toggle="dropdown"
                            data-bs-auto-close="outside" aria-expanded="false" aria-label="Notifikasi" title="Notifikasi">
                            <i class="fa-solid fa-bell"></i>
                            @if ($notifikasiCount > 0)
                                <span class="notif-dot">{{ $notifikasiCount }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0 shadow-sm" style="width:340px;">
                            <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                                <span class="fw-bold small">Notifikasi</span>
                                @if ($notifikasiCount > 0)
                                    <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                                        @csrf
                                        <button class="btn btn-sm btn-link p-0 text-decoration-none small">Tandai semua
                                            dibaca</button>
                                    </form>
                                @endif
                            </div>
                            <div class="list-group list-group-flush" style="max-height:320px;overflow-y:auto;">
                                @forelse ($notifikasiTerbaru as $n)
                                    <form method="POST" action="{{ route('notifikasi.baca', $n->id) }}"
                                        class="list-group-item list-group-item-action p-0">
                                        @csrf
                                        <button type="submit"
                                            class="btn w-100 text-start justify-content-start text-wrap d-flex gap-2 rounded-0 border-0 py-2 px-3 {{ $n->sudah_dibaca ? '' : 'bg-light' }}">
                                            <i
                                                class="fa-solid {{ $n->icon }} mt-1 {{ $n->tipe === 'danger' ? 'text-danger' : 'text-' . $n->tipe }}"></i>
                                            <span class="w-100">
                                                <span class="fw-semibold small d-block">{{ $n->judul }}</span>
                                                <span class="text-muted small text-truncate d-block mb-1">{{ $n->pesan }}</span>
                                                <span class="text-muted d-block"
                                                    style="font-size:.7rem;">{{ $n->created_at->diffForHumans() }}</span>
                                            </span>
                                            @if (!$n->sudah_dibaca)
                                                <span class="mt-1"><i class="fa-solid fa-circle text-primary"
                                                        style="font-size:.55rem;"></i></span>
                                            @endif
                                        </button>
                                    </form>
                                @empty
                                    <div class="px-3 py-4 text-center text-muted small">Belum ada notifikasi</div>
                                @endforelse
                            </div>
                            <div class="text-center py-2 border-top bg-white">
                                <a href="{{ route('notifikasi.index') }}"
                                    class="small text-decoration-none fw-semibold">Lihat Semua Notifikasi</a>
                            </div>
                        </div>
                    </div>

                    <button class="icon-btn d-none d-md-inline-flex" type="button" title="Fullscreen"
                        aria-label="Mode layar penuh" onclick="document.documentElement.requestFullscreen()">
                        <i class="fa-solid fa-expand"></i>
                    </button>

                    <div class="dropdown">
                        <a class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle user-chip" href="#"
                            role="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                            <div class="avatar rounded-circle" style="width:38px;height:38px;font-size:.85rem;">
                                @if ($user->foto)
                                    <img src="{{ asset('storage/' . $user->foto) }}" alt="Foto {{ $user->nama_petugas }}">
                                @else
                                    {{ strtoupper(substr($user->nama_petugas, 0, 1)) }}
                                @endif
                            </div>
                            <div class="d-none d-md-block" style="line-height:1.1;">
                                <div class="small fw-bold text-dark">{{ $user->nama_petugas }}</div>
                                <div class="text-muted" style="font-size:.68rem;">
                                    {{ $user->role_label }}
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow-sm p-1 mx-2 mt-1" style="width:270px;">
                            <div class="px-3 py-3 d-flex align-items-center gap-3 border-bottom mb-1">
                                <div class="avatar rounded-circle flex-shrink-0"
                                    style="width:46px;height:46px;font-size:1rem;">
                                    @if ($user->foto)
                                        <img src="{{ asset('storage/' . $user->foto) }}" alt="Foto {{ $user->nama_petugas }}">
                                    @else
                                        {{ strtoupper(substr($user->nama_petugas, 0, 1)) }}
                                    @endif
                                </div>
                                <div class="text-truncate">
                                    <div class="fw-bold text-truncate small">{{ $user->nama_petugas }}</div>
                                    <div class="text-muted small text-truncate">{{ $user->email ?? '@' . $user->username }}
                                    </div>
                                    <span class="badge badge-soft-primary mt-1">{{ $user->role_label }}</span>
                                </div>
                            </div>
                            <a href="{{ $profilUrl }}" class="dropdown-item py-2">
                                <i class="fa-solid fa-id-badge me-2 text-muted"></i>Lihat Profil
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-danger">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i>Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="page-body">
                @yield('content')
            </main>

            <footer class="app-footer">
                &copy; {{ date('Y') }} BANK MINI Sekolah &middot; Sistem Informasi E-Teller. Dibuat untuk keperluan UKK.
            </footer>
        </div>
    @endunless
    @endif

    @if (session('success'))
        <div class="toast-container notify-holder">
            <div class="toast-notif success" role="alert">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="toast-container notify-holder">
            <div class="toast-notif danger" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="modal fade" id="modalKonfirmasi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="konfirmasiJudul">Konfirmasi Tindakan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="konfirmasiPesan"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-sm btn-danger" id="btnKonfirmasiYa">Ya, Lanjutkan</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    @stack('scripts')
</body>

</html>