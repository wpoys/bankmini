@extends('layouts.app')

@section('title', 'Ubah Jadwal Petugas')
@section('page-title', 'Ubah Jadwal Petugas')
@section('page-subtitle', 'Perbarui jadwal kerja petugas')

@php
    $valueBagian = $jadwal->user?->role ?? 'teller';
    $valueUserId = $jadwal->user_id;
@endphp

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Ubah Jadwal</h1>
                <div class="page-header-sub">
                    {{ $jadwal->nama_petugas }} · {{ $jadwal->tanggal?->format('d M Y') }} ({{ $jadwal->jam_label }})
                </div>
            </div>
        </div>

        <form action="{{ route($prefix.'.jadwal.update', $jadwal->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('jadwal._form', ['jadwal' => $jadwal])
        </form>
    </div>
@endsection
