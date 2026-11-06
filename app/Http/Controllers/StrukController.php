<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Support\ApiFormatter;
use App\Support\InfoBank;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

/**
 * Struk transaksi dinamis: dibangun dari data transaksi di database,
 * tanpa menyimpan file/gambar struk.
 *
 * Dipakai oleh teller, supervisor, dan admin. Otorisasi
 * dicek lewat App\Policies\TransaksiPolicy (bukan hanya lewat ID URL).
 */
class StrukController extends Controller
{
    /**
     * Ukuran kertas struk (mm) untuk PDF — mengikuti struk thermal 58/80mm.
     */
    public const LEBAR_KERTAS_MM = 80;

    public const TINGGI_KERTAS_MM = 200;

    /**
     * Halaman struk di layar (formal + tombol aksi).
     */
    public function show(Request $request, Transaksi $transaksi): View
    {
        Gate::authorize('viewStruk', $transaksi);
        $data = $this->dataStruk($transaksi);

        if ($request->boolean('modal')) {
            return view('transaksi.struk-modal', $data);
        }

        return view('transaksi.struk', $data);
    }

    /**
     * Blade khusus cetak (tanpa layout aplikasi).
     */
    public function cetak(Transaksi $transaksi): View
    {
        Gate::authorize('cetakStruk', $transaksi);

        return view('transaksi.struk-print', $this->dataStruk($transaksi));
    }

    /**
     * PDF struk dibuat on-the-fly (tidak ada file permanen di server).
     */
    public function pdf(Transaksi $transaksi): Response
    {
        Gate::authorize('unduhStruk', $transaksi);

        $data = $this->dataStruk($transaksi, untukPdf: true);

        $pdf = Pdf::loadView('transaksi.struk-pdf', $data);
        $pdf->setPaper([0, 0, $this->mm(self::LEBAR_KERTAS_MM), $this->mm(self::TINGGI_KERTAS_MM)], 'portrait');
        $pdf->setOptions([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'defaultFont' => 'DejaVu Sans Mono',
        ]);

        return $pdf->download('struk-'.$transaksi->no_struk.'.pdf');
    }

    /**
     * Data siap pakai untuk ketiga tampilan struk.
     *
     * @return array<string, mixed>
     */
    protected function dataStruk(Transaksi $transaksi, bool $untukPdf = false): array
    {
        $transaksi->loadMissing(['nasabah', 'user']);
        $nasabah = $transaksi->nasabah;

        $saldoSebelum = $transaksi->saldo_sebelum;
        $saldoSesudah = $transaksi->saldo_sesudah;

        return [
            'transaksi' => $transaksi,
            'nasabah' => $nasabah,
            'teller' => $transaksi->user?->nama_petugas ?? '-',
            'nominal' => (int) $transaksi->nominal,
            'saldoSebelum' => $saldoSebelum,
            'saldoSesudah' => $saldoSesudah,
            'saldoSekarang' => (int) ($nasabah?->saldo ?? $saldoSesudah),
            'catatan' => trim((string) $transaksi->catatan) !== '' ? $transaksi->catatan : null,
            'qrPayload' => $this->payloadQr($transaksi),
            'qrImg' => $untukPdf ? self::qrDataUri($this->payloadQr($transaksi)) : null,
            'qrSvg' => $untukPdf ? null : $this->qrSvg($this->payloadQr($transaksi)),
            'logo' => $untukPdf ? InfoBank::logoDataUri() : InfoBank::logoUrl(),
            'kembali' => $this->urlKembali(),
            'bank' => [
                'nama' => InfoBank::NAMA,
                'jenis' => InfoBank::JENIS,
                'tagline' => InfoBank::TAGLINE,
                'alamat' => InfoBank::ALAMAT,
                'lembaga' => InfoBank::LEMBAGA,
                'keterangan' => InfoBank::KETERANGAN,
            ],
            'dicetakOleh' => auth()->user()?->nama_petugas ?? '-',
            'roleDicetak' => auth()->user()?->role_label ?? '-',
            'strukBaru' => (int) session('struk_baru') === (int) $transaksi->id,
        ];
    }

    /**
     * Isi QR Code: ringkas dan bisa diverifikasi teller/supervisor.
     */
    protected function payloadQr(Transaksi $transaksi): string
    {
        return ApiFormatter::qrContent($transaksi);
    }

    /**
     * Milimeter ke point (1 mm = 2,8346 pt) untuk ukuran kertas DomPDF.
     */
    protected function mm(float $milimeter): float
    {
        return round($milimeter * 2.834645669, 2);
    }

    /**
     * Halaman asal pengguna sesuai role (tombol kembali pada struk).
     */
    protected function urlKembali(): string
    {
        return match (auth()->user()?->role) {
            'teller' => route('teller.transaksi.index'),
            'supervisor' => route('supervisor.dashboard'),
            'admin' => route('admin.dashboard'),
            default => url()->previous(),
        };
    }

    /**
     * QR Code sebagai SVG inline (dipakai tampilan layar & cetak).
     */
    protected function qrSvg(string $payload): ?string
    {
        try {
            return QrCode::size(120)->margin(1)->generate($payload);
        } catch (Throwable $e) {
            Log::warning('Gagal membuat QR Code SVG untuk struk: '.$e->getMessage());

            return null;
        }
    }

    /**
     * QR Code sebagai data URI, aman untuk DomPDF (fallback bila renderer PNG gagal).
     */
    public static function qrDataUri(string $payload, int $ukuran = 150): ?string
    {
        try {
            return QrCode::format('png')
                ->size($ukuran)
                ->margin(1)
                ->generateDataUri($payload);
        } catch (Throwable $e) {
            Log::warning('Gagal membuat QR Code PNG untuk struk: '.$e->getMessage());

            try {
                $svg = QrCode::size($ukuran)->margin(1)->generate($payload);

                return 'data:image/svg+xml;base64,'.base64_encode($svg);
            } catch (Throwable $fallback) {
                return null;
            }
        }
    }
}
