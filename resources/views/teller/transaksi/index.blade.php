@extends('layouts.app')
@section('title', 'Transaksi Teller')
@section('page-title', 'Transaksi Loket')
@section('page-subtitle', 'Cari nasabah lalu proses setoran atau penarikan')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Transaksi</h1>
                <div class="page-header-sub">
                    Loket teller — {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                </div>
            </div>
            <div class="header-actions">
                <span class="status-badge green">Loket Aktif</span>
                <button type="button" class="quick-action" data-bs-toggle="modal" data-bs-target="#modalScanQr">
                    <i class="fa-solid fa-qrcode"></i><span>Scan QR Nasabah</span>
                </button>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="data-panel mb-3">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot"></span>Cari Nasabah</div>
                    </div>
                    <div class="panel-body p-3">
                         <form method="POST" action="{{ route('teller.transaksi.cari') }}" id="formCari">
                            @csrf
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-qrcode"></i></span>
                                <input type="text" name="cari" id="inputCari" class="form-control"
                                       placeholder="Scan QR / No. Rekening / NIS" value="{{ request('cari') }}" autofocus
                                       aria-label="Nomor rekening atau NIS nasabah">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#modalScanQr" title="Scan QR Code dengan Kamera"
                                        aria-label="Scan QR dengan kamera">
                                    <i class="fa-solid fa-camera"></i>
                                </button>
                                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-search me-1"></i>Cari</button>
                            </div>
                        </form>
                        <div class="d-flex align-items-center gap-2 mt-2 small text-muted">
                            <i class="fa-solid fa-circle-info text-primary"></i>
                            <span>Nasabah menunjukkan QR Code di aplikasi/kartunya, lalu teller memindai melalui kamera loket.</span>
                        </div>

                        @if(isset($success))
                            <div class="alert alert-success small py-2 mt-2 mb-0 d-flex align-items-center">
                                <i class="fa-solid fa-circle-check me-2"></i>{{ $success }}
                            </div>
                        @endif
                        @if(isset($error))
                            <div class="alert alert-danger small py-2 mt-2 mb-0 d-flex align-items-center">
                                <i class="fa-solid fa-circle-exclamation me-2"></i>{{ $error }}
                            </div>
                        @endif
                    </div>
                </div>

                @if(isset($nasabah) && $nasabah)
                    <div class="customer-card mb-3">
                        <div class="cus-avatar">
                            @php
                                $inisial = implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), explode(' ', trim($nasabah->nama_siswa))));
                                if (mb_strlen($inisial) > 2) { $inisial = mb_substr($inisial, 0, 2); }
                                @endphp
                            {{ $inisial ?: 'N' }}
                        </div>
                        <div class="flex-fill">
                            <div class="cus-name">{{ $nasabah->nama_siswa }}</div>
                            <div class="cus-meta">
                                Rek: <code>{{ $nasabah->no_rekening }}</code> · NIS: {{ $nasabah->nis }} ·
                                Kelas: {{ $nasabah->kelas }}
                            </div>
                            <div class="cus-balance">Saldo: {{ $nasabah->saldo_rupiah }}</div>
                        </div>
                        <span class="status-badge green">Aktif</span>
                    </div>
                @else
                    <div class="empty-state border" style="border-radius:var(--r);">
                        <div class="empty-icon"><i class="fa-solid fa-user-magnifying-glass"></i></div>
                        <div class="empty-title">Silakan cari nasabah</div>
                        <div class="empty-desc">Masukkan nomor rekening atau NIS untuk menampilkan data saldo nasabah.</div>
                    </div>
                @endif
            </div>

            <div class="col-lg-7">
                @if(isset($nasabah) && $nasabah)
                    <div class="data-panel transaction-form">
                        <div class="panel-head">
                            <div class="panel-title"><span class="dot" style="background:var(--purple)"></span>Form Transaksi</div>
                        </div>
                        <div class="panel-body p-3">
                            <form method="POST" action="{{ route('teller.transaksi.proses') }}" id="formTransaksi" data-wait-submit>
                                @csrf
                                <input type="hidden" name="token_transaksi" value="{{ $tokenForm }}">
                                <input type="hidden" name="id_nasabah" value="{{ $nasabah->id }}">
                                <input type="hidden" id="saldoSebelum" value="{{ (int) $nasabah->saldo }}">

                                <div class="mb-3">
                                    <label class="form-label" id="lblJenisTransaksi">Jenis Transaksi</label>
                                    <div class="segmented" role="group" aria-labelledby="lblJenisTransaksi">
                                        <button type="button" class="seg-btn active" data-jenis-target="jenisTrans" data-value="setoran" id="btnJenisSetoran">
                                            <i class="fa-solid fa-arrow-down text-success"></i>Setoran Tunai
                                        </button>
                                        <button type="button" class="seg-btn" data-jenis-target="jenisTrans" data-value="penarikan" id="btnJenisPenarikan">
                                            <i class="fa-solid fa-arrow-up text-danger"></i>Penarikan Tunai
                                        </button>
                                    </div>
                                    <input type="hidden" name="jenis_trans" id="jenisTrans" value="setoran">
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="namaPemilik">Pemilik Rekening</label>
                                        <input type="text" class="form-control" id="namaPemilik" value="{{ $nasabah->nama_siswa }}" readonly tabindex="-1">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="noRekening">Nomor Rekening</label>
                                        <input type="text" class="form-control" id="noRekening" value="{{ $nasabah->no_rekening }}" readonly tabindex="-1">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="nominal">Nominal Transaksi (Rp)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="nominal" id="nominal" class="form-control" min="1" required
                                               placeholder="Masukkan nominal" aria-describedby="hintNominal">
                                        <span class="input-group-text">.000</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 mt-2 saldo-chips">
                                        @foreach([10000, 20000, 50000, 100000] as $c)
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-shortcut="{{ $c }}">
                                                +{{ number_format($c, 0, ',', '.') }}
                                            </button>
                                        @endforeach
                                    </div>
                                    <div class="form-text" id="hintNominal">Gunakan shortcut untuk nominal cepat. Tekan <kbd>Enter</kbd> untuk memproses, <kbd>F2</kbd> fokus ke nominal.</div>
                                    <div class="mt-1 fw-semibold" id="terbilangNominal" aria-live="polite" style="text-transform:uppercase; font-size:.85rem;">Terbilang: —</div>
                                </div>

                                <div class="mb-3 balance-summary">
                                    <div class="bs-box">
                                        <div class="bs-label">Saldo Sebelum Transaksi</div>
                                        <div class="bs-value" id="teksSaldoSebelum"></div>
                                    </div>
                                    <div class="bs-box">
                                        <div class="bs-label">Estimasi Saldo Setelah</div>
                                        <div class="bs-value" id="teksSaldoSesudah"></div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="catatanTransaksi">Catatan (opsional)</label>
                                    <textarea name="catatan" id="catatanTransaksi" class="form-control" rows="2"
                                              maxlength="500" placeholder="Catatan transaksi, contoh: angsuran ke-4"></textarea>
                                </div>

                                <div class="mb-3" id="blokKonfirmasiBesar" style="display:none;">
                                    <div class="border rounded p-3 bg-warning-subtle" style="border-color:#F3D89A !important;">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" value="1" name="konfirmasi_h1"
                                                   id="konfirmasiH1">
                                            <label class="form-check-label fw-semibold" for="konfirmasiH1">
                                                Penarikan besar — sudah ada konfirmasi nasabah (H-1)
                                            </label>
                                        </div>
                                        <input type="text" name="catatan" class="form-control form-control-sm"
                                               placeholder="Catatan konfirmasi (opsional)" maxlength="500">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="pinKeamanan">PIN Keamanan Nasabah</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                                        <input type="password" name="pin_keamanan" id="pinKeamanan" maxlength="6"
                                               class="form-control" placeholder="PIN 6 digit" required
                                               inputmode="numeric" pattern="[0-9]{6}" autocomplete="off">
                                    </div>
                                    <div class="form-text">PIN dikirim/disampaikan nasabah. Pastikan sesuai sebelum proses.</div>
                                </div>

                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-outline-secondary flex-fill" id="btnResetTransaksi">
                                        <i class="fa-solid fa-rotate-left me-1"></i>Reset
                                    </button>
                                    <!-- <button type="button" class="btn btn-outline-primary flex-fill js-simulasi" data-bs-toggle="modal" data-bs-target="#modalPin" data-pin-target="pinKeamanan">
                                        <i class="fa-solid fa-shield-halved me-1"></i>Verifikasi PIN
                                    </button> -->
                                    <!-- <button type="xbutton" class="btn btn-secondary flex-fill" onclick="showToast('Draft tersimpan di catatan browser (demo).', 'info')">
                                        <i class="fa-solid fa-floppy-disk me-1"></i>Simpan Draft
                                    </button> -->
                                    <button type="submit" class="btn btn-primary flex-fill btn-submit-loading">
                                        <i class="fa-solid fa-circle-check me-1"></i>Proses Transaksi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="data-panel">
                        <div class="panel-head">
                            <div class="panel-title"><span class="dot"></span>Transaksi Terbaru Loket</div>
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
                                        <th>Petugas</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($transaksiTerakhir as $t)
                                        <tr data-search="{{ $t->no_struk }} {{ $t->nasabah->nama_siswa ?? '' }}">
                                            <td><code class="small">{{ $t->no_struk }}</code></td>
                                            <td class="small text-muted">{{ $t->created_at->format('d/m H:i') }}</td>
                                            <td class="fw-semibold small">{{ $t->nasabah->nama_siswa ?? '-' }}</td>
                                            <td>
                                                @php $kc = $t->jenis_trans === 'setoran' ? 'success' : 'danger'; @endphp
                                                <span class="badge badge-soft-{{ $kc }}">{{ $t->jenis_label }}</span>
                                            </td>
                                            <td class="num fw-semibold text-end small">{{ $t->nominal_rupiah }}</td>
                                            <td class="small">{{ $t->user?->nama_petugas ?? '-' }}</td>
                                            <td>@include('transaksi.partials.aksi', ['transaksi' => $t])</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7">
                                                <div class="empty-state">
                                                    <div class="empty-icon"><i class="fa-solid fa-receipt"></i></div>
                                                    <div class="empty-title">Belum ada transaksi</div>
                                                    <div class="empty-desc">Cari nasabah di panel kiri untuk memulai transaksi.</div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Scan QR --}}
    <div class="modal fade" id="modalScanQr" tabindex="-1" aria-labelledby="modalScanQrLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="modalScanQrLabel"><i class="fa-solid fa-qrcode me-2 text-primary"></i>Scan QR Code Nasabah</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="small text-muted mb-3">Arahkan QR Code dari aplikasi/kartu nasabah ke kamera loket.</p>
                    <div id="reader" class="mx-auto rounded border bg-light"></div>
                    <div id="scanStatus" class="small text-muted mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary d-none btn-sm" id="btnScanUlang" onclick="startScanner()">
                        <i class="fa-solid fa-rotate me-1"></i>Scan Ulang
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Verifikasi PIN + konfirmasi transaksi --}}
    <div class="modal fade" id="modalPin" tabindex="-1" aria-labelledby="modalPinLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <form id="formVerifikasiPin">
                    <div class="modal-header">
                        <h6 class="modal-title" id="modalPinLabel"><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Konfirmasi Transaksi</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-3">Transaksi akan diproses setelah PIN nasabah dikonfirmasi. Pastikan data sudah benar.</p>
                        <div class="customer-card mb-3" style="padding:.6rem .8rem;">
                            <div class="cus-avatar" style="width:36px;height:36px;font-size:.8rem;">
                                {{ mb_strtoupper(mb_substr($nasabah?->nama_siswa ?? 'N', 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-semibold small">{{ $nasabah->nama_siswa ?? '-' }}</div>
                                <div class="text-muted" style="font-size:.7rem;">
                                    <span id="pinJenisLabel">Setoran</span> · <span id="pinNominalLabel">Rp 0</span> ·
                                    <span id="pinSaldoLabel"></span>
                                </div>
                            </div>
                        </div>
                        <label class="form-label" for="pinVerifikasi">PIN Keamanan (6 digit)</label>
                        <input type="password" class="form-control text-center" id="pinVerifikasi" maxlength="6"
                               placeholder="• • • • • •" inputmode="numeric" pattern="[0-9]{6}" autocomplete="off">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary btn-sm" id="btnPinLanjut" disabled>
                            <i class="fa-solid fa-check me-1"></i>Konfirmasi & Proses
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Modal Cetak Struk --}}
    <div class="modal fade" id="modalCetakStruk" tabindex="-1" aria-labelledby="modalCetakStrukLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="modalCetakStrukLabel"><i class="fa-solid fa-print me-2 text-primary"></i>Cetak Struk</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fa-solid fa-check-circle text-success" style="font-size:2.2rem;"></i>
                    <div class="fw-bold mt-2">Transaksi Berhasil Diproses</div>
                    @if ($transaksiTerakhir->isNotEmpty())
                        <div class="small text-muted mb-3">No. struk {{ $transaksiTerakhir->first()->no_struk }}</div>
                        <div class="d-grid gap-2">
                            <a href="{{ $transaksiTerakhir->first()->struk_url }}" class="btn btn-primary w-100">
                                <i class="fa-solid fa-receipt me-1"></i>Lihat Struk
                            </a>
                            <a href="{{ $transaksiTerakhir->first()->struk_cetak_url }}?cetak=1" target="_blank" rel="noopener"
                               class="btn btn-outline-primary w-100">
                                <i class="fa-solid fa-print me-1"></i>Cetak Struk
                            </a>
                            <a href="{{ $transaksiTerakhir->first()->struk_pdf_url }}" class="btn btn-outline-primary w-100">
                                <i class="fa-solid fa-file-pdf me-1"></i>Download PDF
                            </a>
                        </div>
                    @else
                        <div class="small text-muted mb-3">Struk dapat dicetak pada halaman struk setelah transaksi.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        let qrScanner = null;
        let isScanning = false;
        let isStarting = false;
        let stopRequested = false;
        let scanInProgress = false;

        const modalScanQrEl = document.getElementById('modalScanQr');
        if (modalScanQrEl) {
            modalScanQrEl.addEventListener('shown.bs.modal', startScanner);
            modalScanQrEl.addEventListener('hidden.bs.modal', stopScanner);
        }

        function setScanStatus(html) {
            const el = document.getElementById('scanStatus');
            if (el) el.innerHTML = html;
        }

        async function startScanner() {
            if (isScanning || isStarting) return;
            isStarting = true;
            stopRequested = false;
            scanInProgress = false;
            const btn = document.getElementById('btnScanUlang');
            if (btn) btn.classList.add('d-none');

            const reader = document.getElementById('reader');
            if (!reader) {
                isStarting = false;
                return;
            }
            reader.innerHTML = '';
            setScanStatus('<span class="text-primary"><i class="fa-solid fa-spinner fa-spin"></i> Menyalakan kamera...</span>');

            const isSecureContext = window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            if (!isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                setScanStatus('<span class="text-warning"><i class="fa-solid fa-triangle-exclamation"></i> Kamera hanya bisa dipakai dari browser yang aman. Buka <strong>http://127.0.0.1:8000</strong> lalu izinkan akses kamera.</span>');
                isStarting = false;
                return;
            }

            if (typeof Html5Qrcode === 'undefined') {
                setScanStatus('<span class="text-danger"><i class="fa-solid fa-circle-xmark"></i> Library pemindai QR Code gagal dimuat.</span>');
                isStarting = false;
                return;
            }

            const scanner = new Html5Qrcode('reader');
            qrScanner = scanner;
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                videoConstraints: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
            };

            try {
                try {
                    await scanner.start({ facingMode: 'environment' }, config, onScanSuccess, () => { });
                } catch (firstError) {
                    const cameras = await Html5Qrcode.getCameras();
                    if (cameras.length === 0) throw firstError;
                    await scanner.start(cameras[0].id, config, onScanSuccess, () => { });
                }

                isScanning = true;
                const video = reader.querySelector('video');
                if (!video) throw new Error('Preview kamera tidak tersedia.');
                video.setAttribute('autoplay', 'true');
                video.setAttribute('playsinline', 'true');
                video.muted = true;
                video.style.display = 'block';
                video.style.width = '100%';
                video.style.height = 'auto';
                await video.play();
                setScanStatus('<span class="text-success"><i class="fa-solid fa-camera"></i> Kamera aktif. Tunjukkan QR Code nasabah.</span>');

                if (stopRequested || !modalScanQrEl.classList.contains('show')) {
                    await stopScanner();
                }
            } catch (error) {
                if (!stopRequested) {
                    setScanStatus('<span class="text-danger"><i class="fa-solid fa-circle-xmark"></i> Kamera tidak dapat diakses.</span>');
                }
                if (qrScanner === scanner) {
                    qrScanner = null;
                }
                try {
                    await scanner.clear();
                } catch (clearError) {
                }
            } finally {
                isStarting = false;
            }
        }

        async function stopScanner() {
            stopRequested = true;
            const scanner = qrScanner;
            if (!scanner) return;

            qrScanner = null;
            isScanning = false;
            try {
                await scanner.stop();
            } catch (error) {
            }
            try {
                await scanner.clear();
            } catch (error) {
            }
        }

        function onScanSuccess(decodedText) {
            if (scanInProgress) return;
            scanInProgress = true;

            const code = decodedText.trim();
            if (!code) {
                scanInProgress = false;
                return;
            }

            stopScanner();
            setScanStatus('<span class="text-primary"><i class="fa-solid fa-spinner fa-spin"></i> Memeriksa nasabah...</span>');

            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            fetch('{{ route('teller.transaksi.cari') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ cari: code }),
            })
                .then((res) => res.json().catch(() => null).then((data) => ({ ok: res.ok, status: res.status, data })))
                .then(({ ok, status, data }) => {
                    if (status === 419) throw new Error('Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.');
                    if (!ok || !data || !data.success) {
                        throw new Error((data && data.message) ? data.message : 'Nasabah tidak ditemukan. Silakan coba lagi.');
                    }
                    setScanStatus('<span class="text-success fw-semibold"><i class="fa-solid fa-circle-check"></i> ' + data.message + '</span>');
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalScanQr'));
                        if (modal) modal.hide();
                        document.getElementById('inputCari').value = code;
                        document.getElementById('formCari').submit();
                    }, 800);
                })
                .catch((err) => {
                    scanInProgress = false;
                    setScanStatus('<span class="text-danger fw-semibold"><i class="fa-solid fa-circle-xmark"></i> ' + (err.message || 'QR tidak dikenali. Coba lagi.') + '</span>');
                    const btn = document.getElementById('btnScanUlang');
                    if (btn) btn.classList.remove('d-none');
                });
        }

        (function () {
            const form = document.getElementById('formTransaksi');
            if (!form) return;

            const nominal = document.getElementById('nominal');
            const blokKonfirmasiBesar = document.getElementById('blokKonfirmasiBesar');
            const batasPenarikanBesar = {{ \App\Models\Konfigurasi::batasPenarikanBesar() }};

            window.batasPenarikanBesar = batasPenarikanBesar;

            function fmt(v) {
                return 'Rp ' + (parseInt(v || '0', 10) || 0).toLocaleString('id-ID');
            }

            document.getElementById('btnResetTransaksi').addEventListener('click', function () {
                form.reset();
                const jenis = document.getElementById('jenisTrans');
                jenis.value = 'setoran';
                document.getElementById('btnJenisSetoran').classList.add('active');
                document.getElementById('btnJenisPenarikan').classList.remove('active');
                document.getElementById('nominal').dispatchEvent(new Event('input', { bubbles: true }));
                nominal.focus();
            });

            const modalPinEl = document.getElementById('modalPin');
            if (modalPinEl) {
                modalPinEl.addEventListener('show.bs.modal', function () {
                    const jenis = (document.getElementById('jenisTrans').value === 'setoran') ? 'Setoran' : 'Penarikan';
                    const n = parseInt(nominal.value || '0', 10) || 0;
                    document.getElementById('pinJenisLabel').textContent = jenis;
                    document.getElementById('pinNominalLabel').textContent = fmt(n);
                    document.getElementById('btnPinLanjut').disabled = true;
                    document.getElementById('pinVerifikasi').value = '';
                });

                document.getElementById('btnPinLanjut').addEventListener('click', function () {
                    const pin = document.getElementById('pinVerifikasi').value.trim();
                    if (!/^\d{6}$/.test(pin)) {
                        showToast('PIN harus 6 digit angka.', 'warning');
                        return;
                    }
                    document.getElementById('pinKeamanan').value = pin;
                    bootstrap.Modal.getInstance(modalPinEl).hide();
                    showToast('PIN terverifikasi. Klik Proses Transaksi untuk melanjutkan.', 'success');
                });

                document.getElementById('pinVerifikasi').addEventListener('input', function (e) {
                    this.value = this.value.replace(/\D/g, '');
                    document.getElementById('btnPinLanjut').disabled = !/^\d{6}$/.test(this.value);
                });
            }

            form.addEventListener('submit', function (e) {
                const jenis = document.getElementById('jenisTrans').value;
                const n = parseInt(nominal.value || '0', 10) || 0;
                if (jenis === 'penarikan' && n > batasPenarikanBesar) {
                    const cb = document.getElementById('konfirmasiH1');
                    if (cb && !cb.checked) {
                        e.preventDefault();
                        showToast('Penarikan besar wajib dicentang konfirmasi H-1.', 'warning');
                        document.getElementById('blokKonfirmasiBesar').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });

            const SATUAN = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

            function terbilang(n) {
                if (n < 12) return SATUAN[n];
                if (n < 20) return terbilang(n - 10) + ' belas';
                if (n < 100) return terbilang(Math.floor(n / 10)) + ' puluh ' + terbilang(n % 10);
                if (n < 200) return 'seratus ' + terbilang(n - 100);
                if (n < 1000) return terbilang(Math.floor(n / 100)) + ' ratus ' + terbilang(n % 100);
                if (n < 2000) return 'seribu ' + terbilang(n - 1000);
                if (n < 1e6) return terbilang(Math.floor(n / 1000)) + ' ribu ' + terbilang(n % 1000);
                if (n < 1e9) return terbilang(Math.floor(n / 1e6)) + ' juta ' + terbilang(n % 1e6);
                return terbilang(Math.floor(n / 1e9)) + ' miliar ' + terbilang(n % 1e9);
            }

            const teksTerbilang = document.getElementById('terbilangNominal');

            function updateTerbilang() {
                const n = parseInt(nominal.value || '0', 10) || 0;
                if (!teksTerbilang) return;
                teksTerbilang.textContent = 'Terbilang: ' + (n > 0 ? terbilang(n) + ' rupiah' : '—');
            }

            nominal.addEventListener('input', updateTerbilang);
            updateTerbilang();

            document.addEventListener('keydown', function (e) {
                if (e.key === 'F2') {
                    e.preventDefault();
                    nominal.focus();
                    nominal.select();
                    return;
                }
                if (e.key !== 'Enter') return;
                const el = e.target;
                if (!el || el.tagName === 'TEXTAREA') return;
                if (el.type === 'submit' || el.type === 'button') return;
                if (el.form === form) {
                    e.preventDefault();
                    form.requestSubmit();
                }
            });
        })();
    </script>
@endpush
