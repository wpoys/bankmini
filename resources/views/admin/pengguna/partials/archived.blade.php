<div class="data-panel">
    <div class="panel-head">
        <div class="panel-title"><span class="dot"></span>Nasabah diarsipkan</div>
        <div class="table-toolbar">
            <form class="d-flex gap-2 align-items-center flex-wrap" method="GET" action="{{ route('admin.pengguna.index') }}">
                <input type="hidden" name="tab" value="archived">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="archived_search" value="{{ $archivedSearch }}" class="form-control form-control-sm" placeholder="Cari nama / NIS / no. rekening">
                </div>
                <select name="archived_kelas" class="form-select form-select-sm" style="width:auto;">
                    <option value="">Semua Kelas</option>
                    @foreach (\App\Models\Nasabah::KELAS_GROUPS as $jurusan => $kelasPilihan)
                        <optgroup label="{{ $jurusan }}">
                            @foreach ($kelasPilihan as $pilihan)
                                <option value="{{ $pilihan }}" @selected($archivedClass === $pilihan)>{{ $pilihan }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary"><i class="fa-solid fa-filter"></i></button>
            </form>
            <div id="arsip-bulk-toolbar" class="d-none align-items-center gap-2 flex-wrap">
                <span><strong id="arsip-selected-count">0</strong> terpilih</span>
                <button type="submit" form="arsip-restore-form" class="btn btn-sm btn-outline-warning">Pulihkan Terpilih</button>
                <button type="submit" form="arsip-delete-form" class="btn btn-sm btn-danger">Hapus Permanen</button>
            </div>
        </div>
    </div>
    <form id="arsip-restore-form" method="POST" action="{{ route('admin.pengguna.pulihkan-terpilih') }}">@csrf</form>
    <form id="arsip-delete-form" method="POST" action="{{ route('admin.pengguna.hapus-terpilih') }}">@csrf</form>
    <div class="table-responsive">
        <table class="table table-hover align-middle table-nowrap mb-0">
            <thead>
                <tr>
                    <th><input type="checkbox" id="arsip-select-all" aria-label="Pilih semua nasabah di halaman"></th>
                    <th>#</th>
                    <th>No. Rekening</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Username / NIS</th>
                    <th>Kelas</th>
                    <th>Produk</th>
                    <th>Role</th>
                    <th class="text-end">Saldo</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($archivedNasabah as $i => $nasabah)
                    @php
                        $saldo = (int) $nasabah->saldo;
                        $hapusPrompt = $saldo > 0
                            ? 'Yakin untuk menghapus nasabah '.$nasabah->nama_siswa.' secara permanen? Saldo masih mengendap sebesar Rp '.number_format($saldo, 0, ',', '.').'. Jika Anda yakin, akun dan saldo tetap akan dihapus; riwayat transaksi dipertahankan.'
                            : 'Yakin untuk menghapus nasabah '.$nasabah->nama_siswa.' secara permanen? Riwayat transaksi akan tetap dipertahankan.';
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="arsip-row-select" value="{{ $nasabah->id }}" aria-label="Pilih {{ $nasabah->nama_siswa }}"></td>
                        <td class="small text-muted">{{ $archivedNasabah->firstItem() + $i }}</td>
                        <td class="fw-semibold small"><code>{{ $nasabah->no_rekening ?: '—' }}</code></td>
                        <td class="small">{{ $nasabah->nis ?: '—' }}</td>
                        <td class="fw-semibold small">{{ $nasabah->nama_siswa }}</td>
                        <td class="small"><code>{{ $nasabah->user?->username ?: $nasabah->nis }}</code></td>
                        <td class="small">{{ $nasabah->kelas ?: '—' }}</td>
                        <td class="small text-capitalize">{{ $nasabah->produk ?: '-' }}</td>
                        <td><span class="badge badge-soft-success">Nasabah</span></td>
                        <td class="num fw-semibold small text-end">Rp {{ number_format($saldo, 0, ',', '.') }}</td>
                        <td><span class="status-badge gray">Arsip</span></td>
                        <td class="text-end text-nowrap">
                            <form action="{{ route('admin.nasabah.restore', $nasabah) }}" method="POST" class="d-inline" data-confirm="Pulihkan nasabah {{ $nasabah->nama_siswa }}?" data-confirm-title="Pulihkan Nasabah">
                                @csrf
                                <button class="btn btn-sm btn-outline-warning" title="Pulihkan"><i class="fa-solid fa-rotate-left"></i></button>
                            </form>
                            <form action="{{ route('admin.nasabah.destroy', $nasabah) }}" method="POST" class="d-inline" data-confirm="{{ $hapusPrompt }}" data-confirm-title="Hapus Permanen Nasabah">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Hapus permanen"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-box-archive"></i></div><div class="empty-title">Tidak ada nasabah diarsipkan</div></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($archivedNasabah->hasPages())
        <div class="p-2 border-top">{{ $archivedNasabah->links() }}</div>
    @endif
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const rows = Array.from(document.querySelectorAll('.arsip-row-select'));
        const selectAll = document.getElementById('arsip-select-all');
        const toolbar = document.getElementById('arsip-bulk-toolbar');
        const count = document.getElementById('arsip-selected-count');
        const restoreForm = document.getElementById('arsip-restore-form');
        const deleteForm = document.getElementById('arsip-delete-form');

        const updateSelection = () => {
            const selected = rows.filter((row) => row.checked);
            count.textContent = selected.length;
            toolbar.classList.toggle('d-none', selected.length === 0);
            toolbar.classList.toggle('d-flex', selected.length > 0);
            selectAll.checked = rows.length > 0 && selected.length === rows.length;
            [restoreForm, deleteForm].forEach((form) => {
                form.querySelectorAll('input[name="ids[]"]').forEach((input) => input.remove());
                selected.forEach((row) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = row.value;
                    form.appendChild(input);
                });
            });
        };

        rows.forEach((row) => row.addEventListener('change', updateSelection));
        selectAll.addEventListener('change', () => {
            rows.forEach((row) => { row.checked = selectAll.checked; });
            updateSelection();
        });
        deleteForm.addEventListener('submit', (event) => {
            if (!confirm('Yakin untuk menghapus nasabah terpilih secara permanen? Saldo yang masih mengendap akan ikut terhapus. Riwayat transaksi akan tetap dipertahankan; nasabah yang memiliki transaksi tidak dapat dihapus.')) event.preventDefault();
        });
        restoreForm.addEventListener('submit', (event) => {
            if (!confirm('Pulihkan seluruh nasabah terpilih dan aktifkan kembali akun mereka?')) event.preventDefault();
        });
    });
</script>
