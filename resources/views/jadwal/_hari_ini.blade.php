@php
    $jadwalRoute ??= null;
    $linkLabel ??= 'Lihat Semua';
@endphp
<div class="data-panel mb-3">
    <div class="panel-head">
        <div class="panel-title"><span class="dot" style="background:var(--purple)"></span>Jadwal Petugas Hari Ini</div>
        @if ($jadwalRoute)
            <a href="{{ $jadwalRoute }}" class="small fw-semibold text-primary">{{ $linkLabel }}</a>
        @endif
    </div>
    <div class="panel-body">
        @forelse ($jadwalList as $j)
            <div class="d-flex align-items-center justify-content-between px-3 py-2" style="border-bottom:1px solid var(--border);">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar rounded-circle" style="width:30px;height:30px;font-size:.7rem;">
                        {{ strtoupper(substr($j->nama_petugas, 0, 1)) }}
                    </div>
                    <div>
                        <div class="fw-semibold small">{{ $j->nama_petugas }}</div>
                        <div class="text-muted" style="font-size:.68rem;">{{ $j->bagian }}</div>
                    </div>
                </div>
                <div class="text-end">
                    <div class="small fw-semibold">{{ $j->jam_label }}</div>
                    <span class="status-badge orange">{{ $j->status_label }}</span>
                </div>
            </div>
        @empty
            <p class="text-muted small px-3 py-3 mb-0">Belum ada jadwal petugas hari ini.</p>
        @endforelse
    </div>
</div>
