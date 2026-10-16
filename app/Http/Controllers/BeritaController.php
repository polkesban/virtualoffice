<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use Illuminate\Http\Request;

class BeritaController extends Controller
{
    public function index(Request $request)
    {
        $kategori = $request->query('kategori');

        $query = Berita::published()->terbaru()->with('penulis');

        if ($kategori && in_array($kategori, ['berita', 'kegiatan', 'pengumuman', 'prestasi'])) {
            $query->kategori($kategori);
        }

        $beritas = $query->paginate(9)->withQueryString();

        return view('berita.index', compact('beritas', 'kategori'));
    }

    public function show(string $slug)
    {
        $berita = Berita::published()
            ->where('slug', $slug)
            ->with('penulis')
            ->firstOrFail();

        Berita::where('id', $berita->id)->increment('views');

        $terkait = Berita::published()
            ->terbaru()
            ->kategori($berita->kategori)
            ->where('id', '!=', $berita->id)
            ->limit(3)
            ->get();

        return view('berita.show', compact('berita', 'terkait'));
    }
}
