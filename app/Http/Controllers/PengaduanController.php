<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PengaduanController extends Controller
{
    /**
     * Show the public complaint form.
     */
    public function create()
    {
        $tujuanOptions = Pengaduan::tujuanOptions();

        return view('pengaduan.create', compact('tujuanOptions'));
    }

    /**
     * Store a new complaint/feedback.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'            => 'required|string|max:100',
            'email'           => 'nullable|email|max:255',
            'telepon'         => 'nullable|string|max:30',
            'tujuan_unit'     => 'required|string|max:100',
            'tupoksi_service' => 'required|string|max:150',
            'jenis'           => 'required|in:pengaduan,kritik,saran,keluhan',
            'subjek'          => 'required|string|max:200',
            'isi'             => 'required|string|min:10|max:5000',
            'anonim'          => 'sometimes|boolean',
            'file_lampiran'   => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip|max:5120',
        ]);

        // Validate tujuan_unit is a valid key
        $validKeys = collect(Pengaduan::tujuanOptions())->pluck('key')->all();
        if (! in_array($validated['tujuan_unit'], $validKeys, true)) {
            return back()->withErrors(['tujuan_unit' => 'Tujuan tidak valid.'])->withInput();
        }

        // Resolve label
        $tujuanLabel = collect(Pengaduan::tujuanOptions())
            ->firstWhere('key', $validated['tujuan_unit'])['label'] ?? $validated['tujuan_unit'];

        $file_lampiran = null;
        if ($request->hasFile('file_lampiran')) {
            $file_lampiran = \App\Support\MediaStorage::storeUploadedFile($request->file('file_lampiran'), 'pengaduan');
        }

        $isAnonim = (bool) ($validated['anonim'] ?? false);
        $fullSubjek = '[' . $validated['tupoksi_service'] . '] ' . $validated['subjek'];

        $pengaduan = Pengaduan::create([
            'nama'         => $isAnonim ? 'Anonim' : $validated['nama'],
            'email'        => $isAnonim ? null : ($validated['email'] ?? null),
            'telepon'      => $isAnonim ? null : ($validated['telepon'] ?? null),
            'tujuan_unit'  => $validated['tujuan_unit'],
            'tujuan_label' => $tujuanLabel,
            'jenis'        => $validated['jenis'],
            'subjek'       => $fullSubjek,
            'isi'          => $validated['isi'],
            'file_lampiran'=> $file_lampiran,
            'anonim'       => $isAnonim,
            'ip_address'   => $request->ip(),
            'nomor_tiket'  => Pengaduan::generateNomorTiket(),
            'status'       => 'masuk',
        ]);

        return redirect()
            ->route('pengaduan.sukses', ['tiket' => $pengaduan->nomor_tiket])
            ->with('success', 'Terima kasih! Pengaduan/saran Anda telah kami terima.');
    }

    /**
     * Show success page with ticket number.
     */
    public function sukses(Request $request)
    {
        $tiket = $request->query('tiket');

        return view('pengaduan.sukses', compact('tiket'));
    }
}
