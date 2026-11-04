@extends('layouts.app')

@section('title', 'Struk Transaksi ' . $transaksi->no_struk)
@section('page-title', 'Struk Transaksi')
@section('page-subtitle', 'Bukti transaksi ' . $transaksi->no_struk)

@section('content')
    <div class="page-animate struk-page">
        @if ($strukBaru)
            <div class="alert alert-success small d-flex flex-wrap align-items-center gap-2 no-print">
                <i class="fa-solid fa-circle-check"></i>
                <div class="fw-semibold">Transaksi berhasil diproses.</div>
                <div class="ms-auto d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalStruk">
                        <i class="fa-solid fa-receipt me-1"></i>Lihat Struk
                    </button>
                    <a href="{{ $transaksi->struk_cetak_url }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm">
                        <i class="fa-solid fa-print me-1"></i>Cetak Struk
                    </a>
                    <a href="{{ $transaksi->struk_pdf_url }}" class="btn btn-outline-success btn-sm">
                        <i class="fa-solid fa-file-pdf me-1"></i>Download PDF
                    </a>
                </div>
            </div>
        @endif

        <div class="d-flex flex-wrap gap-2 mb-3 no-print">
            <a href="{{ $kembali }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i>Kembali
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalStruk">
                <i class="fa-solid fa-receipt me-1"></i>Lihat Struk
            </button>
            @if ($transaksi->boleh_cetak_struk)
                <a href="{{ $transaksi->struk_cetak_url }}" target="_blank" rel="noopener" class="btn btn-outline-primary">
                    <i class="fa-solid fa-print me-1"></i>Cetak Struk
                </a>
            @endif
            @if ($transaksi->boleh_unduh_struk)
                <a href="{{ $transaksi->struk_pdf_url }}" class="btn btn-outline-primary">
                    <i class="fa-solid fa-file-pdf me-1"></i>Download PDF
                </a>
            @endif
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="data-panel struk-dokumen">
                    <div class="struk-dokumen-kop">
                        <div class="d-flex align-items-center gap-3">
                            @if ($logo)
                                <img src="{{ $logo }}" alt="Logo {{ $bank['nama'] }}" class="struk-logo">
                            @else
                                <div class="struk-logo-teks">BM</div>
                            @endif
                            <div>
                                <div class="struk-bank">{{ $bank['nama'] }}</div>
                                <div class="struk-bank-sub">{{ $bank['lembaga'] }} &middot; {{ $bank['tagline'] }}</div>
                                <div class="struk-bank-alamat">{{ $bank['alamat'] }}</div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="struk-dokumen-judul">BUKTI TRANSAKSI</div>
                            <div class="struk-dokumen-no">No. {{ $transaksi->no_struk ?: '-' }}</div>
                        </div>
                    </div>

                    <div class="struk-grid">
                        <div class="struk-kolom">
                            <div class="struk-seksi">Data Nasabah</div>
                            <table class="struk-tabel">
                                <tr>
                                    <td>Nama Nasabah</td>
                                    <td class="fw-semibold">{{ $nasabah?->nama_siswa ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Nomor Rekening</td>
                                    <td class="fw-semibold">{{ $nasabah?->no_rekening ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>NIS</td>
                                    <td>{{ $nasabah?->nis ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Kelas</td>
                                    <td>{{ $nasabah?->kelas ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td>Produk</td>
                                    <td>{{ $nasabah?->produk_label ?? '-' }}</td>
                                </tr>
                            </table>

                            <div class="struk-seksi">Petugas</div>
                            <table class="struk-tabel">
                                <tr>
                                    <td>Teller</td>
                                    <td>{{ $teller }}</td>
                                </tr>
                                <tr>
                                    <td>Waktu Transaksi</td>
                                    <td>{{ $transaksi->tanggal_transaksi }} &middot; {{ $transaksi->jam_transaksi }} WIB</td>
                                </tr>
                            </table>
                        </div>

                        <div class="struk-kolom">
                            <div class="struk-seksi">Detail Transaksi</div>
                            <table class="struk-tabel">
                                <tr>
                                    <td>Jenis Transaksi</td>
                                    <td class="fw-semibold">{{ $transaksi->jenis_label }}</td>
                                </tr>
                                <tr>
                                    <td>Nominal</td>
                                    <td class="fw-bold">{{ $transaksi->nominal_rupiah }}</td>
                                </tr>
                                <tr>
                                    <td>Saldo Sebelum</td>
                                    <td>Rp {{ number_format($saldoSebelum, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Saldo Sesudah</td>
                                    <td class="fw-bold">Rp {{ number_format($saldoSesudah, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Status</td>
                                    <td>
                                        <span class="status-badge {{ $transaksi->status === 'approved' ? 'green' : 'orange' }}">
                                            {{ $transaksi->status_label }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Catatan</td>
                                    <td>{{ $catatan ?: '-' }}</td>
                                </tr>
                            </table>

                            <div class="struk-qr-kartu text-center">
                                @if (! empty($qrSvg))
                                    <div class="struk-qr">{!! $qrSvg !!}</div>
                                @endif
                                <div class="small text-muted mt-1">Pindai untuk verifikasi transaksi</div>
                            </div>
                        </div>
                    </div>

                    <div class="struk-dokumen-kaki">
                        <div>
                            <div class="struk-kaki-teks">{{ $bank['keterangan'] }}</div>
                            <div class="struk-kaki-teks">Simpan struk ini sebagai bukti transaksi yang sah dan tidak dapat diubah.</div>
                            <div class="struk-kaki-teks">
                                Dicetak oleh {{ $dicetakOleh }} ({{ $roleDicetak }})
                                &middot; {{ \Carbon\Carbon::now()->translatedFormat('d/m/Y H:i') }}
                            </div>
                        </div>
                        <div class="struk-tanda">
                            <div class="struk-tanda-nama">{{ $teller }}</div>
                            <div class="struk-tanda-jab">Teller</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="data-panel no-print">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot"></span>Informasi Struk</div>
                    </div>
                    <div class="panel-body p-3">
                        <div class="small text-muted mb-2">
                            Struk dibuat langsung dari data transaksi, tidak disimpan sebagai gambar.
                        </div>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalStruk">
                                <i class="fa-solid fa-receipt me-1"></i>Lihat Struk
                            </button>
                            <a href="{{ $transaksi->struk_cetak_url }}" target="_blank" rel="noopener" class="btn btn-outline-primary">
                                <i class="fa-solid fa-print me-1"></i>Cetak Struk
                            </a>
                            <a href="{{ $transaksi->struk_pdf_url }}" class="btn btn-outline-primary">
                                <i class="fa-solid fa-file-pdf me-1"></i>Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalStruk" tabindex="-1" aria-labelledby="modalStrukLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="modalStrukLabel">
                        <i class="fa-solid fa-receipt me-2 text-primary"></i>Struk {{ $transaksi->no_struk }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="print-area struk-58 p-3 border rounded bg-white">
                        @include('transaksi.partials.struk-css', ['ukuran' => '11px'])
                        @include('transaksi.partials.struk')
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <a href="{{ $transaksi->struk_cetak_url }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-print me-1"></i>Cetak Struk
                    </a>
                    <a href="{{ $transaksi->struk_pdf_url }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-file-pdf me-1"></i>Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
