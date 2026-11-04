@extends('layouts.app')

@section('title', 'Lupa Password - BANK MINI')

@section('auth-body')
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="logo"><img src="{{ asset('images/logo smkn 1 bantul.png') }}" alt="Logo SMK Negeri 1 Bantul"></div>
                <div class="brand-name">Lupa Password</div>
                <div class="brand-sub">Verifikasi identitas Anda, lalu reset password akun</div>
            </div>
            <div class="auth-body">

                <div id="alertBox"></div>

                <form id="resetForm" novalidate>
                    @csrf
                    <div id="identitasFields">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">NIS</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                                <input type="text" class="form-control" name="nis"
                                       placeholder="Masukkan NIS" autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">No Rekening</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-wallet"></i></span>
                                <input type="text" class="form-control" name="no_rekening"
                                       placeholder="Masukkan nomor rekening">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small" for="resetEmail">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" class="form-control" name="email" id="resetEmail"
                                       placeholder="email@contoh.com" autocomplete="email">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">PIN Keamanan</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-shield-halved"></i></span>
                                <input type="password" class="form-control" name="pin_keamanan"
                                       placeholder="Masukkan PIN keamanan">
                            </div>
                        </div>
                    </div>

                    <div id="otpFields" class="d-none">
                        <div class="mb-3" id="infoEmail">
                            <div class="alert alert-info py-2 small mb-0 border-0 shadow-sm" role="alert">
                                <i class="fa-solid fa-envelope me-2"></i>Kode OTP telah dikirim ke email
                                <strong id="infoEmailAddr" class="text-break"></strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                Kode OTP
                                <span id="otpCountdown" class="badge badge-soft-danger ms-1"></span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-shield-halved"></i></span>
                                <input type="text" inputmode="numeric" maxlength="6"
                                       class="form-control text-center fw-bold fs-5 tracking-widest"
                                       name="code" placeholder="000000" disabled>
                            </div>
                        </div>

                        @if (config('mail.otp_dev_display') && app()->environment('local'))
                            <div class="mb-3" id="devOtpBox" class="d-none">
                                <div class="alert alert-warning py-2 small mb-0 border-0 shadow-sm" role="alert">
                                    <i class="fa-solid fa-flask me-2"></i>Mode Pengembangan — OTP Anda:
                                    <strong id="devOtpCode" class="fs-5"></strong>
                                </div>
                            </div>
                        @endif
                    </div>

                    <button type="submit" id="submitBtn" class="btn btn-primary w-100 py-2 fw-semibold">
                        <span id="submitBtnText"><i class="fa-solid fa-paper-plane me-1"></i>Kirim OTP</span>
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

            const state = {
                step: 'identitas',
                nis: '',
                timer: null,
            };

            const form = document.getElementById('resetForm');
            const alertBox = document.getElementById('alertBox');
            const identitasFields = document.getElementById('identitasFields');
            const otpFields = document.getElementById('otpFields');
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnText = document.getElementById('submitBtnText');
            const submitBtnLoading = document.getElementById('submitBtnLoading');
            const codeInput = form.querySelector('input[name="code"]');
            const emailInput = form.querySelector('input[name="email"]');
            const invalidEmailMessage = 'Format salah! Mohon masukkan alamat email yang valid dan ubah teks Anda.';

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

            function clearAlert() {
                alertBox.innerHTML = '';
            }

            function setLoading(loading) {
                submitBtn.disabled = loading;
                submitBtnText.classList.toggle('d-none', loading);
                submitBtnLoading.classList.toggle('d-none', !loading);
            }

            function setStep(step) {
                state.step = step;

                if (step === 'otp') {
                    identitasFields.classList.add('d-none');
                    otpFields.classList.remove('d-none');
                    codeInput.disabled = false;
                    submitBtnText.innerHTML = '<i class="fa-solid fa-check me-1"></i>Verifikasi OTP';
                    codeInput.focus();
                } else {
                    otpFields.classList.add('d-none');
                    identitasFields.classList.remove('d-none');
                    codeInput.disabled = true;
                    submitBtnText.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i>Kirim OTP';
                }
            }

            function startCountdown(seconds) {
                clearInterval(state.timer);
                const el = document.getElementById('otpCountdown');
                el.textContent = formatTime(seconds);

                state.timer = setInterval(function () {
                    seconds--;
                    if (seconds <= 0) {
                        clearInterval(state.timer);
                        el.textContent = 'kedaluwarsa';
                        showAlert('danger', 'Kode OTP telah kedaluwarsa. Silakan minta kode baru.');
                        setStep('identitas');
                        return;
                    }
                    el.textContent = formatTime(seconds);
                }, 1000);
            }

            function formatTime(seconds) {
                const m = String(Math.floor(seconds / 60)).padStart(2, '0');
                const s = String(seconds % 60).padStart(2, '0');
                return m + ':' + s;
            }

            async function postJson(url, body) {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrf(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                });

                let data = {};
                try {
                    data = await res.json();
                } catch (e) {
                    data = { message: 'Terjadi kesalahan pada server.' };
                }

                return { ok: res.ok, status: res.status, data };
            }

            function handleError(data) {
                if (data.errors && Object.keys(data.errors).length) {
                    showAlert('danger', Object.values(data.errors)[0]);
                } else {
                    showAlert('danger', data.message || 'Terjadi kesalahan pada server.');
                }
            }

            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                clearAlert();

                if (state.step === 'identitas') {
                    const nis = form.querySelector('input[name="nis"]').value.trim();
                    const noRekening = form.querySelector('input[name="no_rekening"]').value.trim();
                    const email = emailInput.value.trim();
                    const pin = form.querySelector('input[name="pin_keamanan"]').value;

                    if (!nis) { showAlert('danger', 'NIS wajib diisi.'); return; }
                    if (!noRekening) { showAlert('danger', 'No Rekening wajib diisi.'); return; }
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
                        e.preventDefault();
                        showAlert('danger', invalidEmailMessage);
                        emailInput.focus();
                        return;
                    }
                    if (!pin) { showAlert('danger', 'PIN Keamanan wajib diisi.'); return; }

                    setLoading(true);

                    try {
                        const { ok, data } = await postJson('{{ route('password.kirim-otp') }}', {
                            nis,
                            no_rekening: noRekening,
                            email,
                            pin_keamanan: pin,
                        });

                        if (!ok) {
                            handleError(data);
                            return;
                        }

                        state.nis = nis;
                        showAlert('success', data.message);
                        document.getElementById('infoEmailAddr').textContent = data.email;

                        const devOtpBox = document.getElementById('devOtpBox');
                        if (devOtpBox && data.otp_dev) {
                            devOtpBox.classList.remove('d-none');
                            document.getElementById('devOtpCode').textContent = data.otp_dev;
                        }

                        setStep('otp');
                        startCountdown({{ \App\Http\Controllers\Auth\PasswordResetController::OTP_TTL_MINUTES }} * 60);
                    } finally {
                        setLoading(false);
                    }
                } else {
                    const code = codeInput.value.trim();

                    if (!code) { showAlert('danger', 'Kode OTP wajib diisi.'); return; }
                    if (!/^\d{6}$/.test(code)) { showAlert('danger', 'Kode OTP harus 6 digit.'); return; }

                    setLoading(true);

                    try {
                        const { ok, status, data } = await postJson('{{ route('password.validasi-otp') }}', {
                            nis: state.nis,
                            code,
                        });

                        if (!ok) {
                            if (status === 423) {
                                clearInterval(state.timer);
                            }
                            handleError(data);
                            return;
                        }

                        window.location.href = data.redirect;
                    } finally {
                        setLoading(false);
                    }
                }
            });
        })();
    </script>
@endpush
