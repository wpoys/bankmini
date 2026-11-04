<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Nasabah;
use App\Models\PermintaanResetPin;
use App\Models\User;
use App\Support\PendaftaranNasabah;
use App\Support\PermintaanPinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NasabahController extends Controller
{
    public function index(Request $request): View
    {
        $nasabah = Nasabah::query()
            ->with('user')
            ->when($request->search, fn($q) => $q
                ->where('nama_siswa', 'like', "%{$request->search}%")
                ->orWhere('no_rekening', 'like', "%{$request->search}%")
                ->orWhere('nis', 'like', "%{$request->search}%"))
            ->when($request->kelas, fn($q) => $q->where('kelas', $request->kelas))
            ->when($request->arsip === 'aktif', fn($q) => $q->where('arsip', false))
            ->when($request->arsip === 'arsip', fn($q) => $q->where('arsip', true))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.nasabah.index', compact('nasabah'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.nasabah.index', ['modal' => 'nasabah']);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(PendaftaranNasabah::aturan());

        try {
            $result = PendaftaranNasabah::buat(auth()->id(), $validated);

            return redirect()->route('admin.nasabah.index')
                ->with('success', "Nasabah {$result->nama_siswa} berhasil dibuat dengan no. rekening {$result->no_rekening}.");
        } catch (\Throwable $e) {
            return back()->withInput()
                ->with('error', 'Gagal membuat nasabah: ' . $e->getMessage());
        }
    }

    public function edit(Nasabah $nasabah): View
    {
        return view('admin.nasabah.edit', compact('nasabah'));
    }

    public function update(Request $request, Nasabah $nasabah): RedirectResponse
    {
        $validated = $request->validate(PendaftaranNasabah::aturan(true, $nasabah));

        try {
            PendaftaranNasabah::perbarui($nasabah, auth()->id(), $validated);

            return redirect()->route('admin.nasabah.index')
                ->with('success', 'Nasabah berhasil diperbarui.');
        } catch (\Throwable $e) {
            return back()->withInput()
                ->with('error', 'Gagal memperbarui nasabah: ' . $e->getMessage());
        }
    }

    public function destroy(Nasabah $nasabah): RedirectResponse
    {
        if (!$nasabah->isArsip()) {
            return back()->with('error', 'Nasabah aktif tidak dapat dihapus. Arsipkan nasabah terlebih dahulu.');
        }

        try {
            $nama = $nasabah->nama_siswa;
            $akun = $nasabah->user;

            DB::transaction(function () use ($nasabah, $akun) {
                $transaksiIds = $nasabah->transaksi()->pluck('id');

                if ($transaksiIds->isNotEmpty()) {
                    DB::table('koreksi_transaksi')->whereIn('transaksi_id', $transaksiIds)->delete();
                    DB::table('jurnal_akuntansi')->whereIn('id_transaksi', $transaksiIds)->delete();
                    DB::table('detail_laporan')->whereIn('transaksi_id', $transaksiIds)->delete();
                    DB::table('transaksi')->whereIn('id', $transaksiIds)->delete();
                }

                DB::table('permintaan_reset_pin')->where('id_nasabah', $nasabah->id)->delete();

                $nasabah->delete();
                $akun?->delete();
            });

            ActivityLog::catat(auth()->id(), 'hapus_nasabah', "Nasabah {$nama} dihapus permanen beserta akun user dan seluruh data terkait.");

            return redirect()->route('admin.nasabah.index')
                ->with('success', "Nasabah {$nama}, akun user, saldo, dan seluruh data terkait berhasil dihapus permanen.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus nasabah permanen: ' . $e->getMessage());
        }
    }

    public function arsip(Nasabah $nasabah): RedirectResponse
    {
        if ($nasabah->isArsip()) {
            return back()->with('error', 'Nasabah ini sudah diarsipkan.');
        }

        DB::transaction(function () use ($nasabah) {
            $nasabah->update(['arsip' => true]);

            $akun = $nasabah->user ?? $nasabah->ensureUser();
            $akun->update(['status' => 'inactive']);
        });

        ActivityLog::catat(auth()->id(), 'arsip_nasabah', "Nasabah {$nasabah->nama_siswa} ({$nasabah->no_rekening}) diarsipkan sebagai alumni (akun dinonaktifkan).");

        return back()->with('success', "Nasabah {$nasabah->nama_siswa} diarsipkan sebagai alumni. Akun dinonaktifkan; data kini dapat dihapus permanen.");
    }

    public function restore(Nasabah $nasabah): RedirectResponse
    {
        if (!$nasabah->isArsip()) {
            return back()->with('error', 'Nasabah ini tidak dalam status arsip.');
        }

        DB::transaction(function () use ($nasabah) {
            $nasabah->update(['arsip' => false]);

            $akun = $nasabah->user ?? $nasabah->ensureUser();
            $akun->update(['status' => 'active']);
        });

        ActivityLog::catat(auth()->id(), 'pulihkan_nasabah', "Nasabah {$nasabah->nama_siswa} ({$nasabah->no_rekening}) dikembalikan dari arsip.");

        return back()->with('success', "Nasabah {$nasabah->nama_siswa} dikembalikan dari arsip dan akunnya diaktifkan kembali.");
    }

    /**
     * Tampilkan formulir unggah data nasabah massal.
     */
    public function importForm(): View
    {
        return view('admin.nasabah.import');
    }

    /**
     * Unduh template CSV untuk import data nasabah.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $template = "nis,nama,kelas,produk,email,saldo_awal\n";
        $template .= "20261001,Ahmad Fauzi,RPL 1,siswa,ahmad.fauzi@contoh.test,50000\n";
        $template .= "20261002,Budi Hartono,DKV 2,siswa,budi.hartono@contoh.test,0\n";
        $template .= "20261003,Ibu Ratna Sari,AKL 1,guru,ratna.sari@contoh.test,100000\n";

        return response()->streamDownload(function () use ($template) {
            echo "\xEF\xBB\xBF" . $template;
        }, 'template-import-nasabah.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Proses unggah CSV: validasi, tampilkan pratinjau untuk dikonfirmasi.
     */
    public function importPreview(Request $request): View|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $path = $request->file('file')->store('import', 'local');
        $content = Storage::disk('local')->get($path);
        Storage::disk('local')->delete($path);

        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        $baris = $this->parseCsv($content);

        if (count($baris) < 2) {
            return back()->with('error', 'File CSV kosong atau format tidak sesuai.');
        }

        $header = array_map(fn($h) => strtolower(trim($h)), $baris[0]);

        if ($this->punyaKolomTerlarang($header)) {
            return back()->with('error', 'Kolom no_hp tidak didukung. Gunakan kolom email untuk kontak nasabah.');
        }

        $indeks = $this->petakanKolom($header);

        if ($indeks['nis'] === null || $indeks['nama'] === null || $indeks['kelas'] === null) {
            return back()->with('error', 'Kolom wajib (nis, nama, kelas) tidak ditemukan pada baris header.');
        }

        $rows = collect();
        $nisDalamFile = [];
        $emailDalamFile = [];

        foreach (array_slice($baris, 1) as $no => $kolom) {
            $row = $no + 2;

            $data = [
                'row' => $row,
                'nis' => $this->nilaiKolom($kolom, $indeks, 'nis'),
                'nama' => $this->nilaiKolom($kolom, $indeks, 'nama'),
                'kelas' => $this->nilaiKolom($kolom, $indeks, 'kelas'),
                'produk' => strtolower($this->nilaiKolom($kolom, $indeks, 'produk')),
                'email' => strtolower($this->nilaiKolom($kolom, $indeks, 'email')),
                'saldo_awal' => $this->nilaiKolom($kolom, $indeks, 'saldo_awal'),
            ];

            $data['produk'] = $data['produk'] === '' ? Nasabah::PRODUK_SISWA : $data['produk'];
            $data['saldo_awal'] = $data['saldo_awal'] === '' ? '0' : $data['saldo_awal'];

            $data['errors'] = $this->validasiBaris($data, $nisDalamFile, $emailDalamFile);
            $data['valid'] = empty($data['errors']);
            $data['saldo_awal'] = is_numeric($data['saldo_awal']) ? (int) $data['saldo_awal'] : 0;

            if ($data['nis'] !== '') {
                $nisDalamFile[] = $data['nis'];
            }

            if ($data['email'] !== '') {
                $emailDalamFile[] = strtolower($data['email']);
            }

            $rows->push($data);
        }

        if ($rows->isEmpty()) {
            return back()->with('error', 'Tidak ada baris data yang ditemukan setelah header.');
        }

        session()->put('import_preview', $rows->all());

        return view('admin.nasabah.import-preview', [
            'rows' => $rows,
            'jumlahValid' => $rows->where('valid', true)->count(),
            'jumlahGagal' => $rows->where('valid', false)->count(),
        ]);
    }

    /**
     * Konfirmasi pratinjau: simpan baris yang valid menjadi nasabah baru.
     */
    public function importKonfirmasi(Request $request): RedirectResponse
    {
        $preview = session('import_preview');

        if (!$preview) {
            return redirect()->route('admin.nasabah.import')->with('error', 'Tidak ada data pratinjau. Silakan unggah file terlebih dahulu.');
        }

        $berhasil = 0;
        $gagal = 0;
        $kesalahan = [];

        foreach ($preview as $data) {
            if (!$data['valid']) {
                $gagal++;
                $kesalahan[] = 'Baris ' . $data['row'] . ': ' . implode(' ', $data['errors']);

                continue;
            }

            try {
                PendaftaranNasabah::buat(auth()->id(), [
                    'nama_siswa' => $data['nama'],
                    'nis' => $data['nis'],
                    'kelas' => $data['kelas'],
                    'produk' => $data['produk'],
                    'email' => $data['email'] ?: null,
                    'saldo_awal' => (int) $data['saldo_awal'],
                ]);

                $berhasil++;
            } catch (\Throwable $e) {
                $gagal++;
                $kesalahan[] = 'Baris ' . $data['row'] . ': Gagal menyimpan (' . $e->getMessage() . ').';
            }
        }

        ActivityLog::catat(auth()->id(), 'import_nasabah', "Import data nasabah: {$berhasil} berhasil, {$gagal} gagal.");

        session()->forget('import_preview');
        session()->put('import_hasil', [
            'berhasil' => $berhasil,
            'gagal' => $gagal,
            'kesalahan' => $kesalahan,
        ]);

        return redirect()->route('admin.nasabah.import.hasil');
    }

    /**
     * Laporkan hasil impor kepada petugas.
     */
    public function importHasil(): View|RedirectResponse
    {
        $hasil = session('import_hasil');

        if (!$hasil) {
            return redirect()->route('admin.nasabah.import');
        }

        return view('admin.nasabah.import-hasil', compact('hasil'));
    }

    protected function validasiBaris(array $data, array $nisDalamFile = [], array $emailDalamFile = []): array
    {
        $errors = [];

        if ($data['nis'] === '' || $data['nama'] === '' || $data['kelas'] === '') {
            $errors[] = 'NIS, nama, dan kelas wajib diisi.';

            return $errors;
        }

        if (!in_array($data['kelas'], Nasabah::KELAS, true)) {
            $errors[] = 'Kelas tidak tersedia pada daftar kelas yang diperbolehkan.';
        }

        if (Nasabah::where('nis', $data['nis'])->exists() || User::where('username', $data['nis'])->exists()) {
            $errors[] = "NIS {$data['nis']} sudah terdaftar.";
        }

        if (in_array($data['nis'], $nisDalamFile, true)) {
            $errors[] = "NIS {$data['nis']} duplikat di dalam file.";
        }

        if (!in_array($data['produk'], [Nasabah::PRODUK_SISWA, Nasabah::PRODUK_GURU], true)) {
            $errors[] = 'Produk harus siswa atau guru.';
        }

        if ($data['email'] !== '') {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Format email tidak valid.';
            } elseif (User::where('email', $data['email'])->exists()) {
                $errors[] = "Email {$data['email']} sudah digunakan.";
            } elseif (in_array(strtolower($data['email']), $emailDalamFile, true)) {
                $errors[] = "Email {$data['email']} duplikat di dalam file.";
            }
        } else {
            $errors[] = 'Email wajib diisi untuk aktivasi akun nasabah.';
        }

        if (!is_numeric($data['saldo_awal']) || (int) $data['saldo_awal'] < 0) {
            $errors[] = 'Saldo awal harus berupa angka minimal 0.';
        }

        return $errors;
    }

    protected function parseCsv(string $content): array
    {
        $rows = [];
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        while (($data = fgetcsv($stream)) !== false) {
            $rows[] = array_map('trim', $data);
        }

        fclose($stream);

        return $rows;
    }

    protected function petakanKolom(array $header): array
    {
        $sinonim = [
            'nis' => ['nis', 'niss', 'nomer_induk'],
            'nama' => ['nama', 'nama_siswa', 'nama_santri', 'name'],
            'kelas' => ['kelas', 'class', 'rombel'],
            'produk' => ['produk', 'product', 'jenis'],
            'email' => ['email', 'e-mail', 'surel'],
            'pin' => ['pin', 'pin_keamanan', 'pin_keamanan_6'],
            'saldo_awal' => ['saldo_awal', 'saldo', 'setoran_awal'],
        ];

        $hasil = [];

        foreach ($sinonim as $kunci => $padanan) {
            $hasil[$kunci] = null;

            foreach ($header as $i => $kolom) {
                if (in_array($kolom, $padanan, true)) {
                    $hasil[$kunci] = $i;

                    break;
                }
            }
        }

        return $hasil;
    }

    protected function punyaKolomTerlarang(array $header): bool
    {
        $terlarang = ['no_hp', 'no hp', 'hp', 'phone', 'telepon', 'no_telepon', 'whatsapp', 'wa'];

        foreach ($header as $kolom) {
            if (in_array($kolom, $terlarang, true)) {
                return true;
            }
        }

        return false;
    }

    protected function nilaiKolom(array $kolom, array $indeks, string $kunci): string
    {
        $posisi = $indeks[$kunci] ?? null;

        if ($posisi === null) {
            return '';
        }

        return trim((string) ($kolom[$posisi] ?? ''));
    }

    /**
     * Proses permintaan reset PIN: buat PIN baru, catat pemroses & waktu.
     */
    /**
     * Proses permintaan reset PIN.
     *
     * Keamanan: PIN baru TIDAK lagi diketik oleh Admin di halaman ini. Admin
     * cukup menyetujui; sistem otomatis menerapkan hash PIN baru yang sudah
     * dikirim nasabah. Jika pengajuan dibuat petugas (tanpa pin_baru_hash),
     * proses tidak diizinkan.
     */
    public function prosesPermintaanResetPin(Request $request, PermintaanResetPin $permintaan): RedirectResponse
    {
        $request->validate([
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            PermintaanPinService::setujui($permintaan, $request->user(), $request->input('catatan'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengajuan perubahan PIN disetujui. PIN baru sudah otomatis berlaku.');
    }
}
