@extends('layouts.app')

@section('title', 'Form Transaksi Teller')
@section('page-title', 'Form Transaksi')
@section('page-subtitle', 'Proses setoran atau penarikan nasabah')

@section('content')
    <div class="page-animate teller-transaction-page">
        <div class="page-header">
            <div>
                <h1>Form Transaksi</h1>
                <div class="page-header-sub">Pastikan identitas nasabah dan saldo sudah sesuai sebelum memproses.</div>
            </div>
            <a href="{{ route('teller.dashboard') }}" class="quick-action"><i class="fa-solid fa-arrow-left"></i><span>Kembali ke Dashboard</span></a>
        </div>

        @if (session('error'))
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>{{ $errors->first() }}</div>
        @endif

        <div class="teller-transaction-layout teller-transaction-layout-preview">
            <div class="teller-transaction-form-column">
                <section class="data-panel teller-transaction-customer">
                    <div class="panel-body">
                        <div class="teller-transaction-id-card">
                            <div class="teller-id-card-top"><span><i class="fa-solid fa-building-columns"></i> BANK MINI</span><span><i class="fa-solid fa-circle"></i> Nasabah Aktif</span></div>
                            <div class="teller-id-card-main">
                                <div class="customer-summary-avatar">{{ collect(explode(' ', trim($nasabah->nama_siswa)))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('') ?: 'N' }}</div>
                                <div class="teller-id-card-info"><strong>{{ $nasabah->nama_siswa }}</strong><span>NIS {{ $nasabah->nis }} &nbsp; Kelas {{ $nasabah->kelas }}</span></div>
                                <span class="teller-id-account">{{ $nasabah->no_rekening }}</span>
                            </div>
                            <div class="teller-id-verified"><i class="fa-solid fa-check"></i> Terverifikasi di database nasabah</div>
                        </div>
                    </div>
                </section>

                <section class="data-panel transaction-form teller-transaction-form-panel">
                    <div class="panel-head"><div class="panel-title"><span class="dot"></span>Detail Transaksi</div></div>
                    <div class="panel-body">
                    <form method="POST" action="{{ route('teller.transaksi.proses') }}" id="formTransaksi" data-wait-submit>
                        @csrf
                        <input type="hidden" name="token_transaksi" value="{{ $tokenForm }}">
                        <input type="hidden" name="id_nasabah" value="{{ $nasabah->id }}">
                        <input type="hidden" id="saldoSebelum" value="{{ (int) $nasabah->saldo }}">

                        <div class="mb-3">
                            <label class="form-label" id="lblJenisTransaksi">Jenis Transaksi</label>
                            <div class="segmented" role="group" aria-labelledby="lblJenisTransaksi">
                                <button type="button" class="seg-btn active" data-jenis-target="jenisTrans" data-value="setoran"><i class="fa-solid fa-arrow-down text-success"></i>Setoran Tunai</button>
                                <button type="button" class="seg-btn" data-jenis-target="jenisTrans" data-value="penarikan"><i class="fa-solid fa-arrow-up text-danger"></i>Penarikan Tunai</button>
                            </div>
                            <input type="hidden" name="jenis_trans" id="jenisTrans" value="setoran">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><label class="form-label" for="namaPemilik">Pemilik Rekening</label><input type="text" class="form-control" id="namaPemilik" value="{{ $nasabah->nama_siswa }}" readonly></div>
                            <div class="col-md-6"><label class="form-label" for="noRekening">Nomor Rekening</label><input type="text" class="form-control" id="noRekening" value="{{ $nasabah->no_rekening }}" readonly></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="nominal">Nominal Transaksi (Rp)</label>
                            <div class="input-group"><span class="input-group-text">Rp</span><input type="number" name="nominal" id="nominal" class="form-control" min="1" required placeholder="Masukkan nominal"><span class="input-group-text">.000</span></div>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                @foreach ([10000, 20000, 50000, 100000] as $shortcut)
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-shortcut="{{ $shortcut }}">+{{ number_format($shortcut, 0, ',', '.') }}</button>
                                @endforeach
                            </div>
                            <div class="form-text">Pilih nominal cepat atau ketik jumlah transaksi.</div>
                            <div class="mt-1 fw-semibold" id="terbilangNominal" aria-live="polite">Terbilang: —</div>
                        </div>

                        <div class="mb-3 balance-summary">
                            <div class="bs-box"><div class="bs-label">Saldo Sebelum Transaksi</div><div class="bs-value" id="teksSaldoSebelum"></div></div>
                            <div class="bs-box"><div class="bs-label">Estimasi Saldo Setelah</div><div class="bs-value" id="teksSaldoSesudah"></div></div>
                        </div>

                        <div class="mb-3"><label class="form-label" for="catatanTransaksi">Catatan (opsional)</label><textarea name="catatan" id="catatanTransaksi" class="form-control" rows="2" maxlength="500" placeholder="Catatan transaksi"></textarea></div>

                        <div class="mb-3" id="blokKonfirmasiBesar" style="display:none;">
                            <div class="border rounded p-3 bg-warning-subtle">
                                <div class="form-check"><input class="form-check-input" type="checkbox" value="1" name="konfirmasi_h1" id="konfirmasiH1"><label class="form-check-label fw-semibold" for="konfirmasiH1">Penarikan besar — konfirmasi nasabah H-1 sudah diterima</label></div>
                            </div>
                        </div>

                        <div class="alert alert-info py-2 small"><i class="fa-solid fa-mobile-screen-button me-1"></i>Nasabah akan menerima permintaan konfirmasi di aplikasi dan memasukkan PIN sendiri. Permintaan berlaku 5 menit.</div>

                        <div class="d-flex gap-2 flex-wrap"><button type="button" class="btn btn-outline-secondary" id="btnResetTransaksi"><i class="fa-solid fa-rotate-left me-1"></i>Reset</button><button type="submit" class="btn btn-primary flex-fill btn-submit-loading"><i class="fa-solid fa-paper-plane me-1"></i>Kirim Permintaan</button></div>
                    </form>
                    </div>
                </section>
            </div>

            <aside class="teller-transaction-receipt" aria-label="Pratinjau bukti transaksi">
                <div class="teller-receipt-paper">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="Logo {{ $bank['nama'] }}" class="teller-receipt-logo">
                    @else
                        <div class="teller-receipt-logo-text">BM</div>
                    @endif
                    <div class="teller-receipt-bank">{{ $bank['nama'] }}</div>
                    <div class="teller-receipt-sub">{{ $bank['lembaga'] }}</div>
                    <div class="teller-receipt-tagline">Sistem Informasi E-Teller</div>
                    <div class="teller-receipt-address">{{ $bank['alamat'] }}</div>
                    <div class="teller-receipt-separator"></div>
                    <div class="teller-receipt-title">BUKTI TRANSAKSI</div>
                    <div class="teller-receipt-row"><span>No. Struk</span><strong>TRX-{{ now()->format('Ymd') }}-000000</strong></div>
                    <div class="teller-receipt-row"><span>Tanggal</span><strong>{{ now()->format('d/m/Y') }}</strong></div>
                    <div class="teller-receipt-row"><span>Waktu</span><strong>{{ now()->format('H:i:s') }} WIB</strong></div>
                    <div class="teller-receipt-separator"></div>
                    <div class="teller-receipt-section">DATA NASABAH</div>
                    <div class="teller-receipt-row"><span>Nama</span><strong>{{ $nasabah->nama_siswa }}</strong></div>
                    <div class="teller-receipt-row"><span>Rekening</span><strong>{{ $nasabah->no_rekening }}</strong></div>
                    <div class="teller-receipt-row"><span>NIS</span><strong>{{ $nasabah->nis }}</strong></div>
                    <div class="teller-receipt-row"><span>Kelas</span><strong>{{ $nasabah->kelas }}</strong></div>
                    <div class="teller-receipt-row"><span>Produk</span><strong>{{ $nasabah->produk_label }}</strong></div>
                    <div class="teller-receipt-separator"></div>
                    <div class="teller-receipt-section">DETAIL TRANSAKSI</div>
                    <div class="teller-receipt-row"><span>Jenis</span><strong id="previewJenis">SETORAN</strong></div>
                    <div class="teller-receipt-row"><span>Nominal</span><strong id="previewNominal">Rp 0</strong></div>
                    <div class="teller-receipt-row"><span>Status</span><strong>DIPROSES</strong></div>
                    <div class="teller-receipt-amount"><span>NOMINAL TRANSAKSI</span><strong id="previewNominalBesar">Rp 0</strong><small>Terbilang: <span id="previewTerbilang">Nol Rupiah</span></small></div>
                    <div class="teller-receipt-row"><span>Saldo Sebelum</span><strong>{{ $nasabah->saldo_rupiah }}</strong></div>
                    <div class="teller-receipt-row"><span>Saldo Sesudah</span><strong id="previewSaldoSesudah">{{ $nasabah->saldo_rupiah }}</strong></div>
                    <div class="teller-receipt-row"><span>Teller</span><strong>{{ auth()->user()->nama_petugas ?? auth()->user()->name }}</strong></div>
                    <div class="teller-receipt-separator"></div>
                    <div class="teller-receipt-qr">{!! \App\Support\QrisNasabah::svg($nasabah, 90, 1) !!}</div>
                    <div class="teller-receipt-qr-caption">No. {{ $nasabah->no_rekening }}</div>
                    <div class="teller-receipt-footer">Terima kasih telah bertransaksi<br>{{ $bank['nama'] }}</div>
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.batasPenarikanBesar = {{ \App\Models\Konfigurasi::batasPenarikanBesar() }};
        (function () {
            const form = document.getElementById('formTransaksi');
            const nominal = document.getElementById('nominal');
            const jenis = document.getElementById('jenisTrans');
            const saldo = Number(document.getElementById('saldoSebelum').value || 0);
            const batas = Number(window.batasPenarikanBesar || 0);
            const terbilangLabel = document.getElementById('terbilangNominal');
            const blokKonfirmasi = document.getElementById('blokKonfirmasiBesar');
            const satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

            function terbilang(nilai) {
                const angka = Math.floor(Math.abs(nilai));
                if (angka < 12) return satuan[angka];
                if (angka < 20) return terbilang(angka - 10) + ' belas';
                if (angka < 100) return terbilang(Math.floor(angka / 10)) + ' puluh ' + terbilang(angka % 10);
                if (angka < 200) return 'seratus ' + terbilang(angka - 100);
                if (angka < 1000) return terbilang(Math.floor(angka / 100)) + ' ratus ' + terbilang(angka % 100);
                if (angka < 2000) return 'seribu ' + terbilang(angka - 1000);
                if (angka < 1e6) return terbilang(Math.floor(angka / 1000)) + ' ribu ' + terbilang(angka % 1000);
                if (angka < 1e9) return terbilang(Math.floor(angka / 1e6)) + ' juta ' + terbilang(angka % 1e6);
                return terbilang(Math.floor(angka / 1e9)) + ' miliar ' + terbilang(angka % 1e9);
            }

            function updateForm() {
                const amount = Number(nominal.value || 0);
                const balanceAfter = Math.max(0, saldo + (jenis.value === 'setoran' ? amount : -amount));
                document.getElementById('teksSaldoSebelum').textContent = 'Rp ' + saldo.toLocaleString('id-ID');
                document.getElementById('teksSaldoSesudah').textContent = 'Rp ' + balanceAfter.toLocaleString('id-ID');
                const nominalFormatted = 'Rp ' + amount.toLocaleString('id-ID');
                document.getElementById('previewJenis').textContent = jenis.value === 'setoran' ? 'SETORAN' : 'PENARIKAN';
                document.getElementById('previewNominal').textContent = nominalFormatted;
                document.getElementById('previewNominalBesar').textContent = nominalFormatted;
                document.getElementById('previewTerbilang').textContent = amount > 0 ? terbilang(amount) + ' rupiah' : 'Nol Rupiah';
                document.getElementById('previewSaldoSesudah').textContent = 'Rp ' + balanceAfter.toLocaleString('id-ID');
                terbilangLabel.textContent = 'Terbilang: ' + (amount > 0 ? terbilang(amount) + ' rupiah' : '—');
                blokKonfirmasi.style.display = jenis.value === 'penarikan' && amount > batas ? 'block' : 'none';
            }

            nominal.addEventListener('input', updateForm);
            jenis.addEventListener('change', updateForm);
            updateForm();

            document.getElementById('btnResetTransaksi').addEventListener('click', function () {
                form.reset();
                jenis.value = 'setoran';
                document.querySelector('[data-value="setoran"]').classList.add('active');
                document.querySelector('[data-value="penarikan"]').classList.remove('active');
                nominal.dispatchEvent(new Event('input', { bubbles: true }));
                nominal.focus();
            });

            form.addEventListener('submit', function (event) {
                if (jenis.value === 'penarikan' && Number(nominal.value || 0) > batas && !document.getElementById('konfirmasiH1').checked) {
                    event.preventDefault();
                    if (window.showToast) window.showToast('Penarikan besar wajib dikonfirmasi nasabah H-1.', 'warning');
                    blokKonfirmasi.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'F2') {
                    event.preventDefault();
                    nominal.focus();
                    nominal.select();
                }
            });
        })();
    </script>
@endpush
