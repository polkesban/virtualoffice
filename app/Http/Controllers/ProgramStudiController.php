<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;

class ProgramStudiController extends Controller
{
    public function index()
    {
        $allProdis = ProgramStudi::with('jurusan')->aktif()->get();
        $prodis = $allProdis->groupBy('jenjang');

        $jurusans = \App\Models\Jurusan::with(['programStudis' => function ($q) {
            $q->where('aktif', true);
        }])->where('aktif', true)->orderBy('urutan')->get();

        $unassignedProdis = $allProdis->filter(fn ($p) => ! $p->jurusan_id);

        return view('program-studi.index', compact('prodis', 'jurusans', 'unassignedProdis'));
    }

    public function show(string $slug)
    {
        $prodi = ProgramStudi::with(['unitPage.sections' => function ($q) {
                    $q->where('aktif', true);
                }])
            ->where('slug', $slug)
            ->where('aktif', true)
            ->firstOrFail();

        $lainnya = ProgramStudi::aktif()
            ->where('id', '!=', $prodi->id)
            ->limit(4)
            ->get();

        return view('program-studi.show', compact('prodi', 'lainnya'));
    }
}
