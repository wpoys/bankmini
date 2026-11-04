@extends('layouts.app')

@section('title', 'Hasil Import Nasabah')
@section('page-title', 'Laporan Hasil Import')
@section('page-subtitle', 'Rekapitulasi proses import data nasabah')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Laporan Hasil Import</h1>
                <div class="page-header-sub">Rekapitulasi proses import data nasabah</div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Berhasil Dibuat</span>
                        <span class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-circle-check"></i></span>
                    </div>
                    <div class="stat-value">{{ number_format($hasil['berhasil'], 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Gagal / Dilewati</span>
                        <span class="stat-icon bg-danger-subtle text-danger"><i class="fa-solid fa-circle-xmark"></i></span>
                    </div>
                    <div class="stat-value">{{ number_format($hasil['gagal'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Detail Kesalahan</div>
            </div>
            <div class="panel-body p-0">
                @if (empty($hasil['kesalahan']))
                    <div class="empty-state py-5">
                        <div class="empty-icon text-success"><i class="fa-solid fa-check-double"></i></div>
                        <div class="empty-title">Semua data berhasil diimpor</div>
                        <div class="empty-desc">Tidak ada kesalahan pada proses import.</div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hasil['kesalahan'] as $i => $err)
                                    <tr>
                                        <td class="small text-muted">{{ $i + 1 }}</td>
                                        <td class="small text-danger">{{ $err }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="p-2 d-flex gap-2 border-top flex-wrap">
                <a href="{{ route('admin.nasabah.index') }}" class="btn btn-primary"><i class="fa-solid fa-address-book me-1"></i>Lihat Daftar Nasabah</a>
                <a href="{{ route('admin.nasabah.import') }}" class="btn btn-outline-primary"><i class="fa-solid fa-file-import me-1"></i>Import Lagi</a>
            </div>
        </div>
    </div>
@endsection
