<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use App\Models\TestimoniAlumni;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PojokAlumniController extends Controller
{
    public function index(): View
    {
        $programStudi = ProgramStudi::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get(['nama']);

        $testimoni = TestimoniAlumni::query()
            ->where('status', 'published')
            ->latest()
            ->limit(6)
            ->get();

        return view('pojok-alumni.index', compact('programStudi', 'testimoni'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:120',
            'email' => 'required|email|max:180',
            'telepon' => 'nullable|string|max:30|regex:/^[0-9+\-\s()]{6,30}$/',
            'program_studi' => 'required|string|max:160',
            'tahun_lulus' => 'required|integer|min:1970|max:' . now()->year,
            'instansi' => 'nullable|string|max:180',
            'jabatan' => 'nullable|string|max:180',
            'kota_domisili' => 'nullable|string|max:120',
            'portofolio_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'testimoni' => 'required|string|min:20|max:3000',
            'bersedia_dihubungi' => 'nullable|boolean',
        ]);

        $validated['bersedia_dihubungi'] = $request->boolean('bersedia_dihubungi');
        $validated['ip_address'] = $request->ip();
        $validated['status'] = 'pending';

        TestimoniAlumni::create($validated);

        return redirect()
            ->route('pojok-alumni')
            ->with('success', 'Terima kasih. Testimoni alumni Anda sudah kami terima untuk kebutuhan tracer study dan kurasi publik.');
    }
}