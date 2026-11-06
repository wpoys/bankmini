@extends('layouts.app')

@section('title', 'Dashboard Teller')
@section('page-title', 'Dashboard Teller')
@section('page-subtitle', 'Lakukan transaksi setoran & penarikan dengan cepat')

@php
    $selisihKas = 0;
@endphp

@section('content')
    <div class="page-animate teller-dashboard-page">
        <div class="row g-2 teller-dashboard-stats">
            <div class="col-6 col-xl-2"><div class="stat-card teller-stat-card"><div class="stat-top"><span class="stat-label">Setoran</span><span class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-arrow-down"></i></span></div><div class="stat-value">Rp {{ number_format($totalSetoranHariIni, 0, ',', '.') }}</div><div class="stat-hint">Hari ini</div></div></div>
            <div class="col-6 col-xl-2"><div class="stat-card teller-stat-card"><div class="stat-top"><span class="stat-label">Penarikan</span><span class="stat-icon bg-danger-subtle text-danger"><i class="fa-solid fa-arrow-up"></i></span></div><div class="stat-value">Rp {{ number_format($totalPenarikanHariIni, 0, ',', '.') }}</div><div class="stat-hint">Hari ini</div></div></div>
            <div class="col-6 col-xl-2"><div class="stat-card teller-stat-card"><div class="stat-top"><span class="stat-label">Total Transaksi</span><span class="stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-receipt"></i></span></div><div class="stat-value">{{ number_format($jumlahTransaksi, 0, ',', '.') }}</div><div class="stat-hint">Sepanjang hari</div></div></div>
            <div class="col-6 col-xl-2"><div class="stat-card teller-stat-card"><div class="stat-top"><span class="stat-label">Saldo Fisik Kas</span><span class="stat-icon bg-warning-subtle text-warning"><i class="fa-solid fa-vault"></i></span></div><div class="stat-value">Rp {{ number_format($saldoKas, 0, ',', '.') }}</div><div class="stat-hint">Hari ini</div></div></div>
            <div class="col-6 col-xl-2"><div class="stat-card teller-stat-card"><div class="stat-top"><span class="stat-label">Pending</span><span class="stat-icon bg-warning-subtle text-warning"><i class="fa-solid fa-hourglass-half"></i></span></div><div class="stat-value">{{ number_format($transaksiPending, 0, ',', '.') }}</div><div class="stat-hint">Menunggu approval</div></div></div>
            <div class="col-6 col-xl-2"><div class="stat-card teller-stat-card"><div class="stat-top"><span class="stat-label">Selisih Kas</span><span class="stat-icon bg-info-subtle text-info"><i class="fa-solid fa-scale-balanced"></i></span></div><div class="stat-value {{ $selisihKas === 0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format($selisihKas, 0, ',', '.') }}</div><div class="stat-hint">Sistem vs fisik</div></div></div>
        </div>

        <div class="row g-2 teller-dashboard-main-row">
            <div class="col-lg-5">
                <section class="data-panel teller-lookup-panel h-100">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot"></span>Cari Nasabah &amp; Scan QR</div>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalScanNasabah">
                            <i class="fa-solid fa-qrcode me-1"></i>Scan QR
                        </button>
                    </div>
                    <div class="panel-body">
                        <form id="formCariNasabahDashboard" action="{{ route('teller.dashboard.cari-nasabah') }}" method="POST">
                            @csrf
                            <label class="form-label" for="inputCariNasabahDashboard">Scan barcode atau masukkan NIS / No. Rekening</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                                <input type="text" class="form-control" id="inputCariNasabahDashboard" name="cari" autocomplete="off" placeholder="NIS, nomor rekening, atau QR" required>
                                <button class="btn btn-primary" type="submit" id="btnCariNasabahDashboard"><i class="fa-solid fa-magnifying-glass me-1"></i>Cari</button>
                            </div>
                        </form>
                        <div class="teller-lookup-help"><i class="fa-solid fa-circle-info"></i><span>Nasabah aktif yang ditemukan akan ditampilkan untuk dikonfirmasi.</span></div>
                        <div id="hasilCariNasabahDashboard" class="teller-lookup-feedback" aria-live="polite"></div>
                        <a href="{{ route('teller.transaksi.index') }}" class="teller-lookup-secondary"><i class="fa-solid fa-arrow-up-right-from-square"></i> Buka halaman transaksi lengkap</a>
                    </div>
                </section>
            </div>
            <div class="col-lg-7">
                <section class="data-panel teller-chart-panel h-100">
                    <div class="panel-head">
                        <div>
                            <div class="panel-title"><span class="dot"></span>Grafik Tren Transaksi Hari Ini</div>
                            <div class="teller-chart-legend"><span class="legend-setoran">Setoran</span><span class="legend-penarikan">Penarikan</span></div>
                        </div>
                        <span class="status-badge blue">{{ now()->format('d/m/Y') }}</span>
                    </div>
                    <div class="panel-body teller-hourly-chart"><canvas id="chartTransaksiHarian" aria-label="Grafik setoran dan penarikan per jam hari ini"></canvas></div>
                </section>
            </div>
        </div>

        <section class="data-panel teller-recent-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Transaksi Terbaru</div>
                <div class="table-toolbar">
                    <div class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="form-control form-control-sm" data-table-filter placeholder="Cari struk / nasabah..."></div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0" data-filterable>
                    <thead><tr><th>No. Struk</th><th>Waktu</th><th>Nasabah</th><th>Jenis</th><th class="text-end">Nominal</th><th>Petugas</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($transaksiTerbaru as $t)
                            <tr data-search="{{ $t->no_struk }} {{ $t->nasabah->nama_siswa ?? '' }}" data-status="{{ $t->status }}">
                                <td><code class="small">{{ $t->no_struk }}</code></td>
                                <td class="text-muted small">{{ $t->created_at->format('H:i') }}</td>
                                <td class="fw-semibold small">{{ $t->nasabah->nama_siswa ?? '-' }}</td>
                                <td><span class="badge badge-soft-{{ in_array($t->jenis_trans, ['setoran', 'koreksi_setoran']) ? 'success' : 'danger' }}">@if ($t->isKoreksi())<i class="fa-solid fa-rotate me-1"></i>@endif{{ $t->jenis_label }}</span></td>
                                <td class="num fw-semibold text-end small">{{ $t->nominal_rupiah }}</td>
                                <td class="small">{{ $t->user?->nama_petugas ?? '-' }}</td>
                                <td><span class="status-badge {{ $t->status === 'berhasil' ? 'green' : ($t->status === 'pending' ? 'orange' : ($t->status === 'ditolak' ? 'red' : 'gray')) }}">{{ $t->status === 'berhasil' ? 'Berhasil' : $t->status_label }}</span></td>
                                <td>@include('transaksi.partials.aksi', ['transaksi' => $t])</td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-inbox"></i></div><div class="empty-title">Belum ada transaksi hari ini</div><div class="empty-desc">Transaksi terbaru akan muncul di sini.</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="modal fade teller-customer-modal teller-scan-result-modal" id="modalRingkasanNasabah" tabindex="-1" aria-labelledby="modalRingkasanNasabahLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h2 class="modal-title" id="modalRingkasanNasabahLabel"><i class="fa-solid fa-qrcode me-2 text-primary"></i>Nasabah Ditemukan</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                <div class="modal-body">
                    <div class="teller-scan-card">
                        <div class="teller-scan-card-top"><span class="teller-scan-brand"><i class="fa-solid fa-building-columns"></i> BANK MINI PASAR KELAS</span><span class="teller-scan-active"><i class="fa-solid fa-circle"></i> Nasabah Aktif</span></div>
                        <div class="teller-scan-identity">
                            <div class="customer-summary-avatar" id="ringkasanAvatar">N</div>
                            <div class="customer-summary-identity"><div class="customer-summary-name" id="ringkasanNama">-</div><div class="teller-scan-meta"><span id="ringkasanNis">NIS -</span><span id="ringkasanKelas">Kelas -</span></div></div>
                            <span class="teller-scan-account" id="ringkasanRekening">-</span>
                        </div>
                        <div class="teller-scan-verified"><i class="fa-solid fa-check"></i> Terverifikasi di database nasabah</div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" id="btnScanUlangDariRingkasan"><i class="fa-solid fa-rotate-left me-1"></i>Batal / Scan Ulang</button><a href="#" id="btnLanjutFormTransaksi" class="btn btn-primary"><i class="fa-solid fa-arrow-right me-1"></i>Lanjut ke Form Transaksi</a></div>
            </div>
        </div>
    </div>
    <style>
        .teller-scan-result-modal { background: rgba(12,22,43,.58); backdrop-filter: blur(5px); }
        .teller-scan-result-modal.show { display:flex !important; align-items:center; }
        .teller-scan-result-modal .modal-dialog { width:min(420px, calc(100% - 28px)); max-width:420px; }
        .teller-scan-result-modal .modal-content { padding:14px; border:1px solid rgba(255,255,255,.75); border-radius:16px; background:#f8fafc; box-shadow:0 18px 55px rgba(0,0,0,.28); }
        .teller-scan-result-modal .modal-header { padding:0 0 12px; border:0; }
        .teller-scan-result-modal .modal-title { font-size:1rem; }
        .teller-scan-result-modal .modal-body { padding:0; }
        .teller-scan-result-modal .modal-footer { justify-content:space-between; gap:8px; padding:12px 0 0; border:0; }
        .teller-scan-card { padding:13px; color:#fff; border-radius:11px; background:linear-gradient(135deg,#10264f,#173b79); box-shadow:0 4px 12px rgba(9,27,61,.25); }
        .teller-scan-card-top,.teller-scan-identity { display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .teller-scan-brand { font-size:.65rem; font-weight:700; }
        .teller-scan-brand i { color:#73a4ff; margin-right:4px; }
        .teller-scan-active { padding:4px 8px; border-radius:20px; color:#72f0bf; background:rgba(12,180,125,.18); font-size:.62rem; white-space:nowrap; }
        .teller-scan-active i { font-size:.4rem; margin-right:3px; }
        .teller-scan-identity { justify-content:flex-start; margin-top:16px; }
        .teller-scan-result-modal .customer-summary-avatar { display:grid; place-items:center; width:42px; height:42px; flex:0 0 42px; border-radius:10px; color:#10264f; background:linear-gradient(145deg,#a8ceff,#5794f5); font-weight:800; }
        .teller-scan-result-modal .customer-summary-name { color:#fff; font-size:.9rem; }
        .teller-scan-meta { display:flex; flex-wrap:wrap; gap:4px 12px; color:#b9c8e3; font-size:.65rem; }
        .teller-scan-account { margin-left:auto; padding:3px 6px; border:1px solid rgba(185,200,227,.4); border-radius:4px; color:#dbeafe; font-size:.65rem; white-space:nowrap; }
        .teller-scan-verified { margin-top:15px; color:#6fe8b4; font-size:.65rem; }
        @media(max-width:420px) { .teller-scan-result-modal .modal-footer { align-items:stretch; flex-direction:column-reverse; } .teller-scan-result-modal .modal-footer > * { width:100%; } }
    </style>

    <div class="modal fade" id="modalScanNasabah" tabindex="-1" aria-labelledby="modalScanNasabahLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h2 class="modal-title" id="modalScanNasabahLabel"><i class="fa-solid fa-qrcode me-2 text-primary"></i>Scan QR Nasabah</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body text-center"><p class="small text-muted">Arahkan QR rekening nasabah ke kamera.</p><div id="readerNasabahDashboard" class="mx-auto rounded border bg-light"></div><div id="statusScanNasabahDashboard" class="small text-muted mt-3" aria-live="polite"></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-primary btn-sm d-none" id="btnUlangScanNasabahDashboard">Scan Ulang</button><button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        const tellerHourlyData = @json($grafikJam);
        const tellerChart = document.getElementById('chartTransaksiHarian');
        if (tellerChart && window.Chart) {
            new Chart(tellerChart, {
                type: 'line',
                data: {
                    labels: tellerHourlyData.map((item) => item.label),
                    datasets: [
                        { label: 'Setoran', data: tellerHourlyData.map((item) => item.setoran), borderColor: '#16a34a', backgroundColor: 'rgba(22, 163, 74, .12)', fill: true, tension: .38, pointRadius: 2, borderWidth: 2 },
                        { label: 'Penarikan', data: tellerHourlyData.map((item) => item.penarikan), borderColor: '#2457e6', backgroundColor: 'rgba(36, 87, 230, .10)', fill: true, tension: .38, pointRadius: 2, borderWidth: 2 },
                    ],
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grace: '10%', ticks: { stepSize: 10000, callback: (value) => 'Rp ' + Number(value).toLocaleString('id-ID') }, grid: { color: '#edf0f5' } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 6 } } } },
            });
        }

        (function () {
            const form = document.getElementById('formCariNasabahDashboard');
            const input = document.getElementById('inputCariNasabahDashboard');
            const feedback = document.getElementById('hasilCariNasabahDashboard');
            const submit = document.getElementById('btnCariNasabahDashboard');
            const modalEl = document.getElementById('modalRingkasanNasabah');
            const scanModal = document.getElementById('modalScanNasabah');
            if (!form || !input || !feedback || !modalEl) return;

            let activeFormUrl = '';
            let scanner = null;
            let scannerStarting = false;
            let scannerRunning = false;
            let scanLocked = false;

            function showFeedback(message, type) {
                feedback.className = 'teller-lookup-feedback is-' + type;
                feedback.textContent = message;
            }

            function showCustomer(data) {
                const customer = data.nasabah;
                activeFormUrl = data.form_url;
                document.getElementById('ringkasanNama').textContent = customer.nama || '-';
                document.getElementById('ringkasanRekening').textContent = customer.rekening || '-';
                document.getElementById('ringkasanNis').textContent = 'NIS ' + (customer.nis || '-');
                document.getElementById('ringkasanKelas').textContent = 'Kelas ' + (customer.kelas || '-');
                document.getElementById('ringkasanAvatar').textContent = (customer.nama || 'N').trim().split(/\s+/).slice(0, 2).map((word) => word.charAt(0).toUpperCase()).join('');
                document.getElementById('btnLanjutFormTransaksi').href = activeFormUrl;
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            async function lookupCustomer(keyword) {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                if (!keyword.trim()) {
                    showFeedback('Masukkan NIS, nomor rekening, atau QR nasabah.', 'error');
                    return;
                }
                submit.disabled = true;
                submit.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';
                showFeedback('Memeriksa data nasabah aktif...', 'loading');
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ cari: keyword.trim() }),
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) throw new Error(data.message || 'Nasabah aktif tidak ditemukan.');
                    showFeedback('Nasabah aktif ditemukan.', 'success');
                    showCustomer(data);
                } catch (error) {
                    showFeedback(error.message || 'Pencarian gagal. Coba lagi.', 'error');
                } finally {
                    submit.disabled = false;
                    submit.innerHTML = '<i class="fa-solid fa-magnifying-glass me-1"></i>Cari';
                }
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                lookupCustomer(input.value);
            });

            function setScanStatus(message, className) {
                const status = document.getElementById('statusScanNasabahDashboard');
                status.className = 'small mt-3 ' + (className || 'text-muted');
                status.textContent = message;
            }

            async function stopScan() {
                if (!scanner) return;
                const current = scanner;
                scanner = null;
                scannerRunning = false;
                try { await current.stop(); } catch (error) {}
                try { await current.clear(); } catch (error) {}
            }

            async function startScan() {
                if (scannerStarting || scannerRunning) return;
                scannerStarting = true;
                scanLocked = false;
                const reader = document.getElementById('readerNasabahDashboard');
                reader.innerHTML = '';
                document.getElementById('btnUlangScanNasabahDashboard').classList.add('d-none');
                if (!window.isSecureContext && !['localhost', '127.0.0.1'].includes(location.hostname)) {
                    setScanStatus('Kamera hanya tersedia pada koneksi aman atau localhost.', 'text-warning');
                    scannerStarting = false;
                    return;
                }
                if (!window.Html5Qrcode) {
                    setScanStatus('Library pemindai QR gagal dimuat.', 'text-danger');
                    scannerStarting = false;
                    return;
                }
                setScanStatus('Menyalakan kamera...', 'text-primary');
                try {
                    const instance = new Html5Qrcode('readerNasabahDashboard');
                    scanner = instance;
                    await instance.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 220, height: 220 } }, async (decoded) => {
                        if (scanLocked) return;
                        scanLocked = true;
                        setScanStatus('QR terbaca. Memeriksa nasabah...', 'text-primary');
                        await stopScan();
                        input.value = decoded.trim();
                        scanModal.addEventListener('hidden.bs.modal', function onScanModalHidden() {
                            scanModal.removeEventListener('hidden.bs.modal', onScanModalHidden);
                            lookupCustomer(input.value);
                        });
                        bootstrap.Modal.getOrCreateInstance(scanModal).hide();
                    }, function () {});
                    scannerRunning = true;
                    setScanStatus('Kamera aktif. Arahkan ke QR nasabah.', 'text-success');
                } catch (error) {
                    setScanStatus('Kamera tidak dapat diakses. Periksa izin kamera.', 'text-danger');
                    document.getElementById('btnUlangScanNasabahDashboard').classList.remove('d-none');
                    scanner = null;
                } finally {
                    scannerStarting = false;
                }
            }

            scanModal?.addEventListener('shown.bs.modal', startScan);
            scanModal?.addEventListener('hidden.bs.modal', stopScan);
            document.getElementById('btnUlangScanNasabahDashboard').addEventListener('click', startScan);
            document.getElementById('btnScanUlangDariRingkasan').addEventListener('click', function () {
                bootstrap.Modal.getInstance(modalEl).hide();
                scanModal.addEventListener('hidden.bs.modal', startScan, { once: true });
                bootstrap.Modal.getOrCreateInstance(scanModal).show();
            });
            document.getElementById('btnLanjutFormTransaksi').addEventListener('click', function (event) {
                if (!activeFormUrl) event.preventDefault();
            });
        })();
    </script>
@endpush
