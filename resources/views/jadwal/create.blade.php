@extends('layouts.app')

@section('title', 'Tambah Jadwal Petugas')
@section('page-title', 'Tambah Jadwal Petugas')
@section('page-subtitle', 'Catat jadwal kerja baru untuk petugas')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Jadwal Baru</h1>
                <div class="page-header-sub">
                    Tambah jadwal petugas — {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                </div>
            </div>
        </div>

        <form action="{{ route($prefix.'.jadwal.store') }}" method="POST">
            @csrf
            @include('jadwal._form', ['jadwal' => null])
        </form>
    </div>
@endsection
