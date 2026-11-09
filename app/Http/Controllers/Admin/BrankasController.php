<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BrankasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BrankasController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'saldo' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ]);

        BrankasService::aturSaldoAwal((int) $validated['saldo'], (int) $request->user()->id);

        return redirect()->route('admin.dashboard')->with('success', 'Saldo brankas berhasil diperbarui.');
    }
}
