@extends('layouts.app')

@section('title', 'Pengajuan Perubahan PIN')
@section('page-title', 'Persetujuan Pengajuan PIN')
@section('page-subtitle', 'Admin dan Supervisor memiliki hak persetujuan yang sama. Cukup salah satu memproses.')

@section('list-page-class', 'list-page')
@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Persetujuan Perubahan PIN</h1>
                <div class="page-header-sub">
                    Nasabah mengajukan PIN baru, lalu petugas menyetujui atau menolak.
                </div>
            </div>
            <div class="header-actions">
                @if ($pending > 0)
                    <span class="status-badge orange"><i class="fa-solid fa-clock me-1"></i>{{ $pending }} menunggu</span>
                @else
                    <span class="status-badge green">Tidak ada antrean</span>
                @endif
            </div>
        </div>

        <div class="alert alert-info small">
            <i class="fa-solid fa-shield-halved me-1"></i>
            Demi keamanan, PIN baru <strong>tidak pernah ditampilkan</strong> di halaman ini.
            Proses ini tidak memerlukan Anda memasukkan PIN baru. Pada persetujuan, sistem otomatis
            menerapkan PIN baru yang sudah dikirim nasabah. PIN lama tetap berlaku bila pengajuan ditolak.
        </div>

        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot" style="background:var(--orange)"></span>Daftar Pengajuan</div>
                <form method="GET" class="d-flex gap-2">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" @selected($status === 'all' || ! $status)>Semua Status</option>
                        <option value="pending" @selected($status === 'pending')>Menunggu Persetujuan</option>
                        <option value="diproses" @selected($status === 'diproses')>Disetujui</option>
                        <option value="ditolak" @selected($status === 'ditolak')>Ditolak</option>
                    </select>
                    <button class="btn btn-sm btn-primary"><i class="fa-solid fa-filter"></i></button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nasabah</th>
                            <th>No. Rekening</th>
                            <th>Kelas</th>
                            <th>Waktu Pengajuan</th>
                            <th>Status</th>
                            <th>Alasan Penolakan</th>
                            <th>Diproses Oleh</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($permintaan as $p)
                            <tr>
                                <td class="fw-semibold small">{{ $p->nasabah?->nama_siswa ?? '-' }}</td>
                                <td><code class="small">{{ $p->nasabah?->no_rekening ?? '-' }}</code></td>
                                <td class="small">{{ $p->nasabah?->kelas ?? '-' }}</td>
                                <td class="small text-muted">{{ $p->created_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="status-badge {{ $p->status_badge }}">{{ $p->status_label }}</span>
                                </td>
                                <td class="small text-muted">{{ $p->alasan_penolakan ?? '-' }}</td>
                                <td class="small text-muted">
                                    @if ($p->pemroses)
                                        {{ $p->pemroses->nama_petugas }}
                                        <div class="text-muted" style="font-size:.72rem;">
                                            {{ $p->diproses_at?->format('d/m/Y H:i') }}
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($p->bisa_diproses)
                                        <div class="d-flex gap-1 justify-content-end">
                                            @if ($p->ada_pin_baru)
                                                <form method="POST" action="{{ route('permintaan-pin.setujui', $p->id) }}"
                                                      data-confirm="Setujui pengajuan perubahan PIN {{ $p->nasabah?->nama_siswa ?? '' }}? PIN baru akan langsung berlaku."
                                                      data-confirm-title="Setujui Pengajuan PIN">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success" title="Setujui">
                                                        <i class="fa-solid fa-check me-1"></i>Setujui
                                                    </button>
                                                </form>
                                            @else
                                                <span class="small text-danger align-self-center">PIN baru tidak tersimpan</span>
                                            @endif
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                                    data-bs-target="#modalTolak{{ $p->id }}" title="Tolak">
                                                <i class="fa-solid fa-xmark me-1"></i>Tolak
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-muted small">Selesai diproses</span>
                                    @endif
                                </td>
                            </tr>

                            @if ($p->bisa_diproses)
                                <div class="modal fade" id="modalTolak{{ $p->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('permintaan-pin.tolak', $p->id) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h6 class="modal-title">Tolak Pengajuan PIN</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="small text-muted">
                                                        Nasabah: <strong>{{ $p->nasabah?->nama_siswa ?? '-' }}</strong>
                                                        ({{ $p->nasabah?->no_rekening ?? '-' }})
                                                    </p>
                                                    <label class="form-label" for="alasanTolak{{ $p->id }}">Alasan Penolakan</label>
                                                    <textarea name="alasan_penolakan" id="alasanTolak{{ $p->id }}"
                                                              class="form-control @error('alasan_penolakan') is-invalid @enderror"
                                                              rows="3" maxlength="500" required
                                                              placeholder="Contoh: Cocokkan data nasabah dengan petugas loket terlebih dahulu."></textarea>
                                                    @error('alasan_penolakan')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                    <div class="form-text">Alasan ini akan dilihat oleh nasabah. PIN lama tetap digunakan.</div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-sm btn-danger">Tolak Pengajuan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-key"></i></div>
                                        <div class="empty-title">Belum ada pengajuan perubahan PIN</div>
                                        <div class="empty-desc">Pengajuan dari nasabah akan tampil di sini.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($permintaan->hasPages())
                <div class="p-2 border-top">{{ $permintaan->links() }}</div>
            @endif
        </div>
    </div>
@endsection
