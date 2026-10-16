<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\ProgramStudi;
use App\Models\UnitPage;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $beritaList = Berita::published()
            ->select('slug', 'updated_at')
            ->orderByDesc('published_at')
            ->get();

        $programStudiList = ProgramStudi::aktif()
            ->select('slug', 'updated_at')
            ->orderBy('urutan')
            ->get();

        $unitPageList = UnitPage::query()
            ->where('aktif', true)
            ->whereDoesntHave('programStudi')
            ->select('slug', 'updated_at', 'role')
            ->orderBy('judul')
            ->get();

        $managedSectionUrls = [
            '/galeri',
            '/agenda',
            '/mahasiswa-berprestasi',
            '/layanan-bisnis',
            '/layanan-lab',
            '/fasilitas-asrama',
            '/koleksi-perpustakaan',
            '/publikasi',
            '/dokumen-mutu',
            '/layanan-ti',
            '/pelatihan',
            '/unit',
            '/portal-mahasiswa',
            '/pojok-alumni',
        ];

        $content = view('sitemap', compact('beritaList', 'programStudiList', 'unitPageList', 'managedSectionUrls'))->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
