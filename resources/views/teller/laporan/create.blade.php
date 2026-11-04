@extends('layouts.app')

@section('title', 'Laporan Kas Harian')
@section('page-title', 'Laporan Kas Harian')
@section('page-subtitle', 'Rekap transaksi dan rekonsiliasi kas: ' . now()->format('d M Y'))

@section('content')
    <div class="page-animate">
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

        <div class="row g-3">
            <div class="col-xl-7">
                <div class="data-panel mb-3">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot"></span>Rekap Transaksi Hari Ini</div>
                        <div class="table-toolbar">
                            <div class="search-box">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" class="form-control form-control-sm" data-table-filter placeholder="Cari struk / nasabah...">
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-nowrap mb-0" data-filterable>
                            <thead>
                                <tr>
                                    <th>No. Struk</th>
                                    <th>Waktu</th>
                                    <th>Nasabah</th>
                                    <th>Jenis</th>
                                    <th class="text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transaksi as $t)
                                    @php $kc = $t->jenis_trans === 'setoran' ? 'success' : 'danger'; @endphp
                                    <tr data-search="{{ $t->no_struk }} {{ $t->nasabah->nama_siswa }}">
                                        <td><code class="small">{{ $t->no_struk }}</code></td>
                                        <td class="small text-muted">{{ $t->created_at->format('H:i') }}</td>
                                        <td class="fw-semibold small">{{ $t->nasabah->nama_siswa }}</td>
                                        <td><span class="badge badge-soft-{{ $kc }}">{{ $t->jenis_label }}</span></td>
                                        <td class="num fw-semibold text-end small">{{ $t->nominal_rupiah }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <div class="empty-icon"><i class="fa-solid fa-receipt"></i></div>
                                                <div class="empty-title">Belum ada transaksi hari ini</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="data-panel">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot" style="background:var(--purple)"></span>Form Rekonsiliasi Kas</div>
                    </div>
                    <div class="panel-body p-3">
                        @if (isset($laporanHariIni) && $laporanHariIni)
                            <div class="alert alert-info small">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                Laporan hari ini sudah dibuat dengan status <strong>{{ $laporanHariIni->status_label }}</strong>.
                                Mengubah form ini akan memperbarui laporan tersebut.
                            </div>
                        @endif

                        <form method="POST" action="{{ route('teller.laporan.store') }}" id="formLaporan">
                            @csrf
                            <div class="alert alert-info small py-2">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                Nilai rekap sistem dihitung otomatis dari database. Isilah total uang fisik yang ada di loket
                                kas, lalu tentukan berapa yang dipindahkan ke brankas.
                            </div>
                            @if ($errors->any())
                                <div class="alert alert-danger small py-2">
                                    <ul class="mb-0 ps-3">
                                        @foreach ($errors->all() as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="saldoAkhirSistem">Saldo Akhir Sistem</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" id="saldoAkhirSistem" class="form-control" value="{{ $saldoAkhirSistem }}" readonly tabindex="-1">
                                    </div>
                                    <div class="form-text">
                                        Saldo awal kas (Rp {{ number_format($saldoAwalKas, 0, ',', '.') }}) + setoran (Rp {{ number_format($totalSetoran, 0, ',', '.') }}) - penarikan (Rp {{ number_format($totalPenarikan, 0, ',', '.') }})
                                        @if (isset($referensiKasAwalHarian) && $referensiKasAwalHarian)
                                            · referensi kas awal piket: Rp {{ number_format($referensiKasAwalHarian, 0, ',', '.') }}
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="saldoFisik">Saldo Akhir Kas Fisik <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="saldo_akhir_fisik" id="saldoFisik" min="0" step="100"
                                            class="form-control js-fisik @error('saldo_akhir_fisik') is-invalid @enderror"
                                            value="{{ old('saldo_akhir_fisik', isset($laporanHariIni) && $laporanHariIni ? $laporanHariIni->saldo_akhir_fisik : '') }}"
                                            placeholder="Total nominal uang fisik di loket kas">
                                    </div>
                                    @error('saldo_akhir_fisik')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="kasDipindahkan">Kas Dipindahkan ke Brankas <small class="text-muted">(opsional)</small></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="kas_dipindahkan" id="kasDipindahkan" min="0" step="100"
                                            class="form-control js-brankas @error('kas_dipindahkan') is-invalid @enderror"
                                            value="{{ old('kas_dipindahkan', isset($laporanHariIni) && $laporanHariIni ? $laporanHariIni->kas_dipindahkan : '') }}"
                                            placeholder="Contoh: 500000">
                                    </div>
                                    @error('kas_dipindahkan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div id="previewSelisih" class="d-none mb-3"></div>

                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-outline-primary flex-fill btn-submit-loading" type="submit">
                                    <i class="fa-solid fa-floppy-disk me-1"></i>Simpan Draft
                                </button>
                                <button class="btn btn-success flex-fill btn-submit-loading" id="btnKirimLaporan" type="submit">
                                    <i class="fa-solid fa-paper-plane me-1"></i>Kirim Laporan
                                </button>
                            </div>
                            <div class="form-text text-center mt-2" id="hintKirim">
                                <i class="fa-solid fa-circle-info me-1"></i>Laporan disimpan sebagai draft lalu dikirim pada langkah berikutnya.
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="data-panel mb-3">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot" style="background:var(--purple)"></span>Ringkasan Rekonsiliasi</div>
                    </div>
                    <div class="panel-body p-3">
                        <div class="balance-summary mb-3">
                            <div class="bs-box"><div class="bs-label">Saldo Awal</div><div class="bs-value" style="font-size:.95rem;">Rp {{ number_format($saldoAwalKas, 0, ',', '.') }}</div></div>
                            <div class="bs-box"><div class="bs-label">Total Setoran</div><div class="bs-value text-success" style="font-size:.95rem;">+Rp {{ number_format($totalSetoran, 0, ',', '.') }}</div></div>
                            <div class="bs-box"><div class="bs-label">Total Penarikan</div><div class="bs-value text-danger" style="font-size:.95rem;">-Rp {{ number_format($totalPenarikan, 0, ',', '.') }}</div></div>
                            <div class="bs-box"><div class="bs-label">Saldo Sistem</div><div class="bs-value" style="font-size:.95rem;">Rp {{ number_format($saldoAkhirSistem, 0, ',', '.') }}</div></div>
                        </div>
                        <div id="ringkasanSelisih" class="mb-0"></div>
                    </div>
                </div>

                <div class="data-panel">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot"></span>Perkiraan Fisik Loket</div>
                    </div>
                    <div class="panel-body p-3">
                        <div class="small text-muted mb-3">
                            <i class="fa-solid fa-circle-info me-1"></i>Gunakan kalkulator pecahan untuk menghitung total uang fisik sebelum diisi ke saldo akhir kas fisik.
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle table-nowrap mb-0">
                                <thead>
                                    <tr>
                                        <th>Pecahan</th>
                                        <th class="text-end">Jumlah Lembar</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyPecahan">
                                    @foreach ([100000, 50000, 20000, 10000, 5000, 2000, 1000, 500, 100] as $pec)
                                        <tr>
                                            <td class="small">Rp {{ number_format($pec, 0, ',', '.') }}</td>
                                            <td class="text-end">
                                                <input type="number" class="form-control form-control-sm text-end pecah-jumlah d-inline-block"
                                                       style="width:110px;" name="pecahan[{{ $pec }}]" data-pecahan="{{ $pec }}" min="0" value="0">
                                            </td>
                                            <td class="num small text-end pecah-subtotal">Rp 0</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold" style="background:#F8FAFD;">
                                        <td class="small text-uppercase text-muted">Total Fisik</td>
                                        <td class="text-end" id="totalJumlahPecah">0</td>
                                        <td class="num text-end" id="totalNilaiPecah">Rp 0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-3" id="btnIsiTotalFisik">
                            <i class="fa-solid fa-calculator me-1"></i>Terapkan ke Saldo Kas Fisik
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const akhirSistem = parseInt((document.getElementById('saldoAkhirSistem') || {}).value) || 0;
            const inputFisik = document.querySelector('.js-fisik');
            const inputBrankas = document.querySelector('.js-brankas');
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
                    if (selisih === 0) {
                        ringkasan.innerHTML = '<div class="status-badge green">Sesuai</div><div class="small text-muted mt-2">Saldo fisik sama dengan saldo sistem.</div>';
                    } else {
                        ringkasan.innerHTML = '<div class="status-badge red">Selisih</div><div class="small text-muted mt-2">Terdapat selisih sebesar <strong>Rp ' + Math.abs(selisih).toLocaleString('id-ID') + '</strong> antara kas fisik dan sistem.</div>';
                    }
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
                if (!inputFisik.value) inputFisik.value = totalNilai;
            }
            rows.forEach((row) => row.addEventListener('input', hitungPecahan));
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

            const formLaporan = document.getElementById('formLaporan');
            if (formLaporan && btnKirim) {
                btnKirim.addEventListener('click', function (e) {
                    if (btnKirim.dataset.prepare === 'selisih') {
                        showToast('Perhatian: terdapat selisih kas. Laporan akan diverifikasi dan dapat ditahan (pending) oleh Supervisor.', 'warning');
                    }
                });
            }
        })();
    </script>
@endpush
