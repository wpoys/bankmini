@php
    $isAdmin = $aktor->isAdmin();
    $staticSelf = ! $isAdmin;

    $valueTanggal = $jadwal->tanggal ?? old('tanggal', today()->toDateString());
    $valueMulai = $jadwal?->jam_mulai ? ($jadwal->jam_mulai instanceof \Carbon\CarbonInterface ? $jadwal->jam_mulai->format('H:i') : substr((string) $jadwal->jam_mulai, 0, 5)) : old('jam_mulai', '08:00');
    $valueSelesai = $jadwal?->jam_selesai ? ($jadwal->jam_selesai instanceof \Carbon\CarbonInterface ? $jadwal->jam_selesai->format('H:i') : substr((string) $jadwal->jam_selesai, 0, 5)) : old('jam_selesai', '16:00');

    $valueBagian = old('bagian', $valueBagian ?? $aktor->role);
    $valueUserId = old('user_id', $valueUserId ?? $aktor->id);
@endphp

<div class="row g-3">
    <div class="col-lg-8">
        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>{{ $jadwal ? 'Formulir Ubah Jadwal' : 'Formulir Jadwal Baru' }}</div>
            </div>
            <div class="panel-body p-3">
                @if ($errors->any())
                    <div class="alert alert-danger small py-2">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($isAdmin)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bagian / Role</label>
                        <select name="role_picker" id="rolePicker" class="form-select">
                            @foreach ($roleOptions as $role)
                                <option value="{{ $role }}" @selected($valueBagian === $role)>{{ \App\Models\User::ROLE_OPTION[$role] ?? ucfirst($role) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($isAdmin)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Petugas</label>
                        <select name="user_id" id="userSelect" class="form-select">
                            <option value="">-- Pilih Petugas --</option>
                            @foreach ($usersByRole as $role => $users)
                                <optgroup label="{{ \App\Models\User::ROLE_OPTION[$role] ?? ucfirst($role) }}" data-role="{{ $role }}">
                                    @foreach ($users as $u)
                                        <option value="{{ $u->id }}" data-role="{{ $role }}" @selected((int) $valueUserId === $u->id)>{{ $u->nama_petugas }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="bagian" id="bagianHidden" value="{{ $valueBagian }}">
                @endif

                @if ($staticSelf)
                    <div class="alert alert-info small py-2">
                        <i class="fa-solid fa-circle-info me-1"></i>Jadwal ini tercatat untuk <strong>{{ $aktor->nama_petugas }}</strong> ({{ $aktor->role_label }}).
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Tanggal</label>
                        <input type="date" name="tanggal" value="{{ $valueTanggal }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Jam Mulai</label>
                        <input type="time" name="jam_mulai" value="{{ $valueMulai }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Jam Selesai</label>
                        <input type="time" name="jam_selesai" value="{{ $valueSelesai }}" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Keterangan</label>
                        <textarea name="keterangan" rows="2" class="form-control" placeholder="contoh: Loket pagi, layanan 2 Course">{{ old('keterangan', $jadwal->keterangan ?? '') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="panel-foot p-3 d-flex justify-content-end gap-2">
                <a href="{{ route($prefix.'.jadwal.index') }}" class="btn btn-outline-secondary btn-sm">Batal</a>
                <button class="btn btn-primary btn-sm"><i class="fa-solid fa-check me-1"></i>{{ $jadwal ? 'Simpan Perubahan' : 'Simpan Jadwal' }}</button>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot" style="background:var(--purple)"></span>Ketentuan</div>
            </div>
            <div class="panel-body p-3 small">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Jam selesai harus setelah jam mulai.</li>
                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Petugas tidak boleh bentrok pada rentang jam yang sama di tanggal yang sama.</li>
                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Hari dihitung otomatis dari tanggal.</li>
                    <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i>Jadwal dapat dibatalkan selama masih berstatus Dijadwalkan.
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if ($isAdmin)
                const rolePicker = document.getElementById('rolePicker');
                const userSelect = document.getElementById('userSelect');
                const bagianHidden = document.getElementById('bagianHidden');

                function renderUsers(role) {
                    userSelect.value = '';
                    userSelect.querySelectorAll('optgroup').forEach(g => {
                        const show = g.dataset.role === role;
                        g.style.display = show ? '' : 'none';
                        if (!show) {
                            g.querySelectorAll('option').forEach(o => o.disabled = true);
                        } else {
                            g.querySelectorAll('option').forEach(o => o.disabled = false);
                        }
                    });
                    bagianHidden.value = role;
                }

                rolePicker.addEventListener('change', () => renderUsers(rolePicker.value));
                renderUsers(rolePicker.value);
            @endif
        });
    </script>
@endpush
