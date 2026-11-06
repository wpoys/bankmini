@extends('layouts.app')

@section('title', 'Ajukan Koreksi')
@section('page-title', 'Ajukan Koreksi Transaksi')
@section('page-subtitle', 'Koreksi hanya berlaku untuk transaksi yang ANDA proses sendiri')
@if ($modal)
    @section('body-class', 'modal-form role-teller')
    @push('styles')
        <style>
            .modal-form, .modal-form body, .modal-form .standalone-page { width: 100%; min-width: 0; height: 100%; min-height: 100%; margin: 0; overflow: hidden; background: #fff; }
            .modal-form .sidebar, .modal-form .topbar, .modal-form .page-header { display: none !important; }
            .modal-form .main-content { width: 100% !important; min-height: 100% !important; margin: 0 !important; padding: 0 !important; }
            .modal-form .standalone-page { padding: 0 !important; }
            .modal-form .page-animate { min-height: 100%; padding: 0 !important; }
            .modal-form .row.justify-content-center { display: block; margin: 0; }
            .modal-form .col-lg-8 { max-width: none; width: 100%; padding: 0; }
            .modal-form .data-panel { border: 0; box-shadow: none; }
            .modal-form .panel-body { padding: 12px 16px 72px !important; }
            .modal-form .koreksi-form-card { position: relative; display: flex; height: 100vh; min-height: 100vh; flex-direction: column; overflow: hidden; }
            .modal-form .koreksi-form-heading { position: sticky; top: 0; z-index: 2; display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-top: 3px solid #2f80ed; border-bottom: 1px solid #e5ebf3; background: #fff; }
            .modal-form .koreksi-heading-icon { display: grid; width: 26px; height: 26px; flex: 0 0 26px; place-items: center; border-radius: 8px; background: #edf4ff; color: #3478e5; font-size: 11px; }
            .modal-form .koreksi-heading-copy { min-width: 0; flex: 1; }
            .modal-form .koreksi-heading-badge { display: inline-block; margin-bottom: 2px; color: #5380c7; font-size: 10px; font-weight: 800; letter-spacing: .04em; }
            .modal-form .koreksi-form-heading h1 { margin: 0; color: #1e2a3d; font-size: 16px; font-weight: 800; }
            .modal-form .koreksi-heading-close { padding: 3px 5px; border: 0; background: transparent; color: #94a0b2; }
            .modal-form .koreksi-form-scroll { min-height: 0; flex: 1 1 auto; overflow-x: hidden; overflow-y: auto; padding-bottom: 60px; font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }
            .modal-form .koreksi-form-scroll .alert { padding: 8px 10px; font-size: 12px; }
            .modal-form .koreksi-form-label { display: block; margin: 0 0 5px; color: #536174; font-size: 11px; font-weight: 800; letter-spacing: .02em; text-transform: uppercase; }
            .modal-form .koreksi-form-group { margin-bottom: 10px; }
            .modal-form .koreksi-form-scroll > .row, .modal-form .koreksi-form-scroll > .row > .col-lg-8, .modal-form .koreksi-form-scroll .data-panel { width:100%; max-width:none; margin:0; }
            .modal-form .koreksi-form-scroll .data-panel { height:100%; }
            .modal-form .koreksi-form-scroll .panel-body { display:flex; min-height:0; flex-direction:column; }
            .modal-form .koreksi-compact-form { flex:1; }
            .koreksi-reason-chips { display:flex; flex-wrap:wrap; gap:4px; margin-bottom:5px; }
            .koreksi-reason-chips button { padding:4px 8px; border:1px solid #e3e9f1; border-radius:5px; background:#f5f7fa; color:#68778a; font-size:10px; }
            .koreksi-reason-chips button:hover { border-color:#9ab6ed; color:#326ed0; }
            .modal-form .koreksi-form-group select:focus, .modal-form .koreksi-form-group textarea:focus, .modal-form .koreksi-form-group input:focus { border-color:#8baeff; box-shadow:0 0 0 2px rgba(60,115,220,.12); }
            .modal-form .koreksi-amount-wrap .input-group-text { min-width:36px; justify-content:center; color:#718198; font-size:12px; font-weight:700; }
            .modal-form .koreksi-amount-wrap .form-control { font-size:13px; font-weight:800; }
            .modal-form .koreksi-amount-diff:not(.d-none) { padding:3px 6px; border:1px solid #f4dfa7; border-radius:5px; background:#fff9e9; }
            .modal-form .koreksi-form-group .form-select, .modal-form .koreksi-form-group .form-control { min-height: 38px; padding: 7px 10px; border-color: #d9e1ec; border-radius: 8px; font-size: 13px; }
            .modal-form .koreksi-form-group textarea.form-control { min-height: 80px; }
            .modal-form .koreksi-form-group .form-text { margin-top: 4px; color: #8793a4; font-size: 11px; }
            .modal-form .koreksi-type-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 8px; }
            .modal-form .koreksi-type-option { position: relative; display: flex; min-height: 64px; align-items: flex-start; gap: 8px; padding: 9px; border: 1px solid #dfe6ef; border-radius: 9px; background: #fff; cursor: pointer; }
            .modal-form .koreksi-type-option.is-active { border: 2px solid #8aaeff; padding: 7px; background: #f7faff; }
            .modal-form .koreksi-type-option input { position: absolute; opacity: 0; }
            .modal-form .koreksi-type-icon { display: grid; width: 22px; height: 22px; flex: 0 0 22px; place-items: center; border-radius: 5px; background: #e8f0ff; color: #3478e5; font-size: 10px; }
            .modal-form .koreksi-type-option[data-jenis="koreksi_penarikan"] .koreksi-type-icon { background: #e5f8ef; color: #12a56b; }
            .modal-form .koreksi-type-copy { display: grid; gap: 3px; color: #536174; font-size: 11px; }
            .modal-form .koreksi-type-copy strong { color: #29374b; font-size: 12px; }
            .modal-form .koreksi-type-copy small { color: #8995a6; font-size: 10px; }
            .modal-form .koreksi-type-check { position: absolute; top: 7px; right: 7px; color: #3276e4; font-size: 10px; }
            .modal-form .koreksi-type-option:not(.is-active) .koreksi-type-check { color: #d4dce7; }
            .modal-form .koreksi-amount-wrap { position: relative; }
            .modal-form .koreksi-amount-diff { position: absolute; top: -19px; right: 0; color: #bc7500; font-size: 10px; }
            .modal-form .koreksi-spellout { display: flex; justify-content: space-between; gap: 6px; margin-top: 4px; padding: 6px 8px; border: 1px solid #e7edf5; border-radius: 6px; background: #f7f9fc; color: #4e77bb; font-size: 10px; }
            .modal-form .koreksi-form-footer { position: absolute; right: 0; bottom: 0; left: 0; z-index: 20; display: flex !important; min-height: 54px; align-items: center; justify-content: space-between; gap: 8px; padding: 8px 12px; border-top: 1px solid #e5ebf3; background: #fff; box-shadow: 0 -3px 12px rgba(24,39,65,.06); visibility: visible !important; opacity: 1 !important; }
            .modal-form .koreksi-footer-user { color: #7b8797; font-size: 11px; }
            .modal-form .koreksi-footer-actions { display: flex; gap: 6px; }
            .modal-form .koreksi-form-footer .btn { min-height: 36px; padding: 7px 12px; border-radius: 7px; font-size: 12px; font-weight: 700; }
            @media(max-width:360px) { .modal-form .koreksi-type-grid { grid-template-columns: 1fr; } .modal-form .koreksi-form-footer { align-items: flex-start; flex-direction: column; } .modal-form .koreksi-footer-actions { width:100%; } .modal-form .koreksi-footer-actions .btn { flex:1; } }
        </style>
    @endpush
@endif
@if ($modal)
    @section('standalone-layout', true)
@endif

@section('content')
    <div class="page-animate koreksi-form-card">
        @if ($modal)
            <div class="koreksi-form-heading">
                <span class="koreksi-heading-icon"><i class="fa-solid fa-arrows-rotate"></i></span>
                <div class="koreksi-heading-copy"><span class="koreksi-heading-badge"><i class="fa-solid fa-circle me-1"></i> OPERASIONAL TELLER &amp; SUPERVISOR</span><h1>Ajukan Koreksi Transaksi</h1></div>
                <button type="button" class="koreksi-heading-close" data-dismiss-parent-modal aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @else
            <div class="page-header">
                <div><h1>Form Pengajuan Koreksi</h1><div class="page-header-sub">Koreksi transaksi yang keliru di loket Anda</div></div>
                <a href="{{ route('teller.koreksi.index') }}" class="quick-action"><i class="fa-solid fa-arrow-left"></i><span>Kembali</span></a>
            </div>
        @endif
        <div class="koreksi-form-scroll">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="data-panel">
                        <div class="panel-body p-3">
                            @if (! $modal)
                                <div class="alert alert-info small">
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    Saat disetujui Supervisor, sistem akan membuat transaksi pembalik dan menandai transaksi asli sebagai dikoreksi. Data asli tidak dihapus atau diubah.
                                </div>
                            @endif

                        @if ($errors->any())
                            <div class="alert alert-danger small">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('teller.koreksi.store') }}" id="formKoreksi" class="koreksi-compact-form">
                            @csrf
                            <div class="koreksi-form-group">
                                <label class="koreksi-form-label" for="nasabahId">Nasabah <span class="text-danger">*</span></label>
                                <select name="nasabah_id" id="nasabahId" class="form-select" required>
                                    <option value="">-- Pilih nasabah terdaftar --</option>
                                    @foreach ($nasabahList as $n)
                                        <option value="{{ $n->id }}" @selected(old('nasabah_id') == $n->id)>{{ $n->no_rekening }} · {{ $n->nama_siswa }} @if ($n->kelas) ({{ $n->kelas }}) @endif</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Pilih nasabah yang terkait dengan transaksi koreksi.</div>
                            </div>

                            <div class="koreksi-form-group">
                                <label class="koreksi-form-label" for="transaksiId">Transaksi yang Dikoreksi</label>
                                <select name="transaksi_id" id="transaksiId" class="form-select" onchange="toggleSumberKoreksi()">
                                    <option value="">-- Tanpa transaksi asli (uang rusak/palsu/temuan) --</option>
                                </select>
                                <div class="form-text"><i class="fa-solid fa-circle-info text-primary me-1"></i>Pilih nasabah untuk melihat transaksi. Kosongkan bila tidak terkait.</div>
                            </div>

                            <div class="koreksi-form-group">
                                <label class="koreksi-form-label">Jenis Koreksi <span class="text-danger">*</span></label>
                                <select name="jenis" id="jenisKoreksi" class="form-select visually-hidden" required tabindex="-1" aria-hidden="true">
                                    <option value="">-- Pilih arah koreksi --</option>
                                    <option value="koreksi_setoran" @selected(old('jenis') === 'koreksi_setoran')>Setoran Koreksi</option>
                                    <option value="koreksi_penarikan" @selected(old('jenis') === 'koreksi_penarikan')>Penarikan Koreksi</option>
                                </select>
                                <div class="koreksi-type-grid" role="radiogroup" aria-label="Jenis koreksi">
                                    <button type="button" class="koreksi-type-option" data-jenis="koreksi_setoran" aria-pressed="false"><span class="koreksi-type-icon"><i class="fa-solid fa-arrow-down"></i></span><span class="koreksi-type-copy"><strong>Setoran</strong><small>Membatalkan/mengurangi setoran</small></span><i class="fa-solid fa-circle-dot koreksi-type-check"></i></button>
                                    <button type="button" class="koreksi-type-option" data-jenis="koreksi_penarikan" aria-pressed="false"><span class="koreksi-type-icon"><i class="fa-solid fa-arrow-up"></i></span><span class="koreksi-type-copy"><strong>Penarikan</strong><small>Mengembalikan saldo tarikan nasabah</small></span><i class="fa-regular fa-circle koreksi-type-check"></i></button>
                                </div>
                                <div class="form-text"><i class="fa-solid fa-circle-info text-primary me-1"></i><span id="hintJenis">Arah koreksi mengikuti transaksi awal dan dapat disesuaikan.</span></div>
                            </div>

                            <div class="koreksi-form-group">
                                <label class="koreksi-form-label" for="nominalKoreksi">Nominal Koreksi (Rp) <span class="text-danger" id="wajibNominal" style="display:none;">*</span></label>
                                <div class="koreksi-amount-wrap"><span class="koreksi-amount-diff d-none" id="selisihNominal"></span><div class="input-group"><span class="input-group-text">Rp</span><input type="number" name="nominal" id="nominalKoreksi" class="form-control" min="1" value="{{ old('nominal') }}" placeholder="0"></div></div>
                                <div class="koreksi-spellout"><span><strong>TERBILANG:</strong> <em id="nominalTerbilang">—</em></span><span>Otomatis diselaraskan</span></div>
                                <div class="form-text"><span id="hintNominal">Kosongkan bila nominal sama dengan transaksi asli.</span></div>
                            </div>

                            <div class="koreksi-form-group">
                                <label class="koreksi-form-label" for="alasanKoreksi">Alasan Koreksi <span class="text-danger">*</span></label>
                                <div class="koreksi-reason-chips"><button type="button" data-alasan="Salah input nominal">Salah Input Nominal</button><button type="button" data-alasan="Transaksi ganda">Transaksi Ganda</button><button type="button" data-alasan="Uang rusak/tidak layak edar">Uang Rusak</button><button type="button" data-alasan="Temuan kas lebih">Temuan Kas Lebih</button></div>
                                <textarea name="alasan" id="alasanKoreksi" rows="3" class="form-control" required placeholder="Jelaskan alasan pengajuan koreksi...">{{ old('alasan') }}</textarea>
                            </div>
                        </form>
                    </div>
                    @if ($modal)
                        <div class="koreksi-form-footer"><span class="koreksi-footer-user">Petugas: <strong>{{ auth()->user()->nama_petugas ?? auth()->user()->name }}</strong></span><div class="koreksi-footer-actions"><button type="button" class="btn btn-outline-secondary" data-dismiss-parent-modal>Batal</button><button type="submit" form="formKoreksi" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i>Kirim Pengajuan <i class="fa-solid fa-arrow-right ms-1"></i></button></div></div>
                    @else
                        <div class="koreksi-form-footer"><span class="koreksi-footer-user">Petugas: <strong>{{ auth()->user()->nama_petugas ?? auth()->user()->name }}</strong></span><div class="koreksi-footer-actions"><a href="{{ route('teller.koreksi.index') }}" class="btn btn-outline-secondary">Batal</a><button type="submit" form="formKoreksi" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i>Kirim Pengajuan</button></div></div>
                    @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function pilihDarahKoreksi(opsi) {
            const selectJenis = document.getElementById('jenisKoreksi');
            if (!opsi || !opsi.dataset.jenis) return;
            const tersirat = opsi.dataset.jenis === 'setoran' ? 'koreksi_penarikan' : 'koreksi_setoran';
            selectJenis.value = tersirat;
            perbaruiJenisKoreksi();
        }

        async function muatTransaksiNasabah() {
            const nasabahId = document.getElementById('nasabahId');
            const transaksiId = document.getElementById('transaksiId');
            transaksiId.innerHTML = '<option value="">Memuat transaksi...</option>';

            if (!nasabahId.value) {
                transaksiId.innerHTML = '<option value="">-- Tanpa transaksi asli (uang rusak/palsu/temuan) --</option>';
                toggleSumberKoreksi();
                return;
            }

            try {
                const response = await fetch('{{ url('/teller/koreksi/transaksi') }}/' + nasabahId.value, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const payload = await response.json();
                transaksiId.innerHTML = '<option value="">-- Tanpa transaksi asli (uang rusak/palsu/temuan) --</option>';

                payload.data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.dataset.nominal = item.nominal;
                    option.dataset.jenis = item.jenis;
                    option.textContent = `${item.no_struk} · ${item.tanggal} · ${item.jenis_label} · Rp ${item.nominal.toLocaleString('id-ID')}`;
                    transaksiId.appendChild(option);
                });
            } catch (error) {
                transaksiId.innerHTML = '<option value="">Gagal memuat transaksi</option>';
            }

            toggleSumberKoreksi();
        }

        function toggleSumberKoreksi() {
            const selectTransaksi = document.getElementById('transaksiId');
            const tanpaAsli = selectTransaksi.value === '';
            const blokNasabah = document.getElementById('blokNasabahTemuan');
            const inputNominal = document.getElementById('nominalKoreksi');
            const wajibNominal = document.getElementById('wajibNominal');
            const hintNominal = document.getElementById('hintNominal');
            const hintJenis = document.getElementById('hintJenis');

            if (blokNasabah) blokNasabah.style.display = tanpaAsli ? 'block' : 'none';
            wajibNominal.style.display = tanpaAsli ? 'inline' : 'none';
            hintNominal.textContent = tanpaAsli
                ? 'Wajib diisi untuk koreksi tanpa transaksi asli (uang rusak/palsu/temuan).'
                : 'Kosongkan bila nominal yang dikoreksi sama dengan nominal transaksi asli. Dipilih otomatis sesuai transaksi.';
            hintJenis.textContent = tanpaAsli
                ? 'Pilih arah koreksi: uang masuk (Setoran Koreksi) atau uang keluar (Penarikan Koreksi).'
                : 'Saat memilih transaksi asli, arah koreksi diisi otomatis berlawanan (sebagai pembalik) dan tetap bisa Anda ubah.';

            const opsi = selectTransaksi.options[selectTransaksi.selectedIndex];
            if (!tanpaAsli && opsi) {
                pilihDarahKoreksi(opsi);
                if (!inputNominal.value) {
                    const nominal = parseInt(opsi.dataset.nominal || '0', 10);
                    if (nominal > 0) inputNominal.value = nominal;
                }
            }
        }

        function perbaruiJenisKoreksi() {
            const selected = document.getElementById('jenisKoreksi').value;
            document.querySelectorAll('.koreksi-type-option').forEach((button) => {
                const active = button.dataset.jenis === selected;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
                const icon = button.querySelector('.koreksi-type-check');
                icon.className = active ? 'fa-solid fa-circle-dot koreksi-type-check' : 'fa-regular fa-circle koreksi-type-check';
            });
        }

        function terbilangKoreksi(nilai) {
            const n = Math.floor(Math.abs(Number(nilai) || 0));
            const satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
            if (n < 12) return satuan[n] || 'nol';
            if (n < 20) return terbilangKoreksi(n - 10) + ' belas';
            if (n < 100) return terbilangKoreksi(Math.floor(n / 10)) + ' puluh ' + terbilangKoreksi(n % 10);
            if (n < 200) return 'seratus ' + terbilangKoreksi(n - 100);
            if (n < 1000) return terbilangKoreksi(Math.floor(n / 100)) + ' ratus ' + terbilangKoreksi(n % 100);
            if (n < 2000) return 'seribu ' + terbilangKoreksi(n - 1000);
            if (n < 1e6) return terbilangKoreksi(Math.floor(n / 1000)) + ' ribu ' + terbilangKoreksi(n % 1000);
            if (n < 1e9) return terbilangKoreksi(Math.floor(n / 1e6)) + ' juta ' + terbilangKoreksi(n % 1e6);
            return terbilangKoreksi(Math.floor(n / 1e9)) + ' miliar ' + terbilangKoreksi(n % 1e9);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('formKoreksi');
            const amountInput = document.getElementById('nominalKoreksi');
            const amountWords = document.getElementById('nominalTerbilang');
            const customerSelect = document.getElementById('nasabahId');
            const sourceSelect = document.getElementById('transaksiId');
            customerSelect.addEventListener('change', muatTransaksiNasabah);
            document.querySelectorAll('.koreksi-type-option').forEach((button) => {
                button.addEventListener('click', () => {
                    document.getElementById('jenisKoreksi').value = button.dataset.jenis;
                    perbaruiJenisKoreksi();
                });
            });
            sourceSelect.addEventListener('change', toggleSumberKoreksi);
            amountInput.addEventListener('input', () => {
                const value = Number(amountInput.value || 0);
                amountWords.textContent = value > 0 ? terbilangKoreksi(value) + ' rupiah' : '—';
            });
            document.querySelectorAll('[data-alasan]').forEach((button) => {
                button.addEventListener('click', () => {
                    const textarea = document.getElementById('alasanKoreksi');
                    textarea.value = textarea.value.trim() ? textarea.value.trim() + '; ' + button.dataset.alasan : button.dataset.alasan;
                    textarea.focus();
                });
            });
            document.querySelectorAll('[data-dismiss-parent-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    const parentModal = window.parent.document.getElementById('modalAjukanKoreksi');
                    window.parent.bootstrap.Modal.getOrCreateInstance(parentModal).hide();
                });
            });
            amountWords.textContent = Number(amountInput.value || 0) > 0 ? terbilangKoreksi(amountInput.value) + ' rupiah' : '—';
            perbaruiJenisKoreksi();
            muatTransaksiNasabah();
        });
    </script>
@endpush