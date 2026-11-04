<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Konfigurasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class KonfigurasiController extends Controller
{
    public function index(): View
    {
        $items = collect(Konfigurasi::DESKRIPSI)->map(function ($deskripsi, $key) {
            return [
                'key' => $key,
                'deskripsi' => $deskripsi,
                'value' => Konfigurasi::nilai($key, ''),
            ];
        })->values()->all();

        $kodeSandiTersetel = Konfigurasi::kodeSandiKoreksiTersetel();

        return view('admin.konfigurasi.index', compact('items', 'kodeSandiTersetel'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            Konfigurasi::SALDO_MINIMUM => ['required', 'integer', 'min:0'],
            Konfigurasi::BATAS_PENARIKAN_BESAR => ['required', 'integer', 'min:0'],
            Konfigurasi::KAS_AWAL_HARIAN => ['required', 'integer', 'min:0'],
            Konfigurasi::KODE_JURUSAN => ['required', 'string', 'max:500'],
            Konfigurasi::KODE_SANDI_KOREKSI => ['nullable', 'string', 'min:4', 'max:64'],
        ], [
            Konfigurasi::SALDO_MINIMUM.'.required' => 'Saldo minimum wajib diisi.',
            Konfigurasi::SALDO_MINIMUM.'.integer' => 'Saldo minimum harus berupa angka.',
            Konfigurasi::BATAS_PENARIKAN_BESAR.'.required' => 'Batas penarikan besar wajib diisi.',
            Konfigurasi::KAS_AWAL_HARIAN.'.required' => 'Kas awal harian wajib diisi.',
            Konfigurasi::KODE_JURUSAN.'.required' => 'Daftar kode jurusan wajib diisi.',
            Konfigurasi::KODE_SANDI_KOREKSI.'.min' => 'Kode sandi koreksi minimal 4 karakter.',
        ]);

        $kodeRaw = strtoupper(trim($validated[Konfigurasi::KODE_JURUSAN]));
        $kodeList = array_values(array_filter(array_map('trim', preg_split('/[,\s]+/', $kodeRaw) ?: []), fn ($k) => $k !== ''));

        if ($kodeList === []) {
            return back()->withInput()->with('error', 'Daftar kode jurusan tidak boleh kosong.');
        }

        $nilai = $validated;
        $nilai[Konfigurasi::KODE_JURUSAN] = implode(',', $kodeList);

        foreach (Konfigurasi::DESKRIPSI as $key => $deskripsi) {
            if (array_key_exists($key, $nilai)) {
                Konfigurasi::set($key, $nilai[$key], $deskripsi, auth()->id());
            }
        }

        $kodeSandiDiubah = false;
        $kodeSandiInput = trim((string) ($validated[Konfigurasi::KODE_SANDI_KOREKSI] ?? ''));

        if ($kodeSandiInput !== '') {
            Konfigurasi::set(
                Konfigurasi::KODE_SANDI_KOREKSI,
                Hash::make($kodeSandiInput),
                'Kode sandi persetujuan koreksi transaksi (disimpan ter-hash)',
                auth()->id(),
            );
            $kodeSandiDiubah = true;
        }

        ActivityLog::catat(
            auth()->id(),
            'ubah_konfigurasi',
            'Konfigurasi sistem (saldo minimum, batas penarikan besar, kas awal harian, kode jurusan) diperbarui.'
            .($kodeSandiDiubah ? ' Kode sandi persetujuan koreksi diperbarui.' : '')
        );

        return redirect()->route('admin.konfigurasi.index')
            ->with('success', 'Konfigurasi berhasil diperbarui.');
    }
}
