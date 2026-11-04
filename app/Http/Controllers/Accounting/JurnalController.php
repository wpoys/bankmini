<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JurnalAkuntansi;
use App\Models\User;
use App\Support\JurnalExcelExport;
use App\Support\KodeAkun;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JurnalController extends Controller
{
    /**
     * Buku jurnal supervisor/admin.
     *
     * Spesifikasi: filter rentang tanggal + teller WAJIB dipilih —
     * tanpa filter lengkap halaman menampilkan panel "isi filter",
     * bukan data.
     */
    public function index(Request $request): View|Response|RedirectResponse
    {
        $adaParamFilter = $request->hasAny(['mulai', 'sampai', 'teller', 'kode_akun', 'format']);

        $mulai = $request->date('mulai') ?? today()->startOfMonth();
        $sampai = $request->date('sampai') ?? today();
        $tellerTerpilih = null;
        $kodeAkunTerpilih = $request->input('kode_akun');
        $filterLengkap = false;
        $jurnal = collect();
        $totalDebit = 0;
        $totalKredit = 0;
        $tellerLabel = null;

        if ($adaParamFilter) {
            $validated = $request->validate([
                'mulai' => ['required', 'date'],
                'sampai' => ['required', 'date', 'after_or_equal:mulai'],
                'teller' => [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')->where('role', User::ROLE_TELLER),
                ],
            ], [
                'mulai.required' => 'Tanggal mulai wajib diisi.',
                'mulai.date' => 'Tanggal mulai tidak valid.',
                'sampai.required' => 'Tanggal sampai wajib diisi.',
                'sampai.date' => 'Tanggal sampai tidak valid.',
                'sampai.after_or_equal' => 'Tanggal sampai harus setelah atau sama dengan tanggal mulai.',
                'teller.required' => 'Teller wajib dipilih.',
                'teller.exists' => 'Teller yang dipilih tidak valid.',
            ]);

            $mulai = \Illuminate\Support\Carbon::parse($validated['mulai']);
            $sampai = \Illuminate\Support\Carbon::parse($validated['sampai']);
            $tellerTerpilih = (int) $validated['teller'];
            $filterLengkap = true;

            $query = JurnalAkuntansi::with(['transaksi.nasabah', 'transaksi.user'])
                ->whereHas('transaksi', fn ($q) => $q
                    ->whereBetween('tanggal', [$mulai, $sampai])
                    ->where('koreksi_id', null))
                ->whereHas('transaksi', fn ($q) => $q->where('id_user', $tellerTerpilih));

            if ($request->filled('kode_akun')) {
                $query->where('kode_akun', $request->input('kode_akun'));
            }

            $jurnal = $query->orderBy('created_at')->get();

            $totalDebit = (int) $jurnal->where('posisi', 'debit')->sum('jumlah');
            $totalKredit = (int) $jurnal->where('posisi', 'kredit')->sum('jumlah');
            $tellerLabel = (string) User::where('id', $tellerTerpilih)->value('nama_petugas');

            if ($request->input('format') === 'excel') {
                return JurnalExcelExport::download(
                    $jurnal,
                    $mulai,
                    $sampai,
                    $kodeAkunTerpilih,
                    $tellerLabel
                );
            }

            if ($request->input('format') === 'pdf') {
                $pdf = Pdf::loadView('supervisor.laporan.pdf.jurnal', compact('mulai', 'sampai', 'jurnal', 'totalDebit', 'totalKredit', 'tellerLabel'));
                $pdf->setPaper('a4', 'portrait');

                return $pdf->download('jurnal-akuntansi-' . $mulai->format('Y-m-d') . '-' . $sampai->format('Y-m-d') . '.pdf');
            }
        }

        $kodeAkunList = KodeAkun::LIST;
        $tellerList = User::where('role', User::ROLE_TELLER)->orderBy('nama_petugas')->get(['id', 'nama_petugas']);

        return view('supervisor.jurnal', compact(
            'mulai',
            'sampai',
            'jurnal',
            'totalDebit',
            'totalKredit',
            'kodeAkunList',
            'kodeAkunTerpilih',
            'tellerList',
            'tellerTerpilih',
            'filterLengkap'
        ));
    }
}
