<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Nasabah;
use App\Models\PermintaanResetPin;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\PermintaanPinService;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PengajuanPinTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $supervisor;

    protected User $teller;

    protected Nasabah $nasabah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);

        $this->admin = User::where('role', 'admin')->firstOrFail();
        $this->supervisor = User::where('role', 'supervisor')->firstOrFail();
        $this->teller = User::where('username', 'teller1')->firstOrFail();
        $this->nasabah = Nasabah::where('nis', '20241001')->firstOrFail();

        PermintaanResetPin::query()->delete();
    }

    // ---------- Persetujuan oleh Admin ATAU Supervisor ----------

    public function test_admin_dapat_menyetujui_pengajuan_pin(): void
    {
        $p = $this->buatPengajuan();

        $this->actingAs($this->admin)
            ->post(route('permintaan-pin.setujui', $p->id))
            ->assertSessionHas('success');

        $p->refresh();
        $this->assertSame(PermintaanResetPin::STATUS_DIPROSES, $p->status);
        $this->assertSame($this->admin->id, $p->diproses_oleh);
        $this->assertNotNull($p->disetujui_at);
    }

    public function test_supervisor_dapat_menyetujui_ketika_admin_tidak_tersedia(): void
    {
        $p = $this->buatPengajuan();

        // Admin tidak acting sama sekali; Supervisor memproses.
        $this->actingAs($this->supervisor)
            ->post(route('permintaan-pin.setujui', $p->id))
            ->assertSessionHas('success');

        $this->assertSame(PermintaanResetPin::STATUS_DIPROSES, $p->fresh()->status);
        $this->assertSame($this->supervisor->id, $p->fresh()->diproses_oleh);
    }

    public function test_tidak_perlu_dua_persetujuan_admin_dan_supervisor(): void
    {
        $p = $this->buatPengajuan();

        $this->actingAs($this->admin)->post(route('permintaan-pin.setujui', $p->id));

        $p->refresh();
        $this->assertSame(PermintaanResetPin::STATUS_DIPROSES, $p->status);
        $this->assertSame($this->admin->id, $p->diproses_oleh, 'Cukup satu yang menyetujui.');
        $this->assertNull($p->fresh()->status === PermintaanResetPin::STATUS_PENDING ? 'masih menunggu' : null);
    }

    public function test_penolakan_oleh_admin_dengan_alasan(): void
    {
        $p = $this->buatPengajuan();

        $this->actingAs($this->admin)
            ->post(route('permintaan-pin.tolak', $p->id), ['alasan_penolakan' => 'Data belum sesuai'])
            ->assertSessionHas('success');

        $p->refresh();
        $this->assertSame(PermintaanResetPin::STATUS_DITOLAK, $p->status);
        $this->assertSame('Data belum sesuai', $p->alasan_penolakan);
        $this->assertSame($this->admin->id, $p->diproses_oleh);
        $this->assertNotNull($p->ditolak_at);
    }

    public function test_penolakan_oleh_supervisor_dengan_alasan(): void
    {
        $p = $this->buatPengajuan();

        $this->actingAs($this->supervisor)
            ->post(route('permintaan-pin.tolak', $p->id), ['alasan_penolakan' => 'Silakan datang ke loket'])
            ->assertSessionHas('success');

        $this->assertSame(PermintaanResetPin::STATUS_DITOLAK, $p->fresh()->status);
    }

    public function test_pengajuan_tanpa_hash_pin_baru_tidak_dapat_disetujui(): void
    {
        $permintaan = PermintaanResetPin::create([
            'id_nasabah' => $this->nasabah->id,
            'keterangan' => 'Pengajuan lama tanpa hash PIN baru.',
            'pin_baru_hash' => null,
            'status' => PermintaanResetPin::STATUS_PENDING,
            'diajukan_oleh_role' => 'nasabah',
        ]);

        $this->actingAs($this->admin)
            ->post(route('permintaan-pin.setujui', $permintaan->id))
            ->assertSessionHas('error');

        $this->assertSame(PermintaanResetPin::STATUS_PENDING, $permintaan->fresh()->status);
    }

    public function test_alasan_penolakan_wajib_diisi(): void
    {
        $p = $this->buatPengajuan();

        $this->actingAs($this->admin)
            ->post(route('permintaan-pin.tolak', $p->id), ['alasan_penolakan' => ''])
            ->assertSessionHasErrors('alasan_penolakan');
    }

    // ---------- Keamanan PIN ----------

    public function test_halaman_petugas_tidak_menampilkan_pin_baru_maupun_pin_lama(): void
    {
        $this->buatPengajuan();

        foreach ([$this->admin, $this->supervisor] as $aktor) {
            $response = $this->actingAs($aktor)->get(route('permintaan-pin.index'));

            $response->assertOk();
            $response->assertDontSee('246810');
            $response->assertDontSee('123456');
            $response->assertDontSee('pin_baru');
        }
    }

    public function test_halaman_admin_tidak_meminta_input_pin_baru(): void
    {
        $this->buatPengajuan();

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('name="pin_baru"', false);
        $response->assertDontSee('246810');
    }

    public function test_hash_pin_baru_tidak_pernah_dikirim_ke_frontend(): void
    {
        $p = $this->buatPengajuan();

        $response = $this->actingAs($this->supervisor)->get(route('permintaan-pin.index'));

        $response->assertDontSee($p->pin_baru_hash, false);
        $response->assertDontSee('pin_baru_hash', false);
    }

    public function test_pin_baru_tidak_ada_di_log_maupun_notifikasi(): void
    {
        $p = $this->buatPengajuan();

        $this->actingAs($this->admin)->post(route('permintaan-pin.setujui', $p->id));

        foreach (ActivityLog::all() as $log) {
            $this->assertStringNotContainsString('246810', (string) $log->deskripsi);
        }

        foreach (UserNotification::all() as $notif) {
            $this->assertStringNotContainsString('246810', (string) $notif->pesan);
        }
    }

    public function test_nasabah_model_tidak_menyerahkan_hash_pin(): void
    {
        $array = $this->nasabah->fresh()->toArray();

        $this->assertArrayNotHasKey('pin_keamanan', $array);
    }

    public function test_permintaan_reset_pin_tidak_menyerahkan_hash_pin(): void
    {
        $p = $this->buatPengajuan();

        $this->assertArrayNotHasKey('pin_baru_hash', $p->toArray());
    }

    public function test_teller_tidak_bisa_menyetujui_pengajuan_pin(): void
    {
        $p = $this->buatPengajuan();

        $this->actingAs($this->teller)
            ->get(route('permintaan-pin.index'))
            ->assertForbidden();
    }

    // ---------- Pencegahan persetujuan ganda ----------

    public function test_pengajuan_yang_sudah_disetujui_tidak_bisa_disetujui_lagi(): void
    {
        $p = $this->buatPengajuan();

        $this->actingAs($this->admin)->post(route('permintaan-pin.setujui', $p->id));
        $p->refresh();

        $this->actingAs($this->supervisor)
            ->post(route('permintaan-pin.setujui', $p->id))
            ->assertSessionHas('error');

        $this->assertSame($this->admin->id, $p->fresh()->diproses_oleh, 'Pemroses pertama tidak berubah.');
    }

    public function test_pengajuan_yang_sudah_ditolak_tidak_bisa_disetujui(): void
    {
        $p = $this->buatPengajuan();

        $this->actingAs($this->admin)->post(route('permintaan-pin.tolak', $p->id), ['alasan_penolakan' => 'X']);
        $p->refresh();

        $this->actingAs($this->supervisor)
            ->post(route('permintaan-pin.setujui', $p->id))
            ->assertSessionHas('error');

        $this->assertSame(PermintaanResetPin::STATUS_DITOLAK, $p->fresh()->status);
    }

    public function test_hanya_satu_petugas_berhasil_saat_membuka_pengajuan_yang_sama(): void
    {
        $p = $this->buatPengajuan();

        PermintaanPinService::setujui($p, $this->admin);
        $p->refresh();

        $this->expectException(\RuntimeException::class);
        PermintaanPinService::setujui($p, $this->supervisor);
    }

    // ---------- Dampak ke PIN ----------

    public function test_pin_baru_berlaku_setelah_disetujui(): void
    {
        $p = $this->buatPengajuan();

        $this->actingAs($this->admin)->post(route('permintaan-pin.setujui', $p->id));

        $this->nasabah->refresh();
        $this->assertTrue(Hash::check('246810', $this->nasabah->pin_keamanan));
        $this->assertFalse(Hash::check('123456', $this->nasabah->pin_keamanan));
    }

    public function test_pin_lama_tetap_berlaku_setelah_ditolak(): void
    {
        $p = $this->buatPengajuan();

        $this->actingAs($this->admin)->post(route('permintaan-pin.tolak', $p->id), ['alasan_penolakan' => 'X']);

        $this->nasabah->refresh();
        $this->assertTrue(Hash::check('123456', $this->nasabah->pin_keamanan));
        $this->assertFalse(Hash::check('246810', $this->nasabah->pin_keamanan));
    }

    protected function buatPengajuan(): PermintaanResetPin
    {
        return PermintaanResetPin::create([
            'id_nasabah' => $this->nasabah->id,
            'keterangan' => 'Pengajuan perubahan PIN oleh nasabah.',
            'pin_baru_hash' => Hash::make('246810'),
            'status' => PermintaanResetPin::STATUS_PENDING,
            'diajukan_oleh_role' => 'nasabah',
        ]);
    }
}
