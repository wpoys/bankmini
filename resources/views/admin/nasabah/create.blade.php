@extends('layouts.app')

@section('title', 'Tambah Nasabah')
@section('page-title', 'Tambah Nasabah')
@section('page-subtitle', 'Buat rekening siswa baru')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Form Tambah Nasabah</h1>
                <div class="page-header-sub">Data rekening simpanan baru</div>
            </div>
            <div class="header-actions">
                <a href="{{ route('admin.nasabah.index') }}" class="quick-action">
                    <i class="fa-solid fa-arrow-left"></i><span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="data-panel">
                    <div class="panel-body p-3">
                        <form method="POST" action="{{ route('admin.nasabah.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Lengkap</label>
                                <input type="text" name="nama_siswa" value="{{ old('nama_siswa') }}" class="form-control @error('nama_siswa') is-invalid @enderror" placeholder="Nama lengkap nasabah">
                                @error('nama_siswa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">NIS <small class="text-muted">(username login)</small></label>
                                    <input type="text" name="nis" value="{{ old('nis') }}" class="form-control @error('nis') is-invalid @enderror">
                                    @error('nis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Kelas</label>
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

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email Nasabah</label>
                                <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="email@contoh.test">
                                <div class="form-text">Dipakai untuk mengirim kode aktivasi akun.</div>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Saldo Awal (Rp)</label>
                                <input type="number" name="saldo_awal" value="{{ old('saldo_awal', 0) }}" min="0" step="1" class="form-control @error('saldo_awal') is-invalid @enderror">
                                <div class="form-text">Saldo awal dicatat sebagai setoran awal (transaksi + jurnal otomatis).</div>
                                @error('saldo_awal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="alert alert-info small">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                <strong>Nomor rekening dibuat otomatis</strong> sesuai jurusan kelas, contoh: <code>RPL-001</code>, <code>DKV-002</code> — beserta QR code-nya.
                            </div>
                            <div class="alert alert-info small mb-3">
                                <i class="fa-solid fa-circle-user me-1"></i>
                                <strong>Username login menggunakan NIS</strong>. Akun dibuat belum aktif; nasabah akan membuat sandi dan PIN sendiri melalui alur aktivasi.
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan</button>
                                <a href="{{ route('admin.nasabah.index') }}" class="btn btn-outline-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
