@extends('layouts.app')

@section('title', 'Edit User')
@section('page-title', 'Edit User')
@section('page-subtitle', 'Ubah data akun petugas')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Form Edit User</h1>
                <div class="page-header-sub">{{ $user->nama_petugas }}</div>
            </div>
            <div class="header-actions">
                <a href="{{ route('admin.users.index') }}" class="quick-action">
                    <i class="fa-solid fa-arrow-left"></i><span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="data-panel">
                    <div class="panel-body p-3">
                        <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Petugas</label>
                                <input type="text" name="nama_petugas" value="{{ old('nama_petugas', $user->nama_petugas) }}"
                                       class="form-control @error('nama_petugas') is-invalid @enderror">
                                @error('nama_petugas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Username</label>
                                    <input type="text" name="username" value="{{ old('username', $user->username) }}"
                                           class="form-control @error('username') is-invalid @enderror">
                                    @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Password <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Role</label>
                                    <select name="role" class="form-select @error('role') is-invalid @enderror">
                                        @php $rolePetugas = ['admin' => 'Administrator', 'teller' => 'Teller', 'supervisor' => 'Supervisor']; @endphp
                                        @foreach ($rolePetugas as $val => $label)
                                            <option value="{{ $val }}" @selected(old('role', $user->role) === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Status</label>
                                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                                        <option value="active" @selected(old('status', $user->status) === 'active')>Active</option>
                                        <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactive</option>
                                    </select>
                                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Perbarui</button>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
