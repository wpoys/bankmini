@extends('layouts.app')

@section('title', 'Pusat Notifikasi')
@section('page-title', 'Pusat Notifikasi')
@section('page-subtitle', 'Kelola dan pantau pemberitahuan akun Anda')
@section('body-class', 'notification-center-page')

@section('content')
    <div class="notification-center">
        <section class="notification-list-panel">
            <div class="notification-tabs">
                <a class="{{ $status === 'semua' ? 'active' : '' }}" href="{{ route('notifikasi.index', array_filter(['q' => $q])) }}">
                    Semua Notifikasi <span>{{ $jumlahTotal }}</span>
                </a>
                <a class="{{ $status === 'belum' ? 'active' : '' }}" href="{{ route('notifikasi.index', array_filter(['status' => 'belum', 'q' => $q])) }}">
                    Belum Dibaca <span>{{ $jumlahBelumDibaca }}</span>
                </a>
            </div>

            <form class="notification-search" method="GET" action="{{ route('notifikasi.index') }}">
                @if ($status === 'belum')
                    <input type="hidden" name="status" value="belum">
                @endif
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="q" value="{{ $q }}" placeholder="Cari notifikasi, transaksi, atau aktivitas..." aria-label="Cari notifikasi">
                @if ($q !== '')
                    <a href="{{ route('notifikasi.index', $status === 'belum' ? ['status' => 'belum'] : []) }}" aria-label="Hapus pencarian"><i class="fa-solid fa-xmark"></i></a>
                @endif
            </form>

            <div class="notification-list-heading">
                <label><input type="checkbox" aria-label="Pilih semua notifikasi"> Pilih Semua</label>
                <span>{{ $notifikasi->total() }} notifikasi</span>
            </div>

            <div class="notification-list">
                @forelse ($notifikasi as $n)
                    @php $tipe = in_array($n->tipe, ['success', 'warning', 'danger', 'info']) ? $n->tipe : 'info'; @endphp
                    <a class="notification-row {{ ! $n->sudah_dibaca ? 'unread' : '' }} {{ $selected && $selected->id === $n->id ? 'selected' : '' }}" href="{{ route('notifikasi.index', array_filter(['status' => $status === 'belum' ? 'belum' : null, 'q' => $q ?: null, 'selected' => $n->id, 'page' => $notifikasi->currentPage() > 1 ? $notifikasi->currentPage() : null])) }}">
                        <span class="notification-row-icon type-{{ $tipe }}"><i class="fa-solid {{ $n->icon }}"></i></span>
                        <span class="notification-row-content">
                            <span class="notification-row-title">{{ $n->judul }}</span>
                            <span class="notification-row-message">{{ $n->pesan }}</span>
                            <span class="notification-row-meta"><span class="notification-type-label type-{{ $tipe }}">{{ ucfirst($tipe) }}</span><time>{{ $n->created_at->diffForHumans() }}</time></span>
                        </span>
                        @unless ($n->sudah_dibaca)<span class="notification-unread-dot" title="Belum dibaca"></span>@endunless
                    </a>
                @empty
                    <div class="notification-empty"><i class="fa-regular fa-bell"></i><strong>Tidak ada notifikasi</strong><span>Coba ubah filter atau kata kunci pencarian.</span></div>
                @endforelse
            </div>
            @if ($notifikasi->hasPages())
                <div class="notification-pagination">{{ $notifikasi->links() }}</div>
            @endif
        </section>

        <aside class="notification-detail-panel">
            @if ($selected)
                @php $detailType = in_array($selected->tipe, ['success', 'warning', 'danger', 'info']) ? $selected->tipe : 'info'; @endphp
                <div class="notification-detail-toolbar">
                    <span><i class="fa-regular fa-envelope"></i></span>
                    <span><i class="fa-regular fa-flag"></i></span>
                    <form method="POST" action="{{ route('notifikasi.hapus', $selected->id) }}" data-n-konfirmasi="Hapus notifikasi ini?" data-n-judul="Hapus Notifikasi">
                        @csrf @method('DELETE')
                        <button type="submit" aria-label="Hapus notifikasi"><i class="fa-regular fa-trash-can"></i></button>
                    </form>
                    <button type="button" aria-label="Menu lainnya"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                </div>
                <div class="notification-detail-content">
                    <div class="notification-detail-heading">
                        <h2>{{ $selected->judul }}</h2>
                        @if ($selected->sudah_dibaca)
                            <span class="notification-status read"><i class="fa-solid fa-check"></i> Sudah Dibaca</span>
                        @else
                            <span class="notification-status unread"><i class="fa-solid fa-circle"></i> Belum Dibaca</span>
                        @endif
                    </div>
                    <div class="notification-detail-type type-{{ $detailType }}"><span class="notification-row-icon type-{{ $detailType }}"><i class="fa-solid {{ $selected->icon }}"></i></span><span><strong>{{ ucfirst($detailType) }}</strong><small>{{ $selected->created_at->translatedFormat('d F Y, H:i') }}</small></span></div>
                    <div class="notification-detail-body">
                        <h3>Rincian</h3>
                        <p>{{ $selected->pesan }}</p>
                    </div>
                    @if ($selected->url)
                        <a class="notification-detail-link" href="{{ $selected->url }}"><i class="fa-solid fa-arrow-up-right-from-square"></i> Buka informasi terkait</a>
                    @endif
                    @unless ($selected->sudah_dibaca)
                        <form method="POST" action="{{ route('notifikasi.baca', $selected->id) }}" class="notification-read-form">
                            @csrf
                            <button type="submit"><i class="fa-solid fa-check"></i> Tandai Sudah Dibaca</button>
                        </form>
                    @endunless
                </div>
            @else
                <div class="notification-detail-empty"><span><i class="fa-regular fa-envelope-open"></i></span><strong>Pilih notifikasi</strong><p>Pilih salah satu notifikasi dari daftar untuk melihat rincian lengkapnya.</p></div>
            @endif
        </aside>
    </div>
@endsection
