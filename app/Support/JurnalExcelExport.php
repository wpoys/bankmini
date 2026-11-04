<?php

namespace App\Support;

use App\Models\JurnalAkuntansi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Format export Excel untuk halaman Buku Jurnal.
 *
 * Kelas ini hanya mengatur tampilan (judul, periode, gaya header, lebar
 * kolom, format tanggal & Rupiah, border, total, status balance). Data,
 * filter, dan logika jurnal tetap berasal dari controller.
 */
class JurnalExcelExport
{
    public const JUDUL = 'BUKU JURNAL BANK MINI';

    public const HEADERS = ['Tanggal', 'No. Struk', 'Nasabah', 'Produk', 'Kode Akun', 'Nama Akun', 'Posisi', 'Jumlah (Rp)'];

    /** Lebar kolom sesuai isi, agar teks tidak terpotong. */
    public const LEBAR = [14, 24, 26, 22, 16, 18, 12, 20];

    public const WARNA_JUDUL = '1F4E79';

    public const WARNA_HEADER = '1F4E79';

    public const WARNA_TOTAL = 'DCE6F1';

    public const WARNA_BORDER = '8EA9DB';

    public const WARNA_BALANCE = '1E7B34';

    public const WARNA_TIDAK_BALANCE = 'B03A2E';

    /**
     * Kirim Buku Jurnal sesuai filter yang sedang dipakai halaman.
     *
     * @param  Collection<int, JurnalAkuntansi>  $jurnal
     */
    public static function download(Collection $jurnal, Carbon $mulai, Carbon $sampai, ?string $kodeAkun = null, ?string $teller = null): Response
    {
        $totalDebit = (int) $jurnal->where('posisi', 'debit')->sum('jumlah');
        $totalKredit = (int) $jurnal->where('posisi', 'kredit')->sum('jumlah');

        $rows = self::barisData($jurnal);
        $rows[] = self::barisTotal('TOTAL DEBIT', $totalDebit);
        $rows[] = self::barisTotal('TOTAL KREDIT', $totalKredit);
        $rows[] = self::barisStatus($totalDebit === $totalKredit);

        return ExcelExport::download(
            'jurnal-akuntansi-'.$mulai->format('Y-m-d').'-'.$sampai->format('Y-m-d'),
            self::HEADERS,
            $rows,
            'Jurnal',
            self::opsi($mulai, $sampai, $kodeAkun, $teller)
        );
    }

    /**
     * Pilihan format untuk XlsxWriter: judul, periode, lebar kolom, gaya
     * header, tinggi baris, dan pembekuan header.
     *
     * @return array<string, mixed>
     */
    protected static function opsi(Carbon $mulai, Carbon $sampai, ?string $kodeAkun = null, ?string $teller = null): array
    {
        $periode = 'Periode: '.$mulai->format('d/m/Y').' s.d. '.$sampai->format('d/m/Y');

        if ($kodeAkun !== null && $kodeAkun !== '') {
            $periode .= '  |  Kode Akun: '.$kodeAkun.' ('.KodeAkun::label($kodeAkun).')';
        }

        if ($teller !== null && $teller !== '') {
            $periode .= '  |  Teller: '.$teller;
        }

        return [
            'title' => self::JUDUL,
            'subtitle' => $periode,
            'titleStyle' => [
                'bold' => true,
                'size' => 16,
                'color' => self::WARNA_JUDUL,
                'align' => 'center',
                'valign' => 'center',
            ],
            'subtitleStyle' => [
                'italic' => true,
                'size' => 11,
                'color' => '404040',
                'align' => 'center',
                'valign' => 'center',
            ],
            'headerStyle' => self::gayaHeader(),
            'headerHeight' => 24,
            'titleHeight' => 30,
            'subtitleHeight' => 18,
            'columnWidths' => self::LEBAR,
            'freezeRows' => 3,
            'gridLines' => false,
            'defaultRowHeight' => 18,
            'pageMargins' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function gayaHeader(): array
    {
        return [
            'bold' => true,
            'size' => 11,
            'color' => 'FFFFFF',
            'fill' => self::WARNA_HEADER,
            'align' => 'center',
            'valign' => 'center',
            'wrap' => true,
            'border' => ['all' => 'thin', 'color' => self::WARNA_HEADER],
        ];
    }

    /**
     * Gaya sel isi tabel per kolom. Tanggal & posisi rata tengah,
     * nominal rata kanan, sisanya rata kiri.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function gayaKolom(): array
    {
        $garis = ['all' => 'thin', 'color' => self::WARNA_BORDER];

        return [
            ['valign' => 'center', 'align' => 'center', 'border' => $garis, 'numFmt' => XlsxWriter::NUMFMT_TANGGAL],
            ['valign' => 'center', 'align' => 'left', 'border' => $garis],
            ['valign' => 'center', 'align' => 'left', 'border' => $garis],
            ['valign' => 'center', 'align' => 'left', 'border' => $garis],
            ['valign' => 'center', 'align' => 'center', 'border' => $garis],
            ['valign' => 'center', 'align' => 'left', 'border' => $garis],
            ['valign' => 'center', 'align' => 'center', 'border' => $garis],
            ['valign' => 'center', 'align' => 'right', 'border' => $garis, 'numFmt' => XlsxWriter::NUMFMT_RUPIAH],
        ];
    }

    /**
     * @param  Collection<int, JurnalAkuntansi>  $jurnal
     * @return array<int, array{cells: array<int, array<string, mixed>>, style: array<string, mixed>, height: float|null, merge: array<int, int|null>|null}>
     */
    protected static function barisData(Collection $jurnal): array
    {
        $gaya = self::gayaKolom();
        $baris = [];

        foreach ($jurnal as $j) {
            $sel = [
                ['v' => self::serialTanggal(self::tanggalJurnal($j)), 's' => $gaya[0]],
                ['v' => $j->transaksi?->no_struk ?? '-', 's' => $gaya[1]],
                ['v' => $j->transaksi?->nasabah?->nama_siswa ?? '-', 's' => $gaya[2]],
                ['v' => $j->transaksi?->nasabah?->produk_label ?? '-', 's' => $gaya[3]],
                ['v' => (string) $j->kode_akun, 's' => $gaya[4]],
                ['v' => KodeAkun::label($j->kode_akun), 's' => $gaya[5]],
                ['v' => ucfirst((string) $j->posisi), 's' => $gaya[6]],
                ['v' => (int) $j->jumlah, 's' => $gaya[7]],
            ];

            $baris[] = ['cells' => $sel, 'style' => [], 'height' => 18, 'merge' => null];
        }

        return $baris;
    }

    /**
     * Baris total: label digabung di kiri, nominal rata kanan.
     *
     * @return array{cells: array<int, array<string, mixed>>, style: array<string, mixed>, height: float|null, merge: array<int, int|null>|null}
     */
    protected static function barisTotal(string $label, int $nominal): array
    {
        $dasar = [
            'bold' => true,
            'size' => 11,
            'fill' => self::WARNA_TOTAL,
            'valign' => 'center',
            'border' => ['all' => 'thin', 'color' => self::WARNA_BORDER],
        ];

        $sel = [];
        for ($i = 0; $i < count(self::HEADERS) - 1; $i++) {
            $sel[] = ['v' => '', 's' => $dasar];
        }

        // Sel gabungan hanya menampilkan isi sel paling kiri.
        $sel[0] = ['v' => $label, 's' => [...$dasar, 'align' => 'right']];
        $sel[] = ['v' => $nominal, 's' => [...$dasar, 'align' => 'right', 'numFmt' => XlsxWriter::NUMFMT_RUPIAH]];

        return ['cells' => $sel, 'style' => $dasar, 'height' => 20, 'merge' => [0, count(self::HEADERS) - 2]];
    }

    /**
     * Baris status pembuka buku besar: BALANCE bila total debit = kredit.
     *
     * @return array{cells: array<int, array<string, mixed>>, style: array<string, mixed>, height: float|null, merge: array<int, int|null>|null}
     */
    protected static function barisStatus(bool $balance): array
    {
        $warna = $balance ? self::WARNA_BALANCE : self::WARNA_TIDAK_BALANCE;

        $gaya = [
            'bold' => true,
            'size' => 11,
            'color' => 'FFFFFF',
            'fill' => $warna,
            'align' => 'center',
            'valign' => 'center',
            'border' => ['all' => 'thin', 'color' => $warna],
        ];

        $sel = [];
        for ($i = 0; $i < count(self::HEADERS) - 1; $i++) {
            $sel[] = ['v' => '', 's' => $gaya];
        }

        $sel[0] = ['v' => $balance ? 'BALANCE' : 'TIDAK BALANCE', 's' => $gaya];
        $sel[] = ['v' => '', 's' => $gaya];

        return ['cells' => $sel, 'style' => $gaya, 'height' => 22, 'merge' => [0, count(self::HEADERS) - 1]];
    }

    /**
     * Tanggal transaksi, sama dengan yang dipakai tabel di halaman.
     */
    protected static function tanggalJurnal(JurnalAkuntansi $j): ?Carbon
    {
        $tanggal = $j->transaksi?->tanggal ?? $j->created_at;

        return $tanggal instanceof Carbon ? $tanggal : null;
    }

    /**
     * Ubah tanggal menjadi nomor seri Excel agar tampil 01/09/2026.
     */
    protected static function serialTanggal(?Carbon $tanggal): int
    {
        if ($tanggal === null) {
            return 0;
        }

        return (int) round(
            (strtotime($tanggal->format('Y-m-d').' 00:00:00') - strtotime('1899-12-30 00:00:00')) / 86400
        );
    }
}
