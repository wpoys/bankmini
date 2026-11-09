<div class="modal fade" id="modalTambahPetugas" tabindex="-1" aria-labelledby="modalTambahPetugasLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header px-4">
                <div>
                    <h5 class="modal-title fw-bold" id="modalTambahPetugasLabel">
                        <i class="fa-solid fa-user-plus text-success me-2"></i>Tambah Petugas
                    </h5>
                    <div class="text-muted small">Buat akun petugas baru</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <input type="hidden" name="_form" value="petugas">
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Petugas <span class="text-danger">*</span></label>
                        <input type="text" name="nama_petugas" value="{{ old('nama_petugas') }}"
                            class="form-control @error('nama_petugas') is-invalid @enderror">
                        @error('nama_petugas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror" placeholder="email@contoh.test" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" value="{{ old('username') }}"
                                class="form-control @error('username') is-invalid @enderror">
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select @error('role') is-invalid @enderror">
                                <option value="">Pilih role</option>
                                @php $rolePetugas = ['admin' => 'Administrator', 'teller' => 'Teller', 'supervisor' => 'Supervisor']; @endphp
                                @foreach ($rolePetugas as $val => $label)
                                    <option value="{{ $val }}" @selected(old('role') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" @selected(old('status') !== 'inactive')>Aktif</option>
                                <option value="inactive" @selected(old('status') === 'inactive')>Nonaktif</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-success"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Petugas</button>
                </div>
            </form>
        </div>
    </div>
</div>
