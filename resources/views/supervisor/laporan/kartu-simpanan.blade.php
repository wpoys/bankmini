@extends('layouts.app')

@section('title', 'Kartu Simpanan')
@section('page-title', 'Kartu Simpanan')
@section('page-subtitle', 'Daftar nasabah simpanan')

@section('list-page-class', 'list-page')
@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Kartu Simpanan</h1>
                <div class="page-header-sub">Daftar seluruh nasabah simpanan</div>
            </div>
        </div>

        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Daftar Nasabah</div>
                <span class="small text-muted">{{ number_format($nasabahList->total(), 0, ',', '.') }} nasabah</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>No. Rekening</th>
                            <th>Nama Nasabah</th>
                            <th>Kelas</th>
                            <th>Produk</th>
                            <th class="text-end">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($nasabahList as $n)
                            <tr>
                                <td><code class="small">{{ $n->no_rekening }}</code></td>
                                <td class="fw-semibold small">{{ $n->nama_siswa }}</td>
                                <td class="small">{{ $n->kelas }}</td>
                                <td class="small">{{ $n->produk_label }}</td>
                                <td class="num small text-end">Rp {{ number_format($n->saldo, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-id-card"></i></div>
                                        <div class="empty-title">Belum ada nasabah</div>
                                        <div class="empty-desc">Data nasabah akan tampil di sini.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($nasabahList->hasPages())
                <div class="p-2 border-top">{{ $nasabahList->links() }}</div>
            @endif
        </div>
    </div>
@endsection
