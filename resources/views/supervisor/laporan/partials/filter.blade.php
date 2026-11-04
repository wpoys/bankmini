<div class="data-panel mb-3">
    <div class="panel-body py-3 px-3">
        <form class="row g-2 align-items-end" method="GET">
            <div class="col-auto">
                <label class="form-label small mb-1 fw-semibold">Dari Tanggal</label>
                <input type="date" name="mulai" value="{{ $mulai->format('Y-m-d') }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1 fw-semibold">Sampai Tanggal</label>
                <input type="date" name="sampai" value="{{ $sampai->format('Y-m-d') }}" class="form-control form-control-sm">
            </div>
            @isset($tellerList)
                <div class="col-auto">
                    <label class="form-label small mb-1 fw-semibold">Teller</label>
                    <select name="teller" class="form-select form-select-sm">
                        <option value="">Semua teller</option>
                        @foreach ($tellerList as $t)
                            <option value="{{ $t->id }}" @selected(($tellerTerpilih ?? null)?->id === $t->id)>{{ $t->nama_petugas }}</option>
                        @endforeach
                    </select>
                </div>
            @endisset
            @isset($nasabahList)
                <div class="col-lg-4">
                    <label class="form-label small mb-1 fw-semibold">Pilih Nasabah</label>
                    <select name="nasabah_id" class="form-select form-select-sm">
                        <option value="">Semua nasabah</option>
                        @foreach ($nasabahList as $n)
                            <option value="{{ $n->id }}" @selected($nasabah?->id === $n->id)>{{ $n->nama_siswa }} ({{ $n->no_rekening }})</option>
                        @endforeach
                    </select>
                </div>
            @endisset
            <div class="col-auto">
                <button class="btn btn-sm btn-primary"><i class="fa-solid fa-filter me-1"></i>Tampilkan</button>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route($rute, array_merge(request()->query(), ['format' => 'excel'])) }}" class="btn btn-sm btn-success">
                    <i class="fa-solid fa-file-excel me-1"></i>Excel
                </a>
                <a href="{{ route($rute, array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-sm btn-danger">
                    <i class="fa-solid fa-file-pdf me-1"></i>PDF
                </a>
            </div>
        </form>
    </div>
</div>
