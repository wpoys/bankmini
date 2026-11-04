@extends('layouts.app')

@section('title', 'Pengaturan Profil')
@section('page-title', 'Pengaturan Profil')
@section('page-subtitle', 'Kelola informasi akun pengguna')

@section('content')
    @php
        $user = auth()->user();
    @endphp
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Pengaturan Profil</h1>
                <div class="page-header-sub">Kelola informasi akun pengguna</div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="data-panel text-center">
                    <div class="panel-body py-4">
                        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle text-white fw-bold avatar-88"
                             style="background:var(--blue);font-size:2rem;">
                            {{ strtoupper(substr($user->nama_petugas, 0, 1)) }}
                        </div>
                        <h5 class="fw-bold mb-1">{{ $user->nama_petugas }}</h5>
                        <p class="text-muted small mb-0">@ {{ $user->username }}</p>
                        <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
                            <span class="badge badge-soft-primary">{{ $user->role_label }}</span>
                            <span class="badge badge-soft-success">{{ ucfirst($user->status) }}</span>
                        </div>
                        <div class="small text-muted mt-3 mb-0">
                            Terdaftar {{ $user->created_at->format('d M Y') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="data-panel">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot"></span>Data Akun</div>
                    </div>
                    <div class="panel-body p-3">
                        <form method="POST" action="{{ route('profil.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nama Lengkap</label>
                                    <input type="text" name="nama_petugas"
                                           class="form-control @error('nama_petugas') is-invalid @enderror"
                                           value="{{ old('nama_petugas', $user->nama_petugas) }}">
                                    @error('nama_petugas')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email</label>
                                    <input type="email" name="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email', $user->email) }}" placeholder="nama@contoh.sch.id">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Username</label>
                                    <input type="text" class="form-control" value="{{ $user->username }}" disabled>
                                    <div class="form-text">Username tidak dapat diubah.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Role</label>
                                    <input type="text" class="form-control" value="{{ $user->role_label }}" disabled>
                                </div>
                                <div class="col-12">
                                    <hr>
                                    <div class="small text-muted">
                                        <i class="fa-solid fa-circle-info me-1"></i>
                                        Jika ingin mengganti password, gunakan menu <strong>Lupa Password</strong> pada halaman login.
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary px-4">
                                        <i class="fa-solid fa-floppy-disk me-1"></i>Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
