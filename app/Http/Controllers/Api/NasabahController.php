<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nasabah;
use App\Models\PermintaanResetPin;
use App\Models\Transaksi;
use App\Support\ApiFormatter;
use App\Support\PermintaanPinService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NasabahController extends Controller
{
    private const PER_HALAMAN = 15;

    private const TERBARU = 5;

    public function dashboard(Request $request): JsonResponse
    {
        $nasabah = $this->nasabah($request);

        if (! $nasabah) {
            return $this->gagal('Data nasabah tidak ditemukan.', 404);
        }

        $totalTransaksi = $nasabah->transaksi()->count();
        $totalSetoran = (int) $nasabah->transaksi()->where('jenis_trans', Transaksi::JENIS_SETORAN)->sum('nominal');
        $totalPenarikan = (int) $nasabah->transaksi()->where('jenis_trans', Transaksi::JENIS_PENARIKAN)->sum('nominal');
        $pending = $nasabah->transaksi()->where('status', Transaksi::STATUS_PENDING)->count();

        $terbaru = $nasabah->transaksi()
            ->with('user')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->limit(self::TERBARU)
            ->get()
            ->map(fn (Transaksi $t) => ApiFormatter::transaksi($t))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'nasabah' => ApiFormatter::nasabah($nasabah),
                'ringkasan' => [
                    'total_transaksi' => $totalTransaksi,
                    'total_setoran' => $totalSetoran,
                    'total_setoran_rupiah' => $this->rupiah($totalSetoran),
                    'total_penarikan' => $totalPenarikan,
                    'total_penarikan_rupiah' => $this->rupiah($totalPenarikan),
                    'transaksi_pending' => $pending,
                ],
                'transaksi_terbaru' => $terbaru,
                'notifikasi_belum_dibaca' => $request->user()->userNotifications()->belumDibaca()->count(),
            ],
        ]);
    }

    public function saldo(Request $request): JsonResponse
    {
        $nasabah = $this->nasabah($request);

        if (! $nasabah) {
            return $this->gagal('Data nasabah tidak ditemukan.', 404);
        }

        return response()->json([
            'success' => true,
            'data' => ApiFormatter::nasabah($nasabah),
        ]);
    }

    public function profil(Request $request): JsonResponse
    {
        $nasabah = $this->nasabah($request);

        if (! $nasabah) {
            return $this->gagal('Data nasabah tidak ditemukan.', 404);
        }

        return response()->json([
            'success' => true,
            'data' => array_merge(ApiFormatter::nasabah($nasabah), [
                'qr_code' => $nasabah->qr_code ?: $nasabah->no_rekening,
                'user' => ApiFormatter::user($request->user()),
            ]),
        ]);
    }

    public function transaksi(Request $request): JsonResponse
    {
        $nasabah = $this->nasabah($request);

        if (! $nasabah) {
            return $this->gagal('Data nasabah tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'jenis' => ['nullable', 'in:'.implode(',', [
                Transaksi::JENIS_SETORAN,
                Transaksi::JENIS_PENARIKAN,
                Transaksi::JENIS_KOREKSI_SETORAN,
                Transaksi::JENIS_KOREKSI_PENARIKAN,
            ])],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'q' => ['nullable', 'string', 'max:60'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ], [
            'jenis.in' => 'Jenis transaksi tidak dikenal.',
            'dari.date' => 'Tanggal awal tidak valid.',
            'sampai.date' => 'Tanggal akhir tidak valid.',
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh lebih dulu dari tanggal awal.',
        ]);

        $transaksi = $nasabah->transaksi()
            ->with('user')
            ->when($validated['jenis'] ?? null, fn ($q, $jenis) => $q->where('jenis_trans', $jenis))
            ->when($validated['dari'] ?? null, fn ($q, $dari) => $q->whereDate('tanggal', '>=', $dari))
            ->when($validated['sampai'] ?? null, fn ($q, $sampai) => $q->whereDate('tanggal', '<=', $sampai))
            ->when($validated['q'] ?? null, function ($q, $cari) {
                $cari = trim($cari);
                $q->where(function ($inner) use ($cari) {
                    $inner->where('no_struk', 'like', '%'.$cari.'%')
                        ->orWhere('catatan', 'like', '%'.$cari.'%');
                });
            })
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? self::PER_HALAMAN)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $transaksi->getCollection()->map(fn (Transaksi $t) => ApiFormatter::transaksi($t))->values(),
            'meta' => ApiFormatter::pagination($transaksi),
        ]);
    }

    public function detailTransaksi(Request $request, int $transaksi): JsonResponse
    {
        $nasabah = $this->nasabah($request);

        if (! $nasabah) {
            return $this->gagal('Data nasabah tidak ditemukan.', 404);
        }

        $data = Transaksi::with(['user', 'nasabah'])
            ->where('id_nasabah', $nasabah->id)
            ->find($transaksi);

        if (! $data) {
            return $this->gagal('Transaksi tidak ditemukan.', 404);
        }

        return response()->json([
            'success' => true,
            'data' => ApiFormatter::detailTransaksi($data),
        ]);
    }

    public function cetakMutasi(Request $request)
    {
        $nasabah = $this->nasabah($request);

        if (! $nasabah) {
            return $this->gagal('Data nasabah tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'jenis' => ['nullable', 'in:'.implode(',', [
                Transaksi::JENIS_SETORAN,
                Transaksi::JENIS_PENARIKAN,
                Transaksi::JENIS_KOREKSI_SETORAN,
                Transaksi::JENIS_KOREKSI_PENARIKAN,
            ])],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ], [
            'jenis.in' => 'Jenis transaksi tidak dikenal.',
            'dari.date' => 'Tanggal awal tidak valid.',
            'sampai.date' => 'Tanggal akhir tidak valid.',
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh lebih dulu dari tanggal awal.',
        ]);

        $query = Transaksi::with('user')
            ->where('id_nasabah', $nasabah->id)
            ->where('koreksi_id', null)
            ->when($validated['jenis'] ?? null, fn ($q, $jenis) => $q->where('jenis_trans', $jenis))
            ->when($validated['dari'] ?? null, fn ($q, $dari) => $q->whereDate('tanggal', '>=', $dari))
            ->when($validated['sampai'] ?? null, fn ($q, $sampai) => $q->whereDate('tanggal', '<=', $sampai));

        $urut = (clone $query)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $totalMutasi = (int) $urut->sum(fn (Transaksi $t) => $t->nominal_bertanda);
        $saldoBerjalan = (int) $nasabah->saldo - $totalMutasi;

        $baris = $urut->map(function (Transaksi $t) use (&$saldoBerjalan) {
            $sebelum = $saldoBerjalan;
            $saldoBerjalan += $t->nominal_bertanda;

            return [
                'no' => 0,
                'tanggal' => $t->tanggal?->format('Y-m-d'),
                'tanggal_format' => $t->tanggal?->translatedFormat('d/m/Y') ?? ($t->tanggal ? $t->tanggal->format('d/m/Y') : '-'),
                'no_struk' => $t->no_struk,
                'jenis' => $t->jenis_trans,
                'jenis_label' => $t->jenis_label,
                'status' => $t->status,
                'status_label' => $t->status_label,
                'keterangan' => trim((string) $t->catatan) !== '' ? $t->catatan : $t->jenis_label,
                'teller' => $t->user?->nama_petugas ?? '-',
                'kredit' => $t->is_setoran ? (int) $t->nominal : 0,
                'debit' => $t->is_setoran ? 0 : (int) $t->nominal,
                'saldo_sebelum' => $sebelum,
                'saldo_sesudah' => $saldoBerjalan,
                'nominal' => (int) $t->nominal,
                'transaksi' => $t,
            ];
        })->values()->map(function ($item, $i) {
            $item['no'] = $i + 1;

            return $item;
        });

        $totalKredit = $baris->sum('kredit');
        $totalDebit = $baris->sum('debit');

        $format = strtolower((string) $request->query('format', 'json'));

        if ($format === 'pdf') {
            $data = [
                'nasabah' => $nasabah,
                'baris' => $baris,
                'request' => $request,
                'totalKredit' => $totalKredit,
                'totalDebit' => $totalDebit,
            ];

            $pdf = Pdf::loadView('api.nasabah.riwayat-print', $data);
            $pdf->setPaper('a4', 'portrait');

            return $pdf->download('mutasi-'.$nasabah->no_rekening.'.pdf');
        }

        if ($format === 'html' || $format === 'print') {
            return view('api.nasabah.riwayat-print', [
                'nasabah' => $nasabah,
                'baris' => $baris,
                'request' => $request,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'nasabah' => [
                    'nama' => $nasabah->nama_siswa,
                    'no_rekening' => $nasabah->no_rekening,
                    'nis' => $nasabah->nis,
                    'kelas' => $nasabah->kelas,
                    'produk' => $nasabah->produk,
                    'produk_label' => $nasabah->produk_label ?? null,
                    'saldo' => (int) $nasabah->saldo,
                ],
                'filter' => [
                    'jenis' => $validated['jenis'] ?? null,
                    'dari' => $validated['dari'] ?? null,
                    'sampai' => $validated['sampai'] ?? null,
                ],
                'ringkasan' => [
                    'jumlah_mutasi' => $baris->count(),
                    'total_kredit' => $totalKredit,
                    'total_debit' => $totalDebit,
                    'saldo_akhir' => (int) $nasabah->saldo,
                ],
                'mutasi' => $baris->map(function (array $item) {
                    unset($item['transaksi']);

                    return $item;
                })->values(),
            ],
        ]);
    }

    public function ubahPin(Request $request): JsonResponse
    {
        $nasabah = $this->nasabah($request);

        if (! $nasabah) {
            return $this->gagal('Data nasabah tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'pin_lama' => ['required', 'digits:6'],
            'pin_baru' => ['required', 'digits:6', 'confirmed'],
            'pin_baru_confirmation' => ['nullable', 'digits:6'],
        ], [
            'pin_lama.required' => 'PIN lama wajib diisi.',
            'pin_lama.digits' => 'PIN lama harus 6 digit.',
            'pin_baru.required' => 'PIN baru wajib diisi.',
            'pin_baru.digits' => 'PIN baru harus 6 digit.',
            'pin_baru.confirmed' => 'Konfirmasi PIN baru tidak sama.',
        ]);

        // PIN tidak langsung berubah. Nasabah hanya mengajukan; PIN baru
        // berlaku setelah disetujui admin atau supervisor.
        try {
            PermintaanPinService::ajukan($nasabah, $validated['pin_lama'], $validated['pin_baru']);
        } catch (\InvalidArgumentException $e) {
            return $this->gagal($e->getMessage(), 422, ['pin_lama' => [$e->getMessage()]]);
        } catch (\RuntimeException $e) {
            return $this->gagal($e->getMessage(), 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan perubahan PIN terkirim. Menunggu persetujuan petugas. PIN lama Anda tetap berlaku.',
        ]);
    }

    public function statusPengajuanPin(Request $request): JsonResponse
    {
        $nasabah = $this->nasabah($request);

        if (! $nasabah) {
            return $this->gagal('Data nasabah tidak ditemukan.', 404);
        }

        $permintaan = PermintaanResetPin::where('id_nasabah', $nasabah->id)
            ->orderByDesc('id')
            ->first();

        if (! $permintaan) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        $status = match (strtolower((string) $permintaan->status)) {
            'menunggu' => 'pending',
            'diproses' => 'diproses',
            'disetujui' => 'disetujui',
            'ditolak' => 'ditolak',
            default => strtolower((string) $permintaan->status),
        };

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $status,
                'keterangan' => $permintaan->catatan ?: $permintaan->keterangan,
                'diajukan_pada' => ($permintaan->diajukan_pada ?? $permintaan->created_at)?->toDateTimeString(),
                'alasan_penolakan' => strtolower($status) === 'ditolak' ? $permintaan->alasan_penolakan : null,
            ],
        ]);
    }

    private function nasabah(Request $request): ?Nasabah
    {
        return $request->user()->nasabah;
    }

    private function rupiah(int $nilai): string
    {
        return 'Rp '.number_format($nilai, 0, ',', '.');
    }

    private function gagal(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json(array_filter([
            'success' => false,
            'message' => $message,
            'errors' => $errors ?: null,
        ], fn ($value) => $value !== null), $status);
    }
}
