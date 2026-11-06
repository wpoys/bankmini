<div class="data-panel">
    <div class="panel-head">
        <div class="panel-title"><span class="dot"></span>Riwayat Import CSV</div>
        <a href="{{ route('admin.pengguna.index', ['tab' => 'import']) }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-file-import me-1"></i>Import Baru</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-nowrap mb-0">
            <thead><tr><th>Waktu</th><th>Petugas</th><th>Nama File</th><th class="text-end">Total</th><th class="text-end">Berhasil</th><th class="text-end">Gagal</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($batches as $batch)
                    <tr>
                        <td class="small">{{ $batch->created_at->format('d/m/Y H:i') }}</td>
                        <td class="small">{{ $batch->importer?->nama_petugas ?? $batch->importer?->username ?? 'Akun dihapus' }}</td>
                        <td class="small"><code>{{ $batch->file_name }}</code></td>
                        <td class="num text-end">{{ number_format($batch->total_rows, 0, ',', '.') }}</td>
                        <td class="num text-end text-success">{{ number_format($batch->imported_rows, 0, ',', '.') }}</td>
                        <td class="num text-end text-danger">{{ number_format($batch->failed_rows ?: $batch->invalid_rows, 0, ',', '.') }}</td>
                        <td><span class="status-badge {{ $batch->status === 'completed' ? 'green' : 'orange' }}">{{ $batch->status === 'completed' ? 'Selesai' : 'Pratinjau' }}</span></td>
                        <td><a href="{{ route('admin.nasabah.import.history.show', $batch) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="empty-state py-4"><div class="empty-icon"><i class="fa-solid fa-clock-rotate-left"></i></div><div class="empty-title">Belum ada riwayat import</div></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($batches->hasPages())<div class="p-2 border-top">{{ $batches->links() }}</div>@endif
</div>
