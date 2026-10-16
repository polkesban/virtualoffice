<?php

namespace App\Http\Controllers;

use App\Models\PesanKontak;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KontakController extends Controller
{
    public function index(): View
    {
        return view('kontak.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama'    => 'required|string|max:100',
            'email'   => 'required|email|max:255',
            'telepon' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]{6,20}$/',
            'topik'   => 'required|in:umum,pendaftaran,akademik,kerjasama,lainnya',
            'subjek'  => 'required|string|max:200',
            'pesan'   => 'required|string|max:2000|min:10',
        ]);

        $validated['ip_address'] = $request->ip();
        PesanKontak::create($validated);

        return redirect()
            ->route('kontak')
            ->with('success', 'Pesan Anda telah terkirim. Kami akan merespons dalam 1-2 hari kerja.');
    }
}
