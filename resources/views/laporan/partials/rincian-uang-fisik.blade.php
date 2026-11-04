<div class="data-panel mb-3">
    <div class="panel-head">
        <div class="panel-title"><span class="dot" style="background:var(--purple)"></span>Rincian Lembaran Uang Fisik</div>
        <span class="small text-muted">
            {{ $laporan->saldo_akhir_fisik !== null ? 'Rp '.number_format($laporan->saldo_akhir_fisik, 0, ',', '.') : 'Belum diisi' }}
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Nominal</th>
                    <th class="text-end">Jumlah Lembar</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($laporan->rekonsiliasi?->detail ?? [] as $detail)
                    <tr>
                        <td class="num small">Rp {{ number_format($detail->pecahan, 0, ',', '.') }}</td>
                        <td class="text-end small">{{ $detail->jumlah_lembar }} lembar</td>
                        <td class="num text-end small">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="small text-muted text-center py-2">
                            Rincian pecahan belum disimpan; total uang fisik:
                            {{ $laporan->saldo_akhir_fisik !== null ? 'Rp '.number_format($laporan->saldo_akhir_fisik, 0, ',', '.') : '-' }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="small text-end">TOTAL UANG FISIK</th>
                    <th class="num text-end small">{{ $laporan->saldo_akhir_fisik !== null ? 'Rp '.number_format($laporan->saldo_akhir_fisik, 0, ',', '.') : '-' }}</th>
                </tr>
                <tr>
                    <th colspan="2" class="small text-end">SELISIH</th>
                    <th class="num text-end small {{ $laporan->selisih == 0 ? 'text-success' : 'text-danger' }}">{{ $laporan->selisih !== null ? 'Rp '.number_format($laporan->selisih, 0, ',', '.') : '-' }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
