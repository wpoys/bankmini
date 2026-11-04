@extends('layouts.app')

@section('title', 'Notifikasi')
@section('standalone-layout', 'true')

@section('content')
    <div class="n-simple-page page-animate">
        <div class="n-simple-head">
            <div class="n-simple-title">
                <span class="n-simple-icon"><i class="fa-regular fa-bell"></i></span>
                <div>
                    <h1>Notifikasi</h1>
                    <p>Pemberitahuan dan informasi terbaru untuk akun Anda.</p>
                </div>
            </div>
            <a class="n-simple-back" href="{{ url('/') }}"><i class="fa-solid fa-arrow-left"></i>Kembali</a>
        </div>

        <div class="n-simple-grid">
            <section class="n-simple-list-card">
                <div class="n-simple-toolbar">
                    <div class="n-simple-tabs" role="tablist">
                        <button type="button" class="is-active" data-n-filter-notif="semua" aria-selected="true">Semua <b>{{ $jumlahTotal }}</b></button>
                        <button type="button" data-n-filter-notif="belum" aria-selected="false">Belum Dibaca <b>{{ $jumlahBelumDibaca }}</b></button>
                    </div>
                    <div class="n-simple-tools">
                        <label><i class="fa-solid fa-magnifying-glass"></i><input type="search" data-n-simple-search placeholder="Cari notifikasi..."></label>
                        <select data-n-simple-period aria-label="Filter periode">
                            <option value="7">7 Hari Terakhir</option>
                            <option value="30">30 Hari Terakhir</option>
                            <option value="all">Semua Periode</option>
                        </select>
                    </div>
                </div>
                <div class="n-simple-notifications">
                    @forelse ($notifikasi as $n)
                        @php $tipe = in_array($n->tipe, ['success', 'warning', 'danger', 'info']) ? $n->tipe : 'info'; @endphp
                        <article class="n-simple-item {{ ! $n->sudah_dibaca ? 'is-unread' : '' }}" data-n-simple-item data-n-notif data-n-unread="{{ $n->sudah_dibaca ? '0' : '1' }}" data-n-search="{{ $n->judul }} {{ $n->pesan }}" data-n-time="{{ $n->created_at->timestamp }}">
                            <span class="n-simple-item-icon n-simple-item-icon--{{ $tipe }}"><i class="fa-solid {{ $n->icon }}"></i></span>
                            <div class="n-simple-item-body">
                                <div class="n-simple-item-top"><strong>{{ $n->judul }}</strong><time>{{ $n->created_at->diffForHumans() }}</time></div>
                                <p>{{ $n->pesan }}</p>
                                <div class="n-simple-actions">
                                    @if (! $n->sudah_dibaca)
                                        <form method="POST" action="{{ route('notifikasi.baca', $n->id) }}">@csrf<button type="submit"><i class="fa-solid fa-check"></i>Tandai dibaca</button></form>
                                    @endif
                                    <form method="POST" action="{{ route('notifikasi.hapus', $n->id) }}" data-n-konfirmasi="Hapus notifikasi ini?" data-n-judul="Hapus Notifikasi">@csrf @method('DELETE')<button type="submit"><i class="fa-regular fa-trash-can"></i>Hapus</button></form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="n-simple-empty"><i class="fa-regular fa-bell"></i><strong>Belum ada notifikasi</strong><span>Notifikasi untuk akun Anda akan tampil di sini.</span></div>
                    @endforelse
                </div>
                @if ($notifikasi->hasPages())
                    <div class="n-simple-pagination">{{ $notifikasi->links() }}</div>
                @endif
            </section>

            <aside class="n-simple-side-card">
                <section>
                    <div class="n-simple-card-title"><strong>Ringkasan Status</strong><span>LIVE</span></div>
                    <div class="n-simple-stats">
                        <div><strong>{{ $jumlahBelumDibaca }}</strong><span>BELUM DIBACA</span></div>
                        <div><strong>{{ $jumlahSudahDibaca }}</strong><span>SUDAH DIBACA</span></div>
                    </div>
                    <div class="n-simple-total"><strong>{{ $jumlahTotal }}</strong><span>TOTAL MASUK PER NOTIFIKASI</span></div>
                    <div class="n-simple-sync"><i class="fa-solid fa-arrows-rotate"></i><span><strong>Sinkronisasi Otomatis</strong><small>Notifikasi diperbarui saat halaman dibuka</small></span></div>
                </section>
                <section class="n-simple-preferences">
                    <div class="n-simple-card-title"><strong>Preferensi Notifikasi Cepat</strong></div>
                    <label><span><strong>Transaksi Loket Kasir</strong><small>Setoran tunai & penarikan buku kas</small></span><input type="checkbox" checked><i></i></label>
                    <label><span><strong>Keamanan & Privasi</strong><small>Pengajuan PIN, reset & log sesi</small></span><input type="checkbox" checked><i></i></label>
                    <label><span><strong>Pengumuman Sekolah</strong><small>Informasi operasional & kegiatan</small></span><input type="checkbox" checked><i></i></label>
                    <button type="button" class="n-simple-save">Simpan Preferensi</button>
                </section>
            </aside>
        </div>
    </div>
@endsection
