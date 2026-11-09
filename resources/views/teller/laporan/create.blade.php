@extends('layouts.app')

@section('title', 'Laporan Kas Harian')
@section('page-title', 'Laporan Kas Harian')
@section('page-subtitle', 'Rekap transaksi dan rekonsiliasi kas: ' . now()->format('d M Y'))

@section('content')
    <div class="page-animate laporan-kas-page">
        <div class="page-header">
            <div>
                <h1>Rekonsiliasi Kas</h1>
                <div class="page-header-sub">
                    {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                </div>
            </div>
            <div class="header-actions">
                @php
                    $statusLaporan = isset($laporanHariIni) && $laporanHariIni ? $laporanHariIni->status : 'belum';
                    $statusCls = match ($statusLaporan) {
                        'disetujui' => 'green',
                        'ditolak' => 'red',
                        'menunggu' => 'orange',
                        'pending' => 'blue',
                        default => 'gray',
                    };
                    $statusTxt = match ($statusLaporan) {
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'menunggu' => 'Menunggu',
                        'pending' => 'Ditahan (Pending)',
                        default => 'Belum Dibuat',
                    };
                @endphp
                <span class="status-badge {{ $statusCls }}">{{ $statusTxt }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('teller.laporan.store') }}" id="formLaporan">
            @csrf
            <div class="laporan-kas-grid">
                <div class="laporan-kas-left">
                    <div class="data-panel mb-3 laporan-panel laporan-pecahan">
                        <div class="panel-head">
                            <div class="panel-title"><span class="dot"></span>Bagian A: Perhitungan Uang Fisik</div>
                        </div>
                    <div class="panel-body p-2">
                        <div class="table-responsive">
                            <table class="table align-middle table-nowrap mb-0 laporan-denominasi">
                                <thead>
                                    <tr>
                                        <th>Pecahan (Nominal)</th>
                                        <th class="text-center">Jumlah Lembar</th>
                                        <th class="text-end">Subtotal (Rupiah)</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyPecahan">
                                    @foreach ([100000, 50000, 20000, 10000, 5000, 2000, 1000, 500, 100] as $pec)
                                        <tr>
                                            <td class="small">Rp {{ number_format($pec, 0, ',', '.') }}</td>
                                            <td class="text-center">
                                                <div class="input-group input-group-sm laporan-pecahan-input">
                                                    <button type="button" class="btn btn-light" data-pecahan-action="minus" aria-label="Kurangi lembar"><i class="fa-solid fa-minus"></i></button>
                                                    <input type="number" class="form-control text-center pecah-jumlah" name="pecahan[{{ $pec }}]" data-pecahan="{{ $pec }}" min="0" value="0" aria-label="Jumlah lembar Rp {{ number_format($pec, 0, ',', '.') }}">
                                                    <button type="button" class="btn btn-light" data-pecahan-action="plus" aria-label="Tambah lembar"><i class="fa-solid fa-plus"></i></button>
                                                </div>
                                            </td>
                                            <td class="num small text-end pecah-subtotal">Rp 0</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td colspan="2" class="small text-uppercase">Total Kas Fisik di Loket</td>
                                        <td class="num text-end" id="totalNilaiPecah">Rp 0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="d-flex align-items-center justify-content-between gap-2 px-2 py-2 laporan-total-fisik">
                            <span class="small fw-bold text-uppercase">Total Kas Fisik di Loket</span>
                            <span id="totalJumlahPecah" class="d-none">0</span>
                            <strong id="nilaiTotalFisik">Rp 0</strong>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnIsiTotalFisik">Terapkan</button>
                        </div>
                    </div>
                    </div>

                    <div class="data-panel laporan-panel laporan-transfer">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot" style="background:var(--purple)"></span>Bagian C: Pemindahan Kas</div>
                    </div>
                    <div class="panel-body p-3">
                        <label class="form-label" for="kasDipindahkan">Dipindahkan ke brankas</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="kas_dipindahkan" id="kasDipindahkan" min="0" step="100" class="form-control js-brankas @error('kas_dipindahkan') is-invalid @enderror" value="{{ old('kas_dipindahkan', isset($laporanHariIni) && $laporanHariIni ? $laporanHariIni->kas_dipindahkan : '') }}" placeholder="0">
                        </div>
                        @error('kas_dipindahkan')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @if ($errors->any() && !$errors->has('kas_dipindahkan') && !$errors->has('saldo_akhir_fisik'))
                            <div class="small text-danger mt-2">{{ $errors->first() }}</div>
                        @endif
                        <div class="form-text mt-2">Saldo sistem: Rp {{ number_format($saldoAkhirSistem, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

                <div class="laporan-kas-right">
                    <div class="data-panel mb-3 laporan-panel laporan-ringkasan">
                        <div class="panel-head">
                            <div class="panel-title"><span class="dot" style="background:var(--purple)"></span>Bagian B: Ringkasan Transaksi (Sistem)</div>
                        </div>
                        <div class="panel-body p-2">
                            <div class="laporan-summary-grid">
                                <div class="laporan-summary-card system"><span>Saldo Awal Kas</span><strong>Rp {{ number_format($saldoAwalKas, 0, ',', '.') }}</strong><small>Total kas awal diambil dari brankas hari ini</small></div>
                                <div class="laporan-summary-card deposit"><span>Total Setoran</span><strong>+Rp {{ number_format($totalSetoran, 0, ',', '.') }}</strong><small>Setoran hari ini</small></div>
                                <div class="laporan-summary-card withdrawal"><span>Total Penarikan</span><strong>-Rp {{ number_format($totalPenarikan, 0, ',', '.') }}</strong><small>Penarikan hari ini</small></div>
                                <div class="laporan-summary-card ending"><span>Saldo Akhir Sistem</span><strong>Rp {{ number_format($saldoAkhirSistem, 0, ',', '.') }}</strong><small>Saldo akhir menurut sistem</small></div>
                            </div>
                        </div>
                    </div>

                <div class="data-panel mb-3 laporan-panel laporan-rekonsiliasi">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot" style="background:var(--green)"></span>Bagian D: Hasil Rekonsiliasi</div>
                    </div>
                    <div class="panel-body p-3">
                        <div class="laporan-rekonsiliasi-saldo">
                            <div class="laporan-rekonsiliasi-box">
                                <span>Saldo Akhir Sistem</span>
                                <strong>Rp {{ number_format($saldoAkhirSistem, 0, ',', '.') }}</strong>
                            </div>
                            <div class="laporan-rekonsiliasi-box">
                                <label for="saldoFisik">Saldo Akhir Fisik <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="saldo_akhir_fisik" id="saldoFisik" min="0" step="100" class="form-control js-fisik @error('saldo_akhir_fisik') is-invalid @enderror" value="{{ old('saldo_akhir_fisik', isset($laporanHariIni) && $laporanHariIni ? $laporanHariIni->saldo_akhir_fisik : '') }}" placeholder="Isi saldo fisik">
                                </div>
                                @error('saldo_akhir_fisik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div id="ringkasanSelisih" class="laporan-selisih mt-2"></div>
                    </div>
                </div>

                    <div class="data-panel laporan-panel laporan-validasi">
                        <div class="panel-head">
                            <div class="panel-title"><span class="dot" style="background:var(--orange)"></span>Bagian E: Keterangan Teller &amp; Validasi</div>
                        </div>
                        <div class="panel-body p-3">
                            <label for="catatanTeller" class="form-label">Catatan Penutupan Loket</label>
                            <textarea id="catatanTeller" class="form-control mb-3" rows="2" placeholder="Tidak terdapat selisih antara saldo fisik dan uang fisik."></textarea>
                            @if ($errors->any())
                                <div class="alert alert-danger small py-2">{{ $errors->first() }}</div>
                            @endif
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-outline-primary flex-fill btn-submit-loading" type="submit" name="aksi" value="draft"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Draft</button>
                                <button class="btn btn-success flex-fill btn-submit-loading" id="btnKirimLaporan" type="submit" name="aksi" value="kirim"><i class="fa-solid fa-paper-plane me-1"></i>Kirim Laporan</button>
                            </div>
                            <div class="form-text text-center mt-2" id="hintKirim">Pastikan jumlah kas fisik sudah sesuai sebelum mengirim.</div>
                            <div id="previewSelisih" class="d-none mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('styles')
    <style>
        .laporan-kas-page {
            --laporan-navy: var(--navy);
            --laporan-blue: var(--blue);
            --laporan-border: var(--border);
        }

        .laporan-kas-page .page-header {
            margin-bottom: 14px;
        }

        .laporan-kas-page .laporan-kas-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr);
            align-items: start;
            gap: 12px;
        }

        .laporan-kas-page .laporan-kas-left,
        .laporan-kas-page .laporan-kas-right {
            display: grid;
            align-content: start;
            gap: 12px;
            min-width: 0;
        }

        .laporan-kas-page .laporan-kas-left > .data-panel,
        .laporan-kas-page .laporan-kas-right > .data-panel {
            margin: 0 !important;
        }

        .laporan-kas-page .laporan-panel {
            border-color: var(--laporan-border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-xs);
        }

        .laporan-kas-page .panel-head {
            min-height: 38px;
            padding: 8px 12px;
            background: #fff;
        }

        .laporan-kas-page .panel-title {
            font-size: 13px;
            font-weight: 800;
            color: var(--laporan-navy);
        }

        .laporan-kas-page .dot {
            width: 7px;
            height: 7px;
            flex-basis: 7px;
            margin-right: 8px;
        }

        .laporan-kas-page .panel-body {
            padding: 10px !important;
        }

        .laporan-kas-page .laporan-denominasi > :not(caption) > * > * {
            padding: 4px 6px;
            font-size: 10px;
        }

        .laporan-kas-page .form-control,
        .laporan-kas-page .form-select,
        .laporan-kas-page .input-group-text {
            min-height: 36px;
            font-size: 13px;
        }

        .laporan-kas-page .btn {
            min-height: 36px;
            font-size: 12px;
        }

        .laporan-kas-page .form-label {
            margin-bottom: 5px;
            font-size: 12px;
            font-weight: 700;
        }

        .laporan-kas-page .form-text {
            font-size: 11px;
        }

        .laporan-kas-page .laporan-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
        }

        .laporan-kas-page .laporan-summary-card {
            display: grid;
            gap: 3px;
            min-width: 0;
            min-height: 63px;
            padding: 8px;
            border: 1px solid var(--laporan-border);
            border-radius: 5px;
            background: #f3f7ff;
        }

        .laporan-kas-page .laporan-summary-card span,
        .laporan-kas-page .laporan-rekonsiliasi-box > span,
        .laporan-kas-page .laporan-rekonsiliasi-box label {
            color: #697d9a;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .laporan-kas-page .laporan-summary-card strong,
        .laporan-kas-page .laporan-rekonsiliasi-box > strong {
            color: var(--laporan-navy);
            font-size: 13px;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .laporan-kas-page .laporan-summary-card small {
            color: #7386a1;
            font-size: 8px;
        }

        .laporan-kas-page .laporan-summary-card.deposit strong { color: #07834d; }
        .laporan-kas-page .laporan-summary-card.withdrawal strong { color: #d43c4a; }
        .laporan-kas-page .laporan-summary-card.ending {
            border-color: #18314f;
            background: #102746;
        }
        .laporan-kas-page .laporan-summary-card.ending span,
        .laporan-kas-page .laporan-summary-card.ending strong,
        .laporan-kas-page .laporan-summary-card.ending small { color: #fff; }

        .laporan-kas-page .laporan-denominasi thead th {
            background: #edf3ff;
            color: #18314f;
            font-size: 9px;
            text-transform: uppercase;
        }

        .laporan-kas-page .laporan-denominasi > :not(caption) > * > * {
            padding: 4px 6px;
            font-size: 10px;
        }

        .laporan-kas-page .laporan-denominasi thead th { font-size: 9px; }
        .laporan-kas-page .laporan-denominasi thead th:nth-child(1) { width: 34%; }
        .laporan-kas-page .laporan-denominasi thead th:nth-child(2) { width: 38%; }
        .laporan-kas-page .laporan-denominasi thead th:nth-child(3) { width: 28%; }

        .laporan-kas-page .laporan-pecahan-input {
            width: 108px;
            margin: 0 auto;
        }

        .laporan-kas-page .laporan-pecahan-input [data-pecahan-action] {
            width: 25px;
            min-height: 25px;
            padding: 1px;
            font-size: 9px;
        }

        .laporan-kas-page .laporan-pecahan-input .btn {
            min-height: 24px;
            padding: 1px 6px;
            border-color: #d6e1f1;
            color: var(--laporan-blue);
        }

        .laporan-kas-page .laporan-pecahan-input .form-control {
            min-height: 24px;
            padding: 1px 3px;
            font-size: 10px;
        }

        .laporan-kas-page .laporan-total-fisik {
            border: 1px solid #c9d9f7;
            border-radius: 5px;
            background: #eaf1ff;
            color: #18314f;
        }

        .laporan-kas-page .laporan-total-fisik strong { font-size: 13px; }

        .laporan-kas-page .laporan-rekonsiliasi-saldo {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .laporan-kas-page .laporan-rekonsiliasi-box {
            display: grid;
            align-content: center;
            gap: 6px;
            min-height: 55px;
            padding: 8px;
            border: 1px solid var(--laporan-border);
            border-radius: 5px;
            background: #f4f7fc;
        }

        .laporan-kas-page .laporan-selisih {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 7px 9px;
            border-radius: 5px;
            background: #edf3ff;
            color: #18314f;
        }

        .laporan-kas-page .laporan-selisih-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 10px;
            font-weight: 700;
        }

        .laporan-kas-page .laporan-selisih-label strong { font-size: 13px; }

        .laporan-kas-page .laporan-transfer .panel-body { padding: 10px !important; }
        .laporan-kas-page .laporan-transfer .form-text { margin: 4px 0 0; }

        @media (max-width: 991.98px) {
            .laporan-kas-page .laporan-kas-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 575.98px) {
            .laporan-kas-page .laporan-summary-card { padding: 7px; }
            .laporan-kas-page .laporan-rekonsiliasi-saldo { grid-template-columns: minmax(0, 1fr); }
            .laporan-kas-page .laporan-total-fisik { flex-wrap: wrap; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const akhirSistem = Number(@json((int) $saldoAkhirSistem));
            const inputFisik = document.querySelector('.js-fisik');
            const inputBrankas = document.querySelector('.js-brankas');
            const formLaporan = document.getElementById('formLaporan');
            const preview = document.getElementById('previewSelisih');
            if (!inputFisik) return;

            const btnKirim = document.getElementById('btnKirimLaporan');
            const hintKirim = document.getElementById('hintKirim');

            const hitung = () => {
                const fisik = parseInt(inputFisik.value) || 0;
                const brankas = parseInt(inputBrankas.value) || 0;
                const selisih = fisik - akhirSistem;

                if (preview) {
                    preview.classList.remove('d-none');
                    preview.innerHTML = '';
                    if (selisih === 0) {
                        preview.innerHTML = '<div class="alert alert-success small py-2 mb-0"><i class="fa-solid fa-circle-check me-1"></i>Kas fisik selaras dengan sistem (selisih 0). Laporan siap dikirim.</div>';
                    } else {
                        preview.innerHTML = '<div class="alert alert-danger small py-2 mb-0"><i class="fa-solid fa-circle-exclamation me-1"></i>Selisih: <strong>' + (selisih > 0 ? '+' : '') + selisih.toLocaleString('id-ID') + '</strong>. Laporan tetap dapat dikirim, namun Supervisor akan menahan (pending) hingga selisih diselesaikan.</div>';
                    }
                    if (brankas > 0) {
                        const info = document.createElement('div');
                        info.className = 'alert alert-primary small py-2 mb-2';
                        info.innerHTML = 'Kas dipindahkan ke brankas: <strong>Rp ' + brankas.toLocaleString('id-ID') + '</strong>. Sisa di loket: <strong>Rp ' + Math.max(fisik - brankas, 0).toLocaleString('id-ID') + '</strong>.';
                        preview.prepend(info);
                    }
                    if (brankas > akhirSistem) {
                        const warn = document.createElement('div');
                        warn.className = 'alert alert-warning small py-2 mb-2';
                        warn.textContent = 'Jumlah dipindahkan melebihi saldo akhir sistem (Rp ' + akhirSistem.toLocaleString('id-ID') + ').';
                        preview.prepend(warn);
                    }
                }

                const ringkasan = document.getElementById('ringkasanSelisih');
                if (ringkasan) {
                    ringkasan.innerHTML = '<div class="laporan-selisih-label">Selisih Kas <strong>Rp ' + Math.abs(selisih).toLocaleString('id-ID') + '</strong></div><span class="small text-muted">' + (selisih === 0 ? 'Saldo sesuai' : 'Perlu dicocokkan') + '</span>';
                }

                if (btnKirim) {
                    const selisihNol = selisih === 0;
                    btnKirim.dataset.prepare = selisihNol ? 'siap' : 'selisih';
                    if (hintKirim) {
                        hintKirim.innerHTML = selisihNol
                            ? '<i class="fa-solid fa-circle-check me-1 text-success"></i>Kas selaras dengan sistem. Laporan siap dikirim.'
                            : '<i class="fa-solid fa-triangle-exclamation me-1 text-danger"></i>Selisih kas belum nol — laporan tetap terkirim, tapi akan ditahan (pending) oleh Supervisor untuk ditindaklanjuti.';
                    }
                }
            };

            inputFisik.addEventListener('input', hitung);
            if (inputBrankas) inputBrankas.addEventListener('input', hitung);

            const rows = document.querySelectorAll('.pecah-jumlah');
            function hitungPecahan() {
                let totalJumlah = 0;
                let totalNilai = 0;
                rows.forEach((row) => {
                    const pec = parseInt(row.dataset.pecahan) || 0;
                    const jml = parseInt(row.value) || 0;
                    const sub = pec * jml;
                    totalJumlah += jml;
                    totalNilai += sub;
                    const subEl = row.closest('tr').querySelector('.pecah-subtotal');
                    if (subEl) subEl.textContent = 'Rp ' + sub.toLocaleString('id-ID');
                });
                const tj = document.getElementById('totalJumlahPecah');
                const tn = document.getElementById('totalNilaiPecah');
                if (tj) tj.textContent = totalJumlah.toLocaleString('id-ID');
                if (tn) tn.textContent = 'Rp ' + totalNilai.toLocaleString('id-ID');
                const nilaiTotal = document.getElementById('nilaiTotalFisik');
                if (nilaiTotal) nilaiTotal.textContent = 'Rp ' + totalNilai.toLocaleString('id-ID');
                if (!inputFisik.value || parseInt(inputFisik.value, 10) === 0) {
                    inputFisik.value = totalNilai;
                    inputFisik.dispatchEvent(new Event('input'));
                }
            }
            rows.forEach((row) => row.addEventListener('input', hitungPecahan));
            document.querySelectorAll('[data-pecahan-action]').forEach((button) => {
                button.addEventListener('click', function () {
                    const input = button.parentElement.querySelector('.pecah-jumlah');
                    const arah = button.dataset.pecahanAction === 'plus' ? 1 : -1;
                    input.value = Math.max(0, (parseInt(input.value) || 0) + arah);
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                });
            });
            hitungPecahan();
            hitung();

            const btnIsiTotal = document.getElementById('btnIsiTotalFisik');
            if (btnIsiTotal) {
                btnIsiTotal.addEventListener('click', function () {
                    const totalEl = document.getElementById('totalNilaiPecah');
                    const total = (totalEl.textContent || '0').replace(/[^0-9]/g, '');
                    inputFisik.value = parseInt(total || '0', 10) || 0;
                    inputFisik.dispatchEvent(new Event('input'));
                    showToast('Total fisik diterapkan ke saldo akhir kas fisik.', 'success');
                });
            }

            if (formLaporan && btnKirim) {
                formLaporan.addEventListener('submit', function (event) {
                    if (event.submitter && event.submitter.value === 'kirim') {
                        const hiddenAction = document.createElement('input');
                        hiddenAction.type = 'hidden';
                        hiddenAction.name = 'submit_after_save';
                        hiddenAction.value = '1';
                        formLaporan.appendChild(hiddenAction);
                        if (btnKirim.dataset.prepare === 'selisih' && typeof showToast === 'function') {
                            showToast('Perhatian: terdapat selisih kas. Laporan akan dikirim untuk verifikasi.', 'warning');
                        }
                    }
                });
            }
        })();
    </script>
@endpush
