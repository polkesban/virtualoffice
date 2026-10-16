<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Request;

class PengumumanController extends Controller
{
    public function index(Request $request)
    {
        $query = Pengumuman::where('aktif', true)
            ->where('tanggal_mulai', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('tanggal_selesai')
                  ->orWhere('tanggal_selesai', '>=', now()->toDateString());
            })
            ->orderByRaw("FIELD(prioritas, 'urgent', 'penting', 'normal')")
            ->orderBy('tanggal_mulai', 'desc');

        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }

        $pengumuman = $query->paginate(10)->withQueryString();

        return view('pengumuman.index', compact('pengumuman'));
    }
}
