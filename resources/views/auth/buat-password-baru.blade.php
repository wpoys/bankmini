@extends('layouts.app')

@section('title', 'Buat Password Baru - BANK MINI')

@section('auth-body')
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="logo"><img src="{{ asset('images/logo smkn 1 bantul.png') }}" alt="Logo SMK Negeri 1 Bantul"></div>
                <div class="brand-name">Buat Password Baru</div>
                <div class="brand-sub">Identitas berhasil diverifikasi. Buat password baru untuk akun Anda.</div>
            </div>
            <div class="auth-body">

                <div id="alertBox"></div>

                <div class="mb-4">
                    <div class="alert alert-success py-3 border-0 shadow-sm" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fa-solid fa-circle-check me-2 mt-1"></i>
                            <div class="small">
                                <div class="fw-bold">OTP Terverifikasi</div>
                                <div class="mb-0">Selamat datang kembali, <strong>{{ $nama }}</strong>. Silakan buat password baru.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="passwordForm" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Password Baru</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" name="password"
                                   placeholder="Minimal 8 karakter" autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Konfirmasi Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" name="password_confirmation"
                                   placeholder="Ulangi password baru">
                        </div>
                    </div>

                    <button type="submit" id="submitBtn" class="btn btn-primary w-100 py-2 fw-semibold">
                        <span id="submitBtnText"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Password</span>
                        <span id="submitBtnLoading" class="d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Memproses...
                        </span>
                    </button>
                </form>

                <hr class="my-4">

                <p class="text-center mb-0">
                    <a href="{{ route('login') }}" class="small text-decoration-none">
                        <i class="fa-solid fa-arrow-left me-1"></i>Kembali ke Login
                    </a>
                </p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            'use strict';

            const form = document.getElementById('passwordForm');
            const alertBox = document.getElementById('alertBox');
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnText = document.getElementById('submitBtnText');
            const submitBtnLoading = document.getElementById('submitBtnLoading');

            function getCsrf() {
                return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            }

            function showAlert(type, message) {
                const icon = type === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-check';
                alertBox.innerHTML =
                    '<div class="alert alert-' + type + ' py-3 mb-3 border-0 shadow-sm" role="alert">' +
                    '<div class="d-flex align-items-start"><i class="fa-solid ' + icon + ' me-2 mt-1"></i>' +
                    '<div class="small mb-0">' + message + '</div></div></div>';
            }

            function setLoading(loading) {
                submitBtn.disabled = loading;
                submitBtnText.classList.toggle('d-none', loading);
                submitBtnLoading.classList.toggle('d-none', !loading);
            }

            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                alertBox.innerHTML = '';

                const password = form.querySelector('input[name="password"]').value;
                const confirmation = form.querySelector('input[name="password_confirmation"]').value;

                if (!password) { showAlert('danger', 'Password baru wajib diisi.'); return; }
                if (password.length < 8) { showAlert('danger', 'Password baru minimal 8 karakter.'); return; }
                if (!confirmation) { showAlert('danger', 'Konfirmasi password wajib diisi.'); return; }
                if (password !== confirmation) { showAlert('danger', 'Konfirmasi password tidak sama.'); return; }

                setLoading(true);

                try {
                    const res = await fetch('{{ route('password.simpan') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrf(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ password, password_confirmation: confirmation }),
                    });

                    let data = {};
                    try {
                        data = await res.json();
                    } catch (err) {
                        data = { message: 'Terjadi kesalahan pada server.' };
                    }

                    if (!res.ok) {
                        if (data.errors && Object.keys(data.errors).length) {
                            showAlert('danger', Object.values(data.errors)[0]);
                        } else {
                            showAlert('danger', data.message || 'Terjadi kesalahan pada server.');
                        }
                        return;
                    }

                    showAlert('success', data.message);
                    setTimeout(function () {
                        window.location.href = data.redirect;
                    }, 800);
                } finally {
                    setLoading(false);
                }
            });
        })();
    </script>
@endpush
