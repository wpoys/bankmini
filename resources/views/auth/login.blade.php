@extends('layouts.app')

@section('title', 'Login Pengurus — BANK MINI')
@section('body-class', 'auth-page')

@section('auth-body')
    <div class="n-auth n-auth-minimal">
        <div class="n-auth-card">
            <section class="n-auth-form-panel">
                <div class="n-auth-brand">
                    <div class="n-auth-brand-mark"><i class="fa-solid fa-building-columns me-2"></i>BANK MINI SMKN 1 BANTUL</div>
                    <h2>Selamat datang</h2>
                    <p>Masuk ke akun teller, supervisor, atau administrator.</p>
                </div>

                @if (session('success'))
                    <div class="n-alert n-alert--sukses"><i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span></div>
                @endif

                @if ($errors->any())
                    <div class="n-alert" role="alert">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div><strong>Gagal masuk</strong>
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf
                    <div class="n-field">
                        <label for="username">Username / Email Pegawai</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                            <input type="text" id="username" class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username') }}" placeholder="Contoh: teller01 atau nama@bankmini.sch.id" autofocus>
                        </div>
                        @error('username')<div class="n-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="n-field">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="••••••••">
                            <button type="button" class="input-group-text pw-toggle" id="togglePassword" aria-label="Tampilkan password"><i class="fa-solid fa-eye"></i></button>
                        </div>
                        @error('password')<div class="n-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="n-auth-row">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label" for="remember">Ingat saya</label>
                        </div>
                        <a href="{{ route('password.request') }}">Lupa Password? Hubungi Admin</a>
                    </div>

                    <button type="submit" class="n-btn" id="loginButton" disabled>
                        <span class="n-btn-label">Masuk ke Sistem <i class="fa-solid fa-shield-halved"></i></span>
                        <span class="n-btn-loading d-none"><i class="fa-solid fa-spinner fa-spin"></i>MEMPROSES...</span>
                    </button>
                </form>

                <div class="n-auth-divider"></div>

                <p class="n-auth-meta">Setiap aktivitas login tercatat otomatis dalam audit log sistem SMKN 1 Bantul.</p>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const loginButton = document.getElementById('loginButton');
        const loginForm = document.getElementById('loginForm');

        function updateLoginButton() {
            loginButton.disabled = !usernameInput.value.trim() || !passwordInput.value;
        }

        usernameInput.addEventListener('input', updateLoginButton);
        passwordInput.addEventListener('input', updateLoginButton);
        document.getElementById('togglePassword').addEventListener('click', function () {
            const visible = passwordInput.type === 'text';
            passwordInput.type = visible ? 'password' : 'text';
            this.innerHTML = visible ? '<i class="fa-solid fa-eye"></i>' : '<i class="fa-solid fa-eye-slash"></i>';
            this.setAttribute('aria-label', visible ? 'Tampilkan password' : 'Sembunyikan password');
        });
        loginForm.addEventListener('submit', () => {
            loginButton.disabled = true;
            loginButton.querySelector('.n-btn-label').classList.add('d-none');
            loginButton.querySelector('.n-btn-loading').classList.remove('d-none');
        });
        updateLoginButton();
    </script>
@endpush
