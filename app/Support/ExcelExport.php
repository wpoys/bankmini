<?php

namespace App\Support;

class ExcelExport
{
    public const MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * Kirim data laporan sebagai berkas .xlsx (stream).
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, mixed>  $options  pilihan format (judul, lebar kolom, gaya header, dll.)
     */
    public static function download(string $filename, array $headers, array $rows, ?string $sheetName = null, array $options = [])
    {
        $filename = str_replace(['/', '\\', "\0"], '-', $filename);
        $filename .= str_ends_with(strtolower($filename), '.xlsx') ? '' : '.xlsx';

        return response(XlsxWriter::fromArray($headers, $rows, $sheetName ?? 'Laporan', $options), 200, [
            'Content-Type' => self::MIME,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Kirim laporan sebagai berkas .xlsx dengan beberapa lembar kerja.
     *
     * @param  array<int, array{name?: string, headers: array<int, string>, rows: array<int, array<int, mixed>>, options?: array<string, mixed>}>  $sheets
     */
    public static function downloadSheets(string $filename, array $sheets)
    {
        $filename = str_replace(['/', '\\', "\0"], '-', $filename);
        $filename .= str_ends_with(strtolower($filename), '.xlsx') ? '' : '.xlsx';

        $writer = new XlsxWriter($sheets[0]['name'] ?? 'Laporan');

        foreach ($sheets as $sheet) {
            $writer->addSheet(
                $sheet['headers'] ?? [],
                $sheet['rows'] ?? [],
                $sheet['name'] ?? null,
                $sheet['options'] ?? []
            );
        }

        return response($writer->output(), 200, [
            'Content-Type' => self::MIME,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
        ]);
    }
}
