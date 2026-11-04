@extends('layouts.app')

@section('title', 'Backup Database')
@section('page-title', 'Backup Database')
@section('page-subtitle', 'Kelola salinan cadangan data bank mini')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Backup Database</h1>
                <div class="page-header-sub">Kelola salinan cadangan data bank mini</div>
            </div>
        </div>

        <div class="data-panel mb-3">
            <div class="panel-body py-3 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="small text-muted">
                    <i class="fa-solid fa-database me-1"></i>
                    Salinan database tersimpan di <code>storage/app/backups</code>.
                    Skedul otomatis dapat dijalankan lewat perintah <code>php artisan bankmini:backup</code>.
                </div>
                <form action="{{ route('admin.backup.store') }}" method="POST">
                    @csrf
                    <button class="btn btn-success btn-sm"><i class="fa-solid fa-shield-halved me-1"></i>Buat Backup Sekarang</button>
                </form>
            </div>
        </div>

        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Daftar Backup</div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Nama File</th>
                            <th class="text-end">Ukuran</th>
                            <th class="text-end">Dibuat</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($files as $file)
                            <tr>
                                <td class="fw-semibold small"><i class="fa-solid fa-file-shield me-1 text-success"></i>{{ $file->getFilename() }}</td>
                                <td class="num small text-end">{{ number_format($file->getSize() / 1024, 1) }} KB</td>
                                <td class="small text-end">{{ \Carbon\Carbon::createFromTimestamp($file->getMTime())->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.backup.download', $file->getFilename()) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-download"></i></a>
                                    <form action="{{ route('admin.backup.destroy', $file->getFilename()) }}" method="POST" class="d-inline" data-confirm="Hapus backup ini?" data-confirm-title="Hapus Backup">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-server"></i></div>
                                        <div class="empty-title">Belum ada backup</div>
                                        <div class="empty-desc">Buat backup pertama untuk mengamankan data.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
