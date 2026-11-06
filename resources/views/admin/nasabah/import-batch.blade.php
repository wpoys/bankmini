@extends('layouts.app')

@section('title', 'Detail Riwayat Import')
@section('page-title', 'Detail Riwayat Import')
@section('page-subtitle', $batch->file_name)

@section('content')
    <div class="page-animate">
        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="stat-card"><div class="stat-label">Total Baris</div><div class="stat-value">{{ number_format($batch->total_rows, 0, ',', '.') }}</div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-label">Valid Saat Pratinjau</div><div class="stat-value">{{ number_format($batch->valid_rows, 0, ',', '.') }}</div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-label">Berhasil Diimpor</div><div class="stat-value text-success">{{ number_format($batch->imported_rows, 0, ',', '.') }}</div></div></div>
            <div class="col-md-3"><div class="stat-card"><div class="stat-label">Gagal</div><div class="stat-value text-danger">{{ number_format($batch->failed_rows, 0, ',', '.') }}</div></div></div>
        </div>
        <div class="data-panel">
            <div class="panel-head"><div class="panel-title"><span class="dot"></span>Hasil per Baris</div><a href="{{ route('admin.pengguna.index', ['tab' => 'history']) }}" class="btn btn-sm btn-outline-secondary">Kembali ke Riwayat</a></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Baris CSV</th><th>Status</th><th>Kesalahan</th></tr></thead>
                    <tbody>
                        @forelse ($batch->rows as $row)
                            <tr><td>{{ $row->row_number }}</td><td><span class="status-badge {{ $row->status === 'imported' ? 'green' : 'red' }}">{{ $row->status === 'imported' ? 'Berhasil' : 'Gagal' }}</span></td><td class="small">{{ implode(' ', $row->errors ?? []) ?: '—' }}</td></tr>
                        @empty
                            <tr><td colspan="3"><div class="empty-state py-4"><div class="empty-title">Batch ini belum dikonfirmasi</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
