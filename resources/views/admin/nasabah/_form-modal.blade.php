<div class="modal fade" id="modalTambahNasabah" tabindex="-1" aria-labelledby="modalTambahNasabahLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header px-4">
                <div>
                    <h5 class="modal-title fw-bold" id="modalTambahNasabahLabel">
                        <i class="fa-solid fa-user-graduate text-primary me-2"></i>Tambah Nasabah Baru
                    </h5>
                    <div class="text-muted small">Buat rekening simpanan siswa baru</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="POST" action="{{ route('admin.nasabah.store') }}">
                @csrf
                <input type="hidden" name="_form" value="nasabah">
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama_siswa" value="{{ old('nama_siswa') }}"
                            class="form-control @error('nama_siswa') is-invalid @enderror" placeholder="Nama lengkap nasabah">
                        @error('nama_siswa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">NIS <small class="text-muted">(username login)</small> <span class="text-danger">*</span></label>
                            <input type="text" name="nis" value="{{ old('nis') }}"
                                class="form-control @error('nis') is-invalid @enderror" placeholder="Contoh: 20241001">
                            @error('nis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Kelas <span class="text-danger">*</span></label>
                            <select name="kelas" class="form-select @error('kelas') is-invalid @enderror">
                                <option value="">Pilih kelas</option>
                                @foreach (\App\Models\Nasabah::KELAS_GROUPS as $jurusan => $kelasPilihan)
                                    <optgroup label="{{ $jurusan }}">
                                        @foreach ($kelasPilihan as $kelas)
                                            <option value="{{ $kelas }}" @selected(old('kelas') === $kelas)>{{ $kelas }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('kelas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Produk Tabungan</label>
                            <select name="produk" class="form-select @error('produk') is-invalid @enderror">
                                <option value="siswa" @selected(old('produk', 'siswa') === 'siswa')>Tabungan Siswa</option>
                                <option value="guru" @selected(old('produk') === 'guru')>Tabungan Guru/Karyawan</option>
                            </select>
                            @error('produk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Email Nasabah</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror" placeholder="email@contoh.test">
                            <div class="form-text">Dipakai untuk mengirim kode aktivasi akun.</div>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Saldo Awal (Rp)</label>
                        <input type="number" name="saldo_awal" value="{{ old('saldo_awal', 0) }}" min="0" step="1"
                            class="form-control @error('saldo_awal') is-invalid @enderror">
                        <div class="form-text">Saldo awal dicatat sebagai setoran awal dan jurnal otomatis.</div>
                        @error('saldo_awal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        <strong>Nomor rekening dibuat otomatis</strong> sesuai jurusan kelas, beserta QR code-nya.
                        Username login menggunakan NIS.
                    </div>
                </div>
                <div class="modal-footer px-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan &amp; Buat Rekening</button>
                </div>
            </form>
        </div>
    </div>
</div>
