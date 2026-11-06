@extends('layouts.app')

@section('title', 'Pengajuan Koreksi')
@section('page-title', 'Pengajuan Koreksi')
@section('page-subtitle', 'Ajukan koreksi atas transaksi yang keliru')

@section('list-page-class', 'list-page')
@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Pengajuan Koreksi</h1>
                <div class="page-header-sub">Koreksi atas transaksi yang keliru di loket Anda</div>
            </div>
        </div>

        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Daftar Pengajuan</div>
                <div class="table-toolbar">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" class="form-control form-control-sm" data-table-filter placeholder="Cari struk / nasabah / alasan...">
                    </div>
                    <select class="form-select form-select-sm d-inline-block" style="width:auto;" data-table-filter>
                        <option value="">Semua Status</option>
                        <option value="menunggu_approval">Menunggu</option>
                        <option value="disetujui">Disetujui</option>
                        <option value="ditolak">Ditolak</option>
                    </select>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAjukanKoreksi">
                        <i class="fa-solid fa-plus me-1"></i>Ajukan Koreksi
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap koreksi-index-table mb-0" data-filterable>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Transaksi</th>
                            <th>Nasabah</th>
                            <th>Jenis Asli</th>
                            <th class="text-end">Nominal</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($koreksi as $k)
                            @php $t = $k->transaksi; @endphp
                            <tr data-search="{{ $t?->no_struk }} {{ $k->nasabah->nama_siswa }} {{ $k->alasan }}" data-status="{{ $k->status }}">
                                <td class="small text-muted">{{ $k->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    @if ($t)
                                        <code class="small">{{ $t->no_struk }}</code>
                                    @else
                                        <span class="badge badge-soft-warning">Tanpa asli (temuan)</span>
                                    @endif
                                </td>
                                <td class="koreksi-nasabah-cell">
                                    <span class="koreksi-nasabah-nama">{{ $k->nasabah->nama_siswa }}</span>
                                    @if ($k->jenis_label)
                                        <span class="badge badge-soft-info">{{ $k->jenis_label }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($t)
                                        <span class="badge badge-soft-{{ $t->jenis_trans === 'setoran' ? 'success' : 'danger' }}">{{ $t->jenis_label }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="num fw-semibold text-end small">Rp {{ number_format($k->nominal_efektif, 0, ',', '.') }}</td>
                                <td class="small text-muted" style="max-width:260px;">
                                    <span class="d-inline-block text-truncate w-100" title="{{ $k->alasan }}">{{ $k->alasan }}</span>
                                </td>
                                <td>
                                    @php
                                        $badge = match ($k->status) {
                                            'menunggu_approval' => 'warning',
                                            'disetujui' => 'success',
                                            'ditolak' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge badge-soft-{{ $badge }}">{{ $k->status_label }}</span>
                                </td>
                                <td>
                                    @if ($t)
                                        @include('transaksi.partials.aksi', ['transaksi' => $t, 'strukDalamModal' => true])
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-rotate-left"></i></div>
                                        <div class="empty-title">Belum ada pengajuan koreksi</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($koreksi->hasPages())
                <div class="p-2 border-top">{{ $koreksi->links() }}</div>
            @endif
        </div>
    </div>

    <div class="modal fade struk-koreksi-modal" id="modalStrukKoreksi" tabindex="-1" aria-labelledby="modalStrukKoreksiLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h2 class="modal-title fs-6" id="modalStrukKoreksiLabel"><i class="fa-solid fa-receipt me-2 text-primary"></i>Struk Transaksi</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-0"><iframe id="iframeStrukKoreksi" title="Struk transaksi" loading="lazy"></iframe></div>
            </div>
        </div>
    </div>

    <div class="modal fade koreksi-request-modal" id="modalAjukanKoreksi" tabindex="-1" aria-labelledby="modalAjukanKoreksiLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <h2 class="visually-hidden" id="modalAjukanKoreksiLabel">Ajukan Koreksi Transaksi</h2>
                <div class="modal-body p-0">
                    <iframe id="iframeAjukanKoreksi" src="{{ route('teller.koreksi.create', ['modal' => 1]) }}" title="Form Pengajuan Koreksi" class="w-100 border-0"></iframe>
                </div>
            </div>
        </div>
    </div>
    <script>
        (() => {
            const receiptModalElement = document.getElementById('modalStrukKoreksi');
            const receiptFrame = document.getElementById('iframeStrukKoreksi');

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-struk-modal]');
                if (!trigger) return;
                receiptFrame.src = trigger.dataset.strukModal;
                bootstrap.Modal.getOrCreateInstance(receiptModalElement).show();
            });

            receiptModalElement.addEventListener('hidden.bs.modal', () => {
                receiptFrame.src = 'about:blank';
            });

            window.addEventListener('message', (event) => {
                if (event.origin !== window.location.origin || event.data !== 'tutup-struk-modal') return;
                bootstrap.Modal.getOrCreateInstance(receiptModalElement).hide();
            });
        })();
    </script>
    <script>
        (() => {
            const modalElement = document.getElementById('modalAjukanKoreksi');
            const iframe = document.getElementById('iframeAjukanKoreksi');
            let submitting = false;

            modalElement.addEventListener('show.bs.modal', () => {
                submitting = false;
                iframe.src = '{{ route('teller.koreksi.create', ['modal' => 1]) }}';
            });

            iframe.addEventListener('load', () => {
                if (!submitting) return;

                try {
                    const currentUrl = new URL(iframe.contentWindow.location.href);
                    const successPath = new URL('{{ route('teller.koreksi.index') }}').pathname;

                    if (currentUrl.pathname === successPath) {
                        submitting = false;
                        bootstrap.Modal.getOrCreateInstance(modalElement).hide();
                        window.location.reload();
                    }
                } catch (error) {
                    submitting = false;
                }
            });

            iframe.addEventListener('load', () => {
                try {
                    const form = iframe.contentDocument.getElementById('formKoreksi');
                    if (!form || form.dataset.parentSubmitBound) return;
                    form.dataset.parentSubmitBound = 'true';
                    form.addEventListener('submit', () => {
                        submitting = true;
                    });
                } catch (error) {
                    submitting = false;
                }
            });
        })();
    </script>
    <style>
        .koreksi-index-table tbody tr {
            height: 58px;
        }

        .koreksi-index-table tbody td {
            height: 58px;
            padding-top: 8px;
            padding-bottom: 8px;
            vertical-align: middle !important;
        }

        .koreksi-index-table .koreksi-nasabah-cell {
            min-width: 135px;
        }

        .koreksi-index-table .koreksi-nasabah-nama {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            font-weight: 600;
        }

        .koreksi-index-table .koreksi-nasabah-cell .badge {
            margin-left: 5px;
            vertical-align: middle;
        }

        .struk-koreksi-modal .modal-dialog { width: min(620px, calc(100% - 24px)); max-width: 620px; }
        .struk-koreksi-modal .modal-content { overflow: hidden; border: 0; border-radius: 12px; box-shadow: var(--shadow-md); }
        .struk-koreksi-modal .modal-body { height: min(620px, calc(100vh - 120px)); background: var(--page); }
        .struk-koreksi-modal iframe { display: block; width: 100%; height: 100%; border: 0; }

        .koreksi-request-modal .modal-dialog { width:min(430px, calc(100% - 20px)); max-width:430px; }
        .koreksi-request-modal .modal-content { height:min(560px, calc(100vh - 24px)); overflow:hidden; border-radius:12px; }
        .koreksi-request-modal .modal-body { height:100%; overflow:hidden; }
        .koreksi-request-modal iframe { display:block; height:100%; }
    </style>
@endsection
