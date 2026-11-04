<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Konfigurasi;
use App\Models\Nasabah;
use App\Models\Transaksi;
use App\Models\UserNotification;
use App\Support\KodeAkun;
use App\Support\NomorGenerator;
use App\Support\QrisNasabah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TransaksiController extends Controller
{
    /**
     * Maksimal token form yang disimpan di session (guard refresh/kirim ganda).
     */
    public const MAKS_TOKEN_DIPAKAI = 10;

    /**
     * Jeda minimum antar transaksi identik (anti klik ganda). Transaksi sah
     * dengan nominal sama tetap dapat dilakukan setelah jeda ini.
     */
    public const JEDA_ANTAR_TRANSAKSI_DETIK = 15;

    public function index(Request $request): View
    {
        $nasabah = null;
        $transaksiTerakhir = Transaksi::with(['nasabah', 'user'])
            ->where('id_user', Auth::id())
            ->latest()
            ->take(10)
            ->get();

        return view('teller.transaksi.index', [
            'nasabah' => $nasabah,
            'transaksiTerakhir' => $transaksiTerakhir,
            'tokenForm' => $this->tokenForm($request),
        ]);
    }

    public function cariNasabah(Request $request): View|JsonResponse
    {
        $request->validate([
            'cari' => ['required', 'string'],
        ]);

        $keyword = trim($request->input('cari'));

        // QRIS / QR Code nasabah: isi QR adalah nomor rekening dan stabil
        // (tanpa token/waktu), sehingga QR yang sama bisa dipindai berkali-
        // kali dan selalu menunjuk nasabah yang sama. Hanya nasabah AKTIF
        // (belum diarsipkan) yang boleh dilayani.
        $nasabah = QrisNasabah::cari($keyword);

        $transaksiTerakhir = Transaksi::with(['nasabah', 'user'])
            ->where('id_user', Auth::id())
            ->latest()
            ->take(10)
            ->get();

        if ($request->ajax() || $request->wantsJson()) {
            if (! $nasabah) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nasabah dengan nomor rekening/NIS "'.$keyword.'" tidak ditemukan.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Nasabah ditemukan: '.$nasabah->nama_siswa,
                'nasabah' => [
                    'id' => $nasabah->id,
                    'nama_siswa' => $nasabah->nama_siswa,
                    'no_rekening' => $nasabah->no_rekening,
                ],
            ]);
        }

        return view('teller.transaksi.index', [
            'nasabah' => $nasabah,
            'transaksiTerakhir' => $transaksiTerakhir,
            'tokenForm' => $this->tokenForm($request),
        ])
            ->with($nasabah ? 'success' : 'error', $nasabah
                ? 'Nasabah ditemukan: '.$nasabah->nama_siswa
                : 'Nasabah dengan nomor rekening/NIS "'.$keyword.'" tidak ditemukan.');
    }

    public function proses(Request $request): RedirectResponse
    {
        // Cegah transaksi ganda bila form di-refresh / dikirim ulang.
        if ($sudah = $this->sudahDiproses($request)) {
            return redirect()->route('teller.struk', $sudah)
                ->with('error', 'Transaksi ini sudah pernah diproses sebelumnya. Transaksi tidak digandakan.');
        }

        $validated = $request->validate([
            'id_nasabah' => ['required', 'exists:nasabah,id'],
            'jenis_trans' => ['required', 'in:setoran,penarikan'],
            'nominal' => ['required', 'integer', 'min:1'],
            'pin_keamanan' => ['required', 'digits:6'],
            'konfirmasi_h1' => ['nullable', 'boolean'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $nasabah = Nasabah::findOrFail($validated['id_nasabah']);

        if (! Hash::check($validated['pin_keamanan'], $nasabah->pin_keamanan)) {
            UserNotification::kirim(Auth::id(), UserNotification::TIPE_WARNING, 'PIN Keamanan Salah', 'Transaksi untuk '.$nasabah->nama_siswa.' dibatalkan karena PIN tidak sesuai.', route('teller.transaksi.index'));

            return back()->with('error', 'PIN keamanan salah. Transaksi dibatalkan.');
        }

        if ($nasabah->isArsip()) {
            UserNotification::kirim(Auth::id(), UserNotification::TIPE_WARNING, 'Nasabah Tidak Aktif', 'Transaksi untuk '.$nasabah->nama_siswa.' dibatalkan karena nasabah diarsipkan.', route('teller.transaksi.index'));

            return back()->with('error', 'Nasabah ini sedang tidak aktif (diarsipkan). Transaksi dibatalkan.');
        }

        $nominal = (int) $validated['nominal'];
        $saldoMinimum = Konfigurasi::saldoMinimum();
        $batasPenarikanBesar = Konfigurasi::batasPenarikanBesar();

        if ($validated['jenis_trans'] === 'penarikan') {
            if ($nominal > $nasabah->saldo) {
                UserNotification::kirim(Auth::id(), UserNotification::TIPE_DANGER, 'Saldo Tidak Mencukupi', 'Penarikan untuk '.$nasabah->nama_siswa.' gagal karena saldo tidak mencukupi.', route('teller.transaksi.index'));

                return back()->with('error', 'Saldo nasabah tidak mencukupi untuk penarikan ini.');
            }

            if (($nasabah->saldo - $nominal) < $saldoMinimum) {
                UserNotification::kirim(Auth::id(), UserNotification::TIPE_DANGER, 'Penarikan Dibatalkan', 'Penarikan untuk '.$nasabah->nama_siswa.' ditolak karena melanggar batas saldo mengendap minimum.', route('teller.transaksi.index'));

                return back()->with('error', 'Penarikan ditolak: saldo mengendap minimum Rp '.number_format($saldoMinimum, 0, ',', '.').' harus tersisa setelah transaksi.');
            }

            if ($nominal > $batasPenarikanBesar && ! $request->boolean('konfirmasi_h1')) {
                UserNotification::kirim(Auth::id(), UserNotification::TIPE_WARNING, 'Penarikan Besar Perlu Konfirmasi', 'Penarikan untuk '.$nasabah->nama_siswa.' di atas batas (Rp '.number_format($batasPenarikanBesar, 0, ',', '.').') harus mendapat konfirmasi H-1.', route('teller.transaksi.index'));

                return back()->with('error', 'Penarikan ini tergolong penarikan besar (> Rp '.number_format($batasPenarikanBesar, 0, ',', '.').') dan wajib mendapat konfirmasi nasabah terlebih dahulu (konfirmasi H-1).');
            }
        }

        try {
            $konfirmasiH1 = $request->boolean('konfirmasi_h1');
            $catatan = $request->input('catatan');

            $transaksi = DB::transaction(function () use ($nasabah, $validated, $nominal, $konfirmasiH1, $catatan) {
                $noStruk = NomorGenerator::struk();

                $transaksi = Transaksi::create([
                    'id_nasabah' => $nasabah->id,
                    'id_user' => Auth::id(),
                    'tanggal' => today(),
                    'jenis_trans' => $validated['jenis_trans'],
                    'nominal' => $nominal,
                    'konfirmasi_h1' => $konfirmasiH1,
                    'catatan' => $catatan,
                    'no_struk' => $noStruk,
                    'status' => 'approved',
                ]);

                if ($validated['jenis_trans'] === 'setoran') {
                    $nasabah->increment('saldo', $nominal);
                    $nasabah->save();

                    $this->catatJurnal($transaksi->id, $nominal, 'setoran');
                } else {
                    $nasabah->decrement('saldo', $nominal);
                    $nasabah->save();

                    $this->catatJurnal($transaksi->id, $nominal, 'penarikan');
                }

                ActivityLog::catat(
                    Auth::id(),
                    $validated['jenis_trans'],
                    ucfirst($validated['jenis_trans']).' Rp '.number_format($nominal, 0, ',', '.').
                    " atas nama {$nasabah->nama_siswa} (no struk {$noStruk})."
                );

                return $transaksi;
            });

            $userNasabah = $nasabah->user ?? $nasabah->ensureUser();
            $nominalRupiah = 'Rp '.number_format($nominal, 0, ',', '.');
            $jenisLabel = ucfirst($validated['jenis_trans']);

            UserNotification::kirim(
                $userNasabah,
                UserNotification::TIPE_SUCCESS,
                "Transaksi {$jenisLabel} Berhasil",
                "{$jenisLabel} {$nominalRupiah} telah diproses pada rekening {$nasabah->no_rekening}. Saldo sekarang {$nasabah->saldo_rupiah}.",
                null,
            );

            $this->tandaiDiproses($request, $transaksi->id);
            $this->catatFingerprint($request, $transaksi->id);

            return redirect()->route('teller.struk', $transaksi->id)
                ->with('success', "Transaksi berhasil diproses. Nominal struk: {$nominalRupiah}.")
                ->with('struk_baru', $transaksi->id);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses transaksi: '.$e->getMessage());
        }
    }

    /**
     * Token unik per render form transaksi. Dipakai sebagai guard agar
     * refresh halaman / kirim ulang POST tidak membuat transaksi ganda.
     */
    protected function tokenForm(Request $request): string
    {
        $token = $request->session()->get('form_transaksi_token');

        if (! is_string($token) || $token === '') {
            $token = Str::random(40);
            $request->session()->put('form_transaksi_token', $token);
        }

        return $token;
    }

    /**
     * ID transaksi terakhir bila permintaan ini sudah pernah diproses.
     *
     * Dua lapis guard anti transaksi ganda:
     * 1. Token form (menangkap refresh / kirim ulang form yang sama).
     * 2. Fingerprint transaksi (menangkap klik ganda yang menghasilkan dua
     *    request dengan token berbeda dalam hitungan detik).
     */
    protected function sudahDiproses(Request $request): ?int
    {
        $token = (string) $request->input('token_transaksi', '');

        if ($token !== '') {
            $dipakai = (array) $request->session()->get('form_transaksi_dipakai', []);

            if (in_array($token, $dipakai, true)) {
                $terakhir = (int) $request->session()->get('transaksi_terakhir_id', 0);

                return $terakhir > 0 ? $terakhir : null;
            }
        }

        return $this->sudahDiprosesFingerprint($request);
    }

    /**
     * Fingerprint: nasabah + jenis + nominal + PIN, tanpa timestamp.
     * Berlaku singkat (default 15 detik) agar transaksi sah tetap bisa dilakukan.
     */
    protected function sudahDiprosesFingerprint(Request $request): ?int
    {
        $idNasabah = $request->input('id_nasabah');
        $jenis = $request->input('jenis_trans');
        $nominal = $request->input('nominal');
        $pin = $request->input('pin_keamanan');

        if (! $idNasabah || ! $jenis || ! $nominal || ! $pin) {
            return null;
        }

        $kunci = hash('sha256', implode('|', [
            $request->user()?->id,
            $idNasabah,
            $jenis,
            (int) $nominal,
            $pin,
        ]));

        $tercatat = (array) $request->session()->get('transaksi_fingerprint', []);
        $kadaluarsa = now()->subSeconds(self::JEDA_ANTAR_TRANSAKSI_DETIK)->timestamp;
        $masih = array_filter($tercatat, fn ($item) => (int) ($item['pada'] ?? 0) > $kadaluarsa);

        $cocok = null;
        foreach ($masih as $item) {
            if (hash_equals((string) ($item['kunci'] ?? ''), $kunci)) {
                $cocok = (int) ($item['id'] ?? 0);
            }
        }

        if ($cocok === null) {
            return null;
        }

        // Kunci ditemukan: rotasi agar request berikutnya tidak ikut tertahan.
        $request->session()->put('transaksi_fingerprint', $masih);

        return $cocok > 0 ? $cocok : null;
    }

    protected function catatFingerprint(Request $request, int $transaksiId): void
    {
        $idNasabah = $request->input('id_nasabah');
        $jenis = $request->input('jenis_trans');
        $nominal = $request->input('nominal');
        $pin = $request->input('pin_keamanan');

        if (! $idNasabah || ! $jenis || ! $nominal || ! $pin) {
            return;
        }

        $kunci = hash('sha256', implode('|', [
            $request->user()?->id,
            $idNasabah,
            $jenis,
            (int) $nominal,
            $pin,
        ]));

        $tercatat = (array) $request->session()->get('transaksi_fingerprint', []);
        $kadaluarsa = now()->subSeconds(self::JEDA_ANTAR_TRANSAKSI_DETIK)->timestamp;
        $tercatat = array_values(array_filter($tercatat, fn ($item) => (int) ($item['pada'] ?? 0) > $kadaluarsa));
        $tercatat[] = ['kunci' => $kunci, 'id' => $transaksiId, 'pada' => now()->timestamp];

        $request->session()->put('transaksi_fingerprint', array_slice($tercatat, -self::MAKS_TOKEN_DIPAKAI));
    }

    /**
     * Tandai token form sebagai sudah dipakai lalu rotasi token baru.
     */
    protected function tandaiDiproses(Request $request, int $transaksiId): void
    {
        $token = (string) $request->input('token_transaksi', '');

        if ($token !== '') {
            $dipakai = (array) $request->session()->get('form_transaksi_dipakai', []);
            $dipakai[] = $token;
            $request->session()->put('form_transaksi_dipakai', array_slice($dipakai, -self::MAKS_TOKEN_DIPAKAI));
        }

        $request->session()->put('transaksi_terakhir_id', $transaksiId);
        $request->session()->put('form_transaksi_token', Str::random(40));
    }

    protected function catatJurnal(int $idTransaksi, int $nominal, string $jenis): void
    {
        if ($jenis === 'setoran') {
            $entries = [
                ['kode_akun' => KodeAkun::KAS, 'posisi' => 'debit', 'jumlah' => $nominal],
                ['kode_akun' => KodeAkun::TABUNGAN, 'posisi' => 'kredit', 'jumlah' => $nominal],
            ];
        } else {
            $entries = [
                ['kode_akun' => KodeAkun::TABUNGAN, 'posisi' => 'debit', 'jumlah' => $nominal],
                ['kode_akun' => KodeAkun::KAS, 'posisi' => 'kredit', 'jumlah' => $nominal],
            ];
        }

        foreach ($entries as $entry) {
            DB::table('jurnal_akuntansi')->insert([
                'id_transaksi' => $idTransaksi,
                'kode_akun' => $entry['kode_akun'],
                'posisi' => $entry['posisi'],
                'jumlah' => $entry['jumlah'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
