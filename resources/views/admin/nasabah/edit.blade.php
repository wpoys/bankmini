@extends('layouts.app')

@section('title', 'Edit Nasabah')
@section('page-title', 'Edit Nasabah')
@section('page-subtitle', 'Ubah data rekening siswa')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Form Edit Nasabah</h1>
                <div class="page-header-sub">{{ $nasabah->nama_siswa }}</div>
            </div>
            <div class="header-actions">
                <a href="{{ $returnUrl ?? route('admin.nasabah.index') }}" class="quick-action">
                    <i class="fa-solid fa-arrow-left"></i><span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="data-panel">
                    <div class="panel-body p-3">
                        <form method="POST" action="{{ route('admin.nasabah.update', $nasabah->id) }}">
                            @csrf
                            @method('PUT')
                            @if ($returnUrl)
                                <input type="hidden" name="return_to_pengguna" value="1">
                                @foreach (request()->only(['filter', 'kelas', 'search', 'page', 'per_page']) as $key => $value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endforeach
                            @endif

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Lengkap</label>
                                <input type="text" name="nama_siswa" value="{{ old('nama_siswa', $nasabah->nama_siswa) }}" class="form-control @error('nama_siswa') is-invalid @enderror">
                                @error('nama_siswa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">NIS <small class="text-muted">(username login)</small></label>
                                    <input type="text" name="nis" value="{{ old('nis', $nasabah->nis) }}" class="form-control @error('nis') is-invalid @enderror">
                                    @error('nis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Kelas</label>
                                    <select name="kelas" class="form-select @error('kelas') is-invalid @enderror">
                                        <option value="">Pilih kelas</option>
                                        @foreach (\App\Models\Nasabah::KELAS_GROUPS as $jurusan => $kelasPilihan)
                                            <optgroup label="{{ $jurusan }}">
                                                @foreach ($kelasPilihan as $kelas)
                                                    <option value="{{ $kelas }}" @selected(old('kelas', $nasabah->kelas) === $kelas)>{{ $kelas }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @error('kelas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email Nasabah <small class="text-muted">(opsional)</small></label>
                                <input type="email" name="email" value="{{ old('email', $nasabah->user?->email) }}" class="form-control @error('email') is-invalid @enderror" placeholder="email@contoh.test">
                                <div class="form-text">Perubahan email langsung diperbarui ke akun login.</div>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">PIN Keamanan <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
                                    <input type="password" name="pin_keamanan" maxlength="6" inputmode="numeric" class="form-control @error('pin_keamanan') is-invalid @enderror">
                                    @error('pin_keamanan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Konfirmasi PIN</label>
                                    <input type="password" name="pin_keamanan_confirmation" maxlength="6" inputmode="numeric" class="form-control @error('pin_keamanan_confirmation') is-invalid @enderror">
                                    @error('pin_keamanan_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Saldo Saat Ini</label>
                                <input type="text" value="Rp {{ number_format($nasabah->saldo, 0, ',', '.') }}" class="form-control" readonly>
                                <div class="form-text">Saldo tidak dapat diedit langsung. Perubahan saldo dilakukan melalui transaksi teller (setoran/penarikan).</div>
                            </div>

                            <div class="alert alert-info small mb-3">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                No. rekening: <code>{{ $nasabah->no_rekening }}</code> · Kode jurusan: <code>{{ $nasabah->kode_jurusan }}</code> · QR otomatis mengikuti rekening.
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Perbarui</button>
                                <a href="{{ $returnUrl ?? route('admin.nasabah.index') }}" class="btn btn-outline-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
