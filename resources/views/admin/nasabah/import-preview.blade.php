@extends('layouts.app')

@section('title', 'Pratinjau Import Nasabah')
@section('page-title', 'Pratinjau Import Nasabah')
@section('page-subtitle', 'Periksa data sebelum disimpan')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Pratinjau Import Nasabah</h1>
                <div class="page-header-sub">Periksa data sebelum disimpan</div>
            </div>
        </div>

        <div class="alert alert-info small d-flex align-items-center gap-2">
            <i class="fa-solid fa-eye"></i>
            <span>
                Ditemukan <strong>{{ number_format($rows->count(), 0, ',', '.') }}</strong> baris data —
                <span class="text-success"><strong>{{ number_format($jumlahValid, 0, ',', '.') }}</strong> valid</span> ·
                <span class="text-danger"><strong>{{ number_format($jumlahGagal, 0, ',', '.') }}</strong> bermasalah</span>.
                Baris yang bermasalah tidak akan disimpan.
            </span>
        </div>

        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Pratinjau Data</div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Baris</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Produk</th>
                            <th>Email</th>
                            <th>Saldo Awal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="{{ $row['valid'] ? '' : 'table-danger' }}">
                                <td class="small text-muted">{{ $row['row'] }}</td>
                                <td class="fw-semibold small"><code>{{ $row['nis'] }}</code></td>
                                <td class="small">{{ $row['nama'] }}</td>
                                <td class="small">{{ $row['kelas'] }}</td>
                                <td class="small">{{ ucfirst($row['produk']) }}</td>
                                <td class="small">{{ $row['email'] ?: '—' }}</td>
                                <td class="small">Rp {{ number_format($row['saldo_awal'], 0, ',', '.') }}</td>
                                <td>
                                    @if ($row['valid'])
                                        <span class="status-badge green">Valid</span>
                                    @else
                                        <span class="status-badge red">Gagal</span>
                                        <div class="text-danger small mt-1">{{ implode(' ', $row['errors']) }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-2 d-flex justify-content-between align-items-center gap-2 border-top flex-wrap">
                <a href="{{ route('admin.nasabah.import') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Ubah File</a>
                <div class="d-flex gap-2">
                    <form action="{{ route('admin.nasabah.import.konfirmasi') }}" method="POST">
                        @csrf
                        <button class="btn btn-primary" {{ $jumlahValid === 0 ? 'disabled' : '' }}>
                            <i class="fa-solid fa-check me-1"></i>Konfirmasi & Simpan {{ $jumlahValid }} Data
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
