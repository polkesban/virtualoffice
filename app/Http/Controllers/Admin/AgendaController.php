<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function index(Request $request)
    {
        $q = Agenda::with('creator')->orderBy('tanggal_mulai', 'desc');

        if ($request->filled('kategori')) {
            $q->where('kategori', $request->kategori);
        }
        if ($request->filled('bulan')) {
            [$y, $m] = explode('-', $request->bulan);
            $q->whereYear('tanggal_mulai', $y)->whereMonth('tanggal_mulai', $m);
        }

        $agenda = $q->get();

        return view('admin.agenda.index', compact('agenda'));
    }

    public function create()
    {
        $agenda = new Agenda();
        return view('admin.agenda.form', compact('agenda'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'judul'           => 'required|string|max:255',
            'deskripsi'       => 'nullable|string',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'waktu_mulai'     => 'nullable|string|max:10',
            'waktu_selesai'   => 'nullable|string|max:10',
            'lokasi'          => 'nullable|string|max:255',
            'kategori'        => 'required|string|max:30',
            'warna'           => 'required|string|max:20',
            'publik'          => 'boolean',
        ]);

        $data['publik']     = $request->boolean('publik');
        $data['created_by'] = auth()->id();

        Agenda::create($data);

        return redirect()->route('admin.agenda.index')
            ->with('success', 'Agenda berhasil ditambahkan.');
    }

    public function edit(Agenda $agenda)
    {
        return view('admin.agenda.form', compact('agenda'));
    }

    public function update(Request $request, Agenda $agenda)
    {
        $data = $request->validate([
            'judul'           => 'required|string|max:255',
            'deskripsi'       => 'nullable|string',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'waktu_mulai'     => 'nullable|string|max:10',
            'waktu_selesai'   => 'nullable|string|max:10',
            'lokasi'          => 'nullable|string|max:255',
            'kategori'        => 'required|string|max:30',
            'warna'           => 'required|string|max:20',
            'publik'          => 'boolean',
        ]);

        $data['publik'] = $request->boolean('publik');

        $agenda->update($data);

        return redirect()->route('admin.agenda.index')
            ->with('success', 'Agenda berhasil diperbarui.');
    }

    public function destroy(Agenda $agenda)
    {
        $agenda->delete();
        return back()->with('success', 'Agenda dihapus.');
    }
}
