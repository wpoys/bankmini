<div class="data-panel mb-3">
    <div class="panel-head">
        <div class="panel-title"><span class="dot" style="background:var(--blue)"></span>Rincian Lembaran Saldo Awal</div>
        <span class="small text-muted">Rp {{ number_format($laporan->saldo_awal_kas, 0, ',', '.') }}</span>
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
                @php
                    $sisaSaldoAwal = (int) $laporan->saldo_awal_kas;
                    $pecahanSaldoAwal = [100000, 50000, 20000, 10000, 5000, 2000, 1000, 500, 100];
                @endphp
                @foreach ($pecahanSaldoAwal as $pecahan)
                    @php
                        $jumlahLembar = intdiv($sisaSaldoAwal, $pecahan);
                        $subtotal = $jumlahLembar * $pecahan;
                        $sisaSaldoAwal -= $subtotal;
                    @endphp
                    @if ($jumlahLembar > 0)
                        <tr>
                            <td class="num small">Rp {{ number_format($pecahan, 0, ',', '.') }}</td>
                            <td class="text-end small">{{ $jumlahLembar }} lembar</td>
                            <td class="num text-end small">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                @endforeach
                @if ((int) $laporan->saldo_awal_kas === 0)
                    <tr><td colspan="3" class="small text-muted text-center py-2">Tidak ada saldo awal.</td></tr>
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="small text-end">TOTAL SALDO AWAL</th>
                    <th class="num text-end small">Rp {{ number_format($laporan->saldo_awal_kas, 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
