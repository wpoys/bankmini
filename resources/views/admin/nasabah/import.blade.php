@extends('layouts.app')

@section('title', 'Import Nasabah')
@section('page-title', 'Import Data Nasabah')
@section('page-subtitle', 'Pendaftaran massal melalui file CSV')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Import Data Nasabah</h1>
                <div class="page-header-sub">Pendaftaran massal melalui file CSV</div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="data-panel mb-3">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot"></span>Unggah Berkas</div>
                    </div>
                    <div class="panel-body p-3">
                        <form action="{{ route('admin.nasabah.import.preview') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold">File CSV</label>
                                <input type="file" name="file" accept=".csv,.txt" class="form-control @error('file') is-invalid @enderror">
                                <div class="form-text">Maksimal 2 MB. Format: <code>.csv</code>.</div>
                                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-primary"><i class="fa-solid fa-file-circle-check me-1"></i>Pratinjau Data</button>
                                <a href="{{ route('admin.nasabah.import.template') }}" class="btn btn-outline-info"><i class="fa-solid fa-download me-1"></i>Unduh Template</a>
                                <a href="{{ route('admin.nasabah.index') }}" class="btn btn-outline-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="data-panel">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot" style="background:var(--orange)"></span>Panduan Format CSV</div>
                    </div>
                    <div class="panel-body p-3">
                        <p class="small text-muted">Baris pertama harus berupa header dengan kolom berikut:</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered small mb-0">
                                <thead>
                                    <tr>
                                        <th>Kolom</th>
                                        <th>Wajib</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>nis</code></td><td>Ya</td><td>Nomor Induk Siswa, otomatis jadi username login (unik)</td></tr>
                                    <tr><td><code>nama</code></td><td>Ya</td><td>Nama lengkap nasabah</td></tr>
                                    <tr><td><code>kelas</code></td><td>Ya</td><td>Gunakan salah satu value enum, contoh: RPL 2 atau AKL 1</td></tr>
                                    <tr><td><code>produk</code></td><td>Tidak</td><td><code>siswa</code> atau <code>guru</code>, default <code>siswa</code></td></tr>
                                    <tr><td><code>email</code></td><td>Tidak</td><td>Email nasabah (unik). Kontak utama; <code>no_hp</code> tidak didukung</td></tr>
                                    <tr><td><code>pin</code></td><td>Ya</td><td>PIN 6 digit angka (wajib)</td></tr>
                                    <tr><td><code>saldo_awal</code></td><td>Tidak</td><td>Setoran awal (angka, minimal 0), default <code>0</code></td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="small text-muted mt-3 mb-1">Contoh isi file:</p>
                        <pre class="bg-light p-2 small rounded mb-0"><code>nis,nama,kelas,produk,email,pin,saldo_awal
20241001,Budi Santoso,RPL 1,siswa,budi@bmt.test,123456,100000
20241002,Siti Aminah,AKL 2,,siti@bmt.test,654321,50000</code></pre>
                        <div class="alert alert-warning small mt-3 mb-0">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                            No. rekening otomatis mengikuti jurusan kelas (contoh <code>RPL-001</code>, <code>DKV-003</code>). Kolom <code>no_hp</code> / <code>hp</code> / <code>telepon</code> tidak didukung dan akan ditolak. Pastikan NIS, email, dan PIN sudah benar.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
