<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="data-panel mb-3">
            <div class="panel-head"><div class="panel-title"><span class="dot"></span>Unggah Data Nasabah Massal</div></div>
            <div class="panel-body p-3">
                <form action="{{ route('admin.nasabah.import.preview') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">File CSV</label>
                        <input type="file" name="file" accept=".csv,.txt" class="form-control @error('file') is-invalid @enderror" required>
                        <div class="form-text">Maksimal 2 MB. File diproses setelah pratinjau dan konfirmasi.</div>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-primary"><i class="fa-solid fa-file-circle-check me-1"></i>Pratinjau Data</button>
                        <a href="{{ route('admin.nasabah.import.template') }}" class="btn btn-outline-info"><i class="fa-solid fa-download me-1"></i>Unduh Template</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="data-panel">
            <div class="panel-head"><div class="panel-title"><span class="dot"></span>Panduan Format CSV</div></div>
            <div class="panel-body p-3">
                <p class="small text-muted">Header wajib <code>nis,nama,kelas</code>. Kolom opsional <code>produk,email,saldo_awal</code>; produk default siswa dan saldo awal default 0.</p>
                <pre class="bg-light p-2 small rounded mb-0"><code>nis,nama,kelas,produk,email,saldo_awal
20241001,Budi Santoso,RPL 1,siswa,budi@contoh.test,100000</code></pre>
            </div>
        </div>
    </div>
</div>
