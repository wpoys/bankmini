<?php

namespace App\Support;

/**
 * Penulis XLSX minimal tanpa dependensi eksternal.
 *
 * Berbeda dengan PDF (dompdf), proyek ini tidak bisa memakai paket Excel
 * (mis. maatwebsite/excel) karena ekstensi PHP `zip` tidak tersedia di
 * lingkungan ini. Kelas ini membangun berkas .xlsx secara langsung:
 * dokumen SpreadsheetML di-kompresi DEFLATE (zlib) dan dibungkus ke dalam
 * arsip ZIP standar secara manual (local header + central directory).
 *
 * Selain data, kelas ini juga menerapkan format tampilan (xl/styles.xml):
 * font tebal/warna, warna latar, border, perataan, format angka (Rupiah &
 * tanggal), lebar kolom, tinggi baris, penggabungan sel, serta pembekuan
 * header saat digulir.
 *
 * Bentuk data tetap kompatibel dengan pemakaian lama: `$rows` boleh berupa
 * array nilai biasa. Untuk format, satu baris boleh berupa deskripsi
 * `['cells' => [...], 'style' => [...], 'height' => 20, 'merge' => [0, 5]]`
 * dan satu sel boleh berupa `['v' => $nilai, 's' => [...]]`.
 */
class XlsxWriter
{
    /** Format tanggal Indonesia, mis. 01/09/2026. */
    public const NUMFMT_TANGGAL = 'dd/mm/yyyy';

    /** Format nominal Rupiah, mis. Rp 100.000. */
    public const NUMFMT_RUPIAH = '[$-id-ID]"Rp "#,##0';

    protected const PERATAAN_HORIZONTAL = ['left', 'center', 'right', 'fill', 'justify', 'centerContinuous', 'distributed'];

    protected const PERATAAN_VERTIKAL = ['top', 'center', 'bottom', 'justify', 'distributed'];

    protected array $sheetNames = [];

    protected array $sheets = [];

    protected array $types = [];

    /** id => xml */
    protected array $fonts = [];

    /** id => xml */
    protected array $fills = [];

    /** id => xml */
    protected array $borders = [];

    /** formatCode => id */
    protected array $numFmtLookup = [];

    /** id => formatCode */
    protected array $numFmts = [];

    protected int $nextNumFmtId = 164;

    /** cache key => id sel */
    protected array $styleIds = [];

    /** id => xml */
    protected array $styleXml = [];

    public function __construct(protected string $defaultSheet = 'Sheet1')
    {
        // Indeks 0 dan 1 wajib oleh spesifikasi OOXML.
        $this->fonts[0] = '<font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/><family val="2"/></font>';
        $this->fills[0] = '<fill><patternFill patternType="none"/></fill>';
        $this->fills[1] = '<fill><patternFill patternType="gray125"/></fill>';
        $this->borders[0] = '<border><left/><right/><top/><bottom/><diagonal/></border>';
        $this->styleXml[0] = '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>';
    }

    /**
     * Buat lembar kerja dari array. Tipe sel dideteksi otomatis:
     * integer/float => angka, lainnya => teks inline.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows  nilai biasa atau deskripsi berformat
     * @param  array<string, mixed>  $options  judul, periode, lebar kolom, tinggi baris, pembekuan, dll.
     */
    public function addSheet(array $headers, array $rows, ?string $name = null, array $options = []): self
    {
        $name = $name ?: $this->defaultSheet;
        $name = substr(preg_replace('/[\[\]:*?\/\\\\]/', '', (string) $name) ?: 'Sheet1', 0, 31);

        $sheetId = count($this->sheets) + 1;
        $this->sheetNames[] = $name;
        $this->sheets[] = [
            'headers' => array_values($headers),
            'rows' => array_values($rows),
            'options' => $options,
        ];
        $this->types[] = $sheetId;

        return $this;
    }

    /**
     * Bangun berkas XLSX sebagai string biner.
     */
    public function output(): string
    {
        $parts = [];

        $parts['[Content_Types].xml'] = $this->contentTypes();
        $parts['_rels/.rels'] = $this->rootRels();
        $parts['xl/workbook.xml'] = $this->workbook();
        $parts['xl/_rels/workbook.xml.rels'] = $this->workbookRels();

        // Lembar kerja digenerate lebih dulu: setiap sel mendaftarkan gaya
        // yang dipakainya, sehingga styles.xml baru bisa disusun setelahnya.
        foreach ($this->sheets as $i => $sheet) {
            $parts['xl/worksheets/sheet'.($i + 1).'.xml'] = $this->worksheet($sheet);
        }

        $parts['xl/styles.xml'] = $this->styles();

        return $this->zip($parts);
    }

    /**
     * Helper ringkas: bangun xlsx satu lembar.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, mixed>  $options
     */
    public static function fromArray(array $headers, array $rows, string $sheetName = 'Sheet1', array $options = []): string
    {
        return (new self)->addSheet($headers, $rows, $sheetName, $options)->output();
    }

    /**
     * Daftarkan gaya sel dan kembalikan indeksnya (dipakai lewat atribut `s`).
     *
     * Kunci yang dikenali: bold, italic, size, color, name, fill, border,
     * align, valign, wrap, indent, numFmt.
     *
     * @param  array<string, mixed>  $style
     */
    public function styleId(array $style = []): int
    {
        if ($style === []) {
            return 0;
        }

        $style = $this->normalizeStyle($style);
        $key = json_encode($style);

        if (isset($this->styleIds[$key])) {
            return $this->styleIds[$key];
        }

        $alignment = '';
        if ($style['align'] !== null || $style['valign'] !== null || $style['wrap'] || $style['indent'] > 0) {
            $attributes = '';
            if ($style['align'] !== null) {
                $attributes .= ' horizontal="'.$style['align'].'"';
            }
            if ($style['valign'] !== null) {
                $attributes .= ' vertical="'.$style['valign'].'"';
            }
            if ($style['wrap']) {
                $attributes .= ' wrapText="1"';
            }
            if ($style['indent'] > 0) {
                $attributes .= ' indent="'.$style['indent'].'"';
            }
            $alignment = '<alignment'.$attributes.'/>';
        }

        $xf = '<xf numFmtId="'.$this->numFmtId($style['numFmt'])
            .'" fontId="'.$this->fontId($style['font'])
            .'" fillId="'.$this->fillId($style['fill'])
            .'" borderId="'.$this->borderId($style['border'])
            .'" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"';

        $this->styleXml[] = $alignment === ''
            ? $xf.'/>'
            : $xf.'>'.$alignment.'</xf>';

        return $this->styleIds[$key] = count($this->styleXml) - 1;
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    protected function normalizeStyle(array $style): array
    {
        $align = $style['align'] ?? null;
        $valign = $style['valign'] ?? null;

        return [
            'font' => [
                'bold' => (bool) ($style['bold'] ?? false),
                'italic' => (bool) ($style['italic'] ?? false),
                'size' => (float) ($style['size'] ?? 11),
                'color' => strtoupper(ltrim((string) ($style['color'] ?? '000000'), '#')),
                'name' => (string) ($style['name'] ?? 'Calibri'),
            ],
            'fill' => isset($style['fill']) && $style['fill'] !== '' ? strtoupper(ltrim((string) $style['fill'], '#')) : null,
            'border' => $style['border'] ?? null,
            'align' => in_array($align, self::PERATAAN_HORIZONTAL, true) ? $align : null,
            'valign' => in_array($valign, self::PERATAAN_VERTIKAL, true) ? $valign : null,
            'wrap' => (bool) ($style['wrap'] ?? false),
            'indent' => max(0, (int) ($style['indent'] ?? 0)),
            'numFmt' => isset($style['numFmt']) && $style['numFmt'] !== '' ? (string) $style['numFmt'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $font
     */
    protected function fontId(array $font): int
    {
        $xml = '<font>';
        if ($font['bold']) {
            $xml .= '<b/>';
        }
        if ($font['italic']) {
            $xml .= '<i/>';
        }
        $xml .= '<sz val="'.(0 + $font['size']).'"/>'
            .'<color rgb="FF'.$this->xml($font['color']).'"/>'
            .'<name val="'.$this->xml($font['name']).'"/><family val="2"/></font>';

        foreach ($this->fonts as $id => $existing) {
            if ($existing === $xml) {
                return $id;
            }
        }

        $this->fonts[] = $xml;

        return count($this->fonts) - 1;
    }

    protected function fillId(?string $rgb): int
    {
        if ($rgb === null) {
            return 0;
        }

        $xml = '<fill><patternFill patternType="solid"><fgColor rgb="FF'.$this->xml($rgb).'"/><bgColor indexed="64"/></patternFill></fill>';

        foreach ($this->fills as $id => $existing) {
            if ($id > 1 && $existing === $xml) {
                return $id;
            }
        }

        $this->fills[] = $xml;

        return count($this->fills) - 1;
    }

    /**
     * @param  string|array<string, string>|null  $spec  'thin' atau ['all' => 'thin', 'top' => 'double', 'color' => 'BFBFBF']
     */
    protected function borderId($spec): int
    {
        if ($spec === null || $spec === false || $spec === 'none' || $spec === []) {
            return 0;
        }

        $spec = is_string($spec) ? ['all' => $spec] : $spec;
        $warna = strtoupper(ltrim((string) ($spec['color'] ?? 'B4C6E7'), '#'));

        $xml = '<border>';
        foreach (['left', 'right', 'top', 'bottom'] as $side) {
            $tebal = $spec[$side] ?? $spec['all'] ?? null;
            $xml .= $tebal
                ? '<'.$side.' style="'.$this->xml((string) $tebal).'"><color rgb="FF'.$this->xml($warna).'"/></'.$side.'>'
                : '<'.$side.'/>';
        }
        $xml .= '<diagonal/></border>';

        foreach ($this->borders as $id => $existing) {
            if ($existing === $xml) {
                return $id;
            }
        }

        $this->borders[] = $xml;

        return count($this->borders) - 1;
    }

    protected function numFmtId(?string $code): int
    {
        if ($code === null) {
            return 0;
        }

        return $this->numFmtLookup[$code] ??= $this->nextNumFmtId++;
    }

    protected function styles(): string
    {
        $numFmts = '';
        foreach ($this->numFmtLookup as $code => $id) {
            $numFmts .= '<numFmt numFmtId="'.$id.'" formatCode="'.$this->xml($code).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .($numFmts === '' ? '' : '<numFmts count="'.count($this->numFmtLookup).'">'.$numFmts.'</numFmts>')
            .'<fonts count="'.count($this->fonts).'">'.implode('', $this->fonts).'</fonts>'
            .'<fills count="'.count($this->fills).'">'.implode('', $this->fills).'</fills>'
            .'<borders count="'.count($this->borders).'">'.implode('', $this->borders).'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="'.count($this->styleXml).'">'.implode('', $this->styleXml).'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    /**
     * Ubah satu baris menjadi deskripsi seragam: cells, style, height, merge.
     *
     * @param  array<int|string, mixed>  $row
     * @return array{cells: array<int, mixed>, style: array<string, mixed>, height: float|null, merge: array<int, int|null>|null}
     */
    protected function normalizeRow(array $row): array
    {
        if (array_is_list($row)) {
            return ['cells' => array_values($row), 'style' => [], 'height' => null, 'merge' => null];
        }

        $merge = $row['merge'] ?? null;

        return [
            'cells' => array_values($row['cells'] ?? []),
            'style' => $row['style'] ?? [],
            'height' => isset($row['height']) ? (float) $row['height'] : null,
            'merge' => is_array($merge) ? [0 => (int) $merge[0], 1 => $merge[1] === null ? null : (int) $merge[1]] : null,
        ];
    }

    protected function worksheet(array $sheet): string
    {
        $options = $sheet['options'];
        $headers = $sheet['headers'];

        $judul = $options['title'] ?? null;
        $periode = $options['subtitle'] ?? null;

        $tajuk = [];
        if ($judul !== null && $judul !== '') {
            $tajuk[] = [
                'cells' => [['v' => $judul, 's' => $options['titleStyle'] ?? []]],
                'style' => $options['titleStyle'] ?? [],
                'height' => $options['titleHeight'] ?? 28,
                'merge' => [0, null],
            ];
        }
        if ($periode !== null && $periode !== '') {
            $tajuk[] = [
                'cells' => [['v' => $periode, 's' => $options['subtitleStyle'] ?? []]],
                'style' => $options['subtitleStyle'] ?? [],
                'height' => $options['subtitleHeight'] ?? 18,
                'merge' => [0, null],
            ];
        }

        $kepala = [
            'cells' => array_map(fn ($h) => ['v' => $h, 's' => $options['headerStyle'] ?? []], $headers),
            'style' => $options['headerStyle'] ?? [],
            'height' => $options['headerHeight'] ?? null,
            'merge' => null,
        ];

        $isi = array_map(fn ($row) => $this->normalizeRow($row), $sheet['rows']);
        $tata = array_merge($tajuk, [$kepala], $isi);

        $nKolom = count($headers);
        foreach ($tata as $baris) {
            $nKolom = max($nKolom, count($baris['cells']));
        }
        $nKolom = max(1, $nKolom);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        // Tanpa opsi tampilan, keluarkan XML persis seperti versi sebelumnya.
        $beku = (int) ($options['freezeRows'] ?? 0);
        $gridLines = $options['gridLines'] ?? true;
        $tinggiDefault = $options['defaultRowHeight'] ?? null;

        if ($beku > 0 || $gridLines === false || $tinggiDefault !== null) {
            $atas = 'A'.($beku + 1);
            $tampilan = '<sheetViews><sheetView workbookViewId="0"'.($gridLines === false ? ' showGridLines="0"' : '').'>';
            $tampilan .= $beku > 0
                ? '<pane ySplit="'.$beku.'" topLeftCell="'.$atas.'" activePane="bottomLeft" state="frozen"/>'
                    .'<selection pane="bottomLeft" activeCell="'.$atas.'" sqref="'.$atas.'"/>'
                : '';
            $tampilan .= '</sheetView></sheetViews>';

            $xml .= $tampilan;
        }

        if ($tinggiDefault !== null) {
            $xml .= '<sheetFormatPr defaultRowHeight="'.(0 + $tinggiDefault).'"/>';
        }

        $lebar = '';
        foreach ((array) ($options['columnWidths'] ?? []) as $i => $w) {
            $lebar .= '<col min="'.($i + 1).'" max="'.($i + 1).'" width="'.(0 + $w).'" customWidth="1"/>';
        }

        $xml .= ($lebar === '' ? '' : '<cols>'.$lebar.'</cols>').'<sheetData>';

        $gabung = [];

        foreach ($tata as $i => $baris) {
            $r = $i + 1;
            $atribut = '<row r="'.$r.'" spans="1:'.$nKolom.'" s="'.$this->styleId($baris['style']).'"';
            $atribut .= $baris['height'] === null ? '' : ' ht="'.$baris['height'].'" customHeight="1"';
            $atribut .= '>';

            $cells = '';
            foreach ($baris['cells'] as $kolom => $sel) {
                $gaya = $baris['style'];

                if (is_array($sel) && (array_key_exists('v', $sel) || array_key_exists('s', $sel))) {
                    $nilai = $sel['v'] ?? null;
                    $gaya = $sel['s'] ?? $gaya;
                } else {
                    $nilai = $sel;
                }

                $cells .= $this->cell($this->columnName($kolom).$r, $nilai, $this->styleId($gaya));
            }

            $xml .= $atribut.$cells.'</row>';

            if ($baris['merge'] !== null) {
                $sampai = $baris['merge'][1] ?? $nKolom - 1;
                $gabung[] = '<mergeCell ref="'.$this->columnName($baris['merge'][0]).$r.':'.$this->columnName(min($sampai, $nKolom - 1)).$r.'"/>';
            }
        }

        $xml .= '</sheetData>'
            .($gabung === [] ? '' : '<mergeCells count="'.count($gabung).'">'.implode('', $gabung).'</mergeCells>')
            .($options['pageMargins'] ?? false ? '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>' : '')
            .'</worksheet>';

        return $xml;
    }

    protected function cell(string $ref, mixed $nilai, int $style): string
    {
        if ($nilai === null || $nilai === '') {
            return '<c r="'.$ref.'" s="'.$style.'"/>';
        }

        if (is_int($nilai) || is_float($nilai) || (is_string($nilai) && is_numeric($nilai) && (string) $nilai === (string) ((float) $nilai))) {
            $v = is_numeric($nilai) && ! is_int($nilai) && ! is_float($nilai) ? (0 + $nilai) : $nilai;

            return '<c r="'.$ref.'" s="'.$style.'"><v>'.$v.'</v></c>';
        }

        return '<c r="'.$ref.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$this->xml((string) $nilai).'</t></is></c>';
    }

    protected function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .implode('', array_map(fn ($i) => '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>', $this->types))
            .'</Types>';
    }

    protected function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    protected function workbook(): string
    {
        $sheets = '';
        foreach ($this->sheetNames as $i => $nama) {
            $sheets .= '<sheet name="'.$this->xml($nama).'" sheetId="'.($i + 1).'" r:id="rId'.($i + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheets.'</sheets></workbook>';
    }

    protected function workbookRels(): string
    {
        $rels = '';
        foreach ($this->types as $i) {
            $rels .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }

        $rels .= '<Relationship Id="rId'.(count($this->types) + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$rels
            .'</Relationships>';
    }

    protected function columnName(int $index): string
    {
        $name = '';
        $index++;

        while ($index > 0) {
            $rem = ($index - 1) % 26;
            $name = chr(65 + $rem).$name;
            $index = intdiv($index - 1, 26);
        }

        return $name;
    }

    protected function xml(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Bangun arsip ZIP tanpa ekstensi `zip`, memakai deflate (zlib).
     *
     * @param  array<string, string>  $files  path di dalam arsip => isi
     */
    protected function zip(array $files): string
    {
        $local = '';
        $central = '';
        $offset = 0;

        foreach ($files as $nama => $isi) {
            $crc = crc32($isi);
            $uncompressed = strlen($isi);
            $deflated = gzdeflate($isi, 6) ?: $isi;
            $compressed = strlen($deflated);
            $namaPanjang = strlen($nama);

            $localHeader = pack('VvvvvvVVVvv', 0x04034B50, 20, 0, 8, 0, 0, $crc, $compressed, $uncompressed, $namaPanjang, 0)
                .$nama
                .$deflated;

            $centralHeader = pack('VvvvvvvVVVvvvvvVV', 0x02014B50, 20, 20, 0, 8, 0, 0, $crc, $compressed, $uncompressed, $namaPanjang, 0, 0, 0, 0, 0, $offset)
                .$nama;

            $local .= $localHeader;
            $central .= $centralHeader;
            $offset += strlen($localHeader);
        }

        $centralSize = strlen($central);
        $eocd = pack('VvvvvVVv', 0x06054B50, 0, 0, count($files), count($files), $centralSize, $offset, 0);

        return $local.$central.$eocd;
    }
}
