@extends('layouts.app')

@section('title', 'Ajukan Koreksi')
@section('page-title', 'Ajukan Koreksi Transaksi')
@section('page-subtitle', 'Koreksi hanya berlaku untuk transaksi yang ANDA proses sendiri')
@if ($modal)
    @section('body-class', 'modal-form role-teller')
    @push('styles')
        <style>
            .modal-form .sidebar, .modal-form .topbar, .modal-form .page-header, .modal-form .alert.alert-info { display: none !important; }
            .modal-form .main-content { margin-left: 0 !important; padding: 0 !important; }
            .modal-form .page-animate { padding: 0 !important; }
            .modal-form .row.justify-content-center { display: block; }
            .modal-form .col-lg-8 { max-width: none; width: 100%; }
            .modal-form .data-panel { border: 0; box-shadow: none; }
        </style>
    @endpush
@endif
@if ($modal)
    @section('standalone-layout', true)
@endif

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Form Pengajuan Koreksi</h1>
                <div class="page-header-sub">Koreksi transaksi yang keliru di loket Anda</div>
            </div>
            <div class="header-actions">
                <a href="{{ route('teller.koreksi.index') }}" class="quick-action">
                    <i class="fa-solid fa-arrow-left"></i><span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="data-panel">
                    <div class="panel-body p-3">
                        <div class="alert alert-info small">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            Saat disetujui Supervisor, sistem akan membuat <strong>transaksi pembalik</strong> (jurnal
                            koreksi) dan menandai transaksi asli sebagai dikoreksi — atau pencatatan baru bila
                            <strong>tanpa transaksi asli</strong> (uang rusak/palsu/temuan). Data asli tidak pernah
                            dihapus/diubah.
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger small">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('teller.koreksi.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nasabah <span class="text-danger">*</span></label>
                                <select name="nasabah_id" id="nasabahId" class="form-select" required>
                                    <option value="">-- Pilih nasabah --</option>
                                    @foreach ($nasabahList as $n)
                                        <option value="{{ $n->id }}" @selected(old('nasabah_id') == $n->id)>
                                            {{ $n->no_rekening }} · {{ $n->nama_siswa }} @if ($n->kelas) ({{ $n->kelas }}) @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Transaksi yang Dikoreksi</label>
                                <select name="transaksi_id" id="transaksiId" class="form-select" onchange="toggleSumberKoreksi()">
                                    <option value="">-- Tanpa transaksi asli (uang rusak/palsu/temuan) --</option>
                                </select>
                                <div class="form-text">
                                    <i class="fa-solid fa-circle-info text-primary me-1"></i>Pilih nasabah untuk menampilkan transaksi yang dapat dikoreksi.
                                    Kosongkan bila transaksi asli tidak ada.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Jenis Koreksi <span class="text-danger">*</span></label>
                                <select name="jenis" id="jenisKoreksi" class="form-select" required>
                                    <option value="">-- Pilih arah koreksi --</option>
                                    <option value="koreksi_setoran" @selected(old('jenis') === 'koreksi_setoran')>Setoran Koreksi — uang masuk (saldo bertambah)</option>
                                    <option value="koreksi_penarikan" @selected(old('jenis') === 'koreksi_penarikan')>Penarikan Koreksi — uang keluar (saldo berkurang)</option>
                                </select>
                                <div class="form-text">
                                    <i class="fa-solid fa-circle-info text-primary me-1"></i>
                                    <span id="hintJenis">Saat memilih transaksi asli, arah koreksi diisi otomatis berlawanan
                                    (sebagai pembalik) dan tetap bisa Anda ubah.</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nominal Koreksi (Rp) <span class="text-danger" id="wajibNominal" style="display:none;">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="nominal" id="nominalKoreksi" class="form-control" min="1"
                                        value="{{ old('nominal') }}">
                                </div>
                                <div class="form-text">
                                    <i class="fa-solid fa-circle-info text-primary me-1"></i><span id="hintNominal">Kosongkan
                                    bila nominal yang dikoreksi sama dengan nominal transaksi asli. Dipilih otomatis sesuai
                                    transaksi.</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Alasan Koreksi <span class="text-danger">*</span></label>
                                <textarea name="alasan" rows="3" class="form-control" required placeholder="Contoh: nominal salah input, transaksi ganda, uang rusak/tidak layak edar, selisih temuan loket.">{{ old('alasan') }}</textarea>
                            </div>
                            <button class="btn btn-primary w-100 py-2 fw-semibold">
                                <i class="fa-solid fa-paper-plane me-1"></i>Kirim Pengajuan
                            </button>
                        </form>
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
            const sudahAda = [...selectJenis.options].some(o => o.value === tersirat && o.selected);
            if (selectJenis.value === '' || !selectJenis.value || !sudahAda) {
                selectJenis.value = tersirat;
            }
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

            blokNasabah.style.display = tanpaAsli ? 'block' : 'none';
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

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('nasabahId').addEventListener('change', muatTransaksiNasabah);
            muatTransaksiNasabah();
        });
    </script>
@endpush