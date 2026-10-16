<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesUnitPageAccess;
use App\Http\Controllers\Controller;
use App\Models\UnitEvent;
use App\Models\UnitPage;
use App\Support\MediaStorage;
use App\Support\QrCodeDataUri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UnitEventController extends Controller
{
    use AuthorizesUnitPageAccess;

    public function index(UnitPage $unitPage): View
    {
        $this->authorizeUnitPage($unitPage);

        $events = $unitPage->events()
            ->withCount(['participants', 'materials', 'questions'])
            ->orderByDesc('mulai_at')
            ->get();

        return view('admin.unit-events.index', compact('unitPage', 'events'));
    }

    public function create(UnitPage $unitPage): View
    {
        $this->authorizeUnitPage($unitPage);

        $unitEvent = new UnitEvent([
            'jenis_kegiatan' => 'webinar',
            'metode' => 'online',
            'status' => 'published',
            'flyer_theme' => 'modern_teal',
            'registrasi_aktif' => true,
            'daftar_hadir_aktif' => true,
        ]);

        return view('admin.unit-events.form', compact('unitPage', 'unitEvent'));
    }

    public function store(Request $request, UnitPage $unitPage): RedirectResponse
    {
        $this->authorizeUnitPage($unitPage);

        $data = $this->validatedData($request);
        $data['unit_page_id'] = $unitPage->id;
        $data['created_by'] = auth()->id();
        $slugBase = !empty($data['slug']) ? $data['slug'] : ($data['judul'] ?? 'event');
        $data['slug'] = $this->uniqueSlug($slugBase);

        if ($request->hasFile('flyer')) {
            $data['flyer_path'] = MediaStorage::storeUploadedFile($request->file('flyer'), 'unit-events/flyers');
        }

        if ($request->hasFile('direktur_foto')) {
            $data['direktur_foto_path'] = MediaStorage::storeUploadedFile($request->file('direktur_foto'), 'unit-events/directors');
        }

        if ($request->hasFile('surat_tugas_event_file')) {
            $data['surat_tugas_event_path'] = MediaStorage::storeUploadedFile($request->file('surat_tugas_event_file'), 'unit-events/event-assignment-letters');
            $data['surat_tugas_event_uploaded_at'] = now();
        }

        if ($request->hasFile('penandatangan_signature_file')) {
            $data['penandatangan_signature_path'] = MediaStorage::storeUploadedFile($request->file('penandatangan_signature_file'), 'unit-events/signatures');
        } elseif ($request->filled('penandatangan_signature_data')) {
            $sigData = (string) $request->input('penandatangan_signature_data');
            if (preg_match('#^data:image/(\w+);base64,(.+)$#i', $sigData, $matches)) {
                $binary = base64_decode($matches[2]);
                $path = sprintf('unit-events/signatures/%d/penanggungjawab-%s.png', time(), Str::random(10));
                $data['penandatangan_signature_path'] = MediaStorage::put($path, $binary, 'image/png');
            }
        }

        $unitEvent = UnitEvent::create($data);

        return redirect()->route('admin.unit-events.edit', [$unitPage, $unitEvent])
            ->with('success', 'Event unit berhasil dibuat. Lanjutkan dengan mengisi materi, peserta, dan tes sesuai kebutuhan.');
    }

    public function edit(UnitPage $unitPage, UnitEvent $unitEvent): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        return view('admin.unit-events.form', compact('unitPage', 'unitEvent'));
    }

    public function flyerPreview(UnitPage $unitPage, UnitEvent $unitEvent, QrCodeDataUri $qrCode): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $speakers = $unitEvent->speakers()->get();
        $theme = $unitEvent->resolvedFlyerThemeConfig();
        $benefits = $unitEvent->resolvedBenefitsList();
        $publicUrl = route('unit.events.show', [$unitPage->slug, $unitEvent->slug]);
        $qrCodeDataUri = $qrCode->make($publicUrl, 6);

        return view('unit.events.flyer', [
            'page' => $unitPage,
            'event' => $unitEvent,
            'speakers' => $speakers,
            'theme' => $theme,
            'benefits' => $benefits,
            'publicUrl' => $publicUrl,
            'qrCodeDataUri' => $qrCodeDataUri,
            'isAdminPreview' => true,
        ]);
    }

    public function update(Request $request, UnitPage $unitPage, UnitEvent $unitEvent): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $data = $this->validatedData($request, $unitEvent);
        $slugBase = !empty($data['slug']) ? $data['slug'] : ($data['judul'] ?? $unitEvent->judul);
        $data['slug'] = $this->uniqueSlug($slugBase, $unitEvent->id);

        if ($request->hasFile('flyer')) {
            MediaStorage::delete($unitEvent->flyer_path);
            $data['flyer_path'] = MediaStorage::storeUploadedFile($request->file('flyer'), 'unit-events/flyers');
        }

        if ($request->boolean('hapus_direktur_foto') && $unitEvent->direktur_foto_path) {
            MediaStorage::delete($unitEvent->direktur_foto_path);
            $data['direktur_foto_path'] = null;
        }

        if ($request->hasFile('direktur_foto')) {
            MediaStorage::delete($unitEvent->direktur_foto_path);
            $data['direktur_foto_path'] = MediaStorage::storeUploadedFile($request->file('direktur_foto'), 'unit-events/directors');
        }

        if ($request->boolean('hapus_surat_tugas_event') && $unitEvent->surat_tugas_event_path) {
            MediaStorage::delete($unitEvent->surat_tugas_event_path);
            $data['surat_tugas_event_path'] = null;
            $data['surat_tugas_event_uploaded_at'] = null;
        }

        if ($request->hasFile('surat_tugas_event_file')) {
            MediaStorage::delete($unitEvent->surat_tugas_event_path);
            $data['surat_tugas_event_path'] = MediaStorage::storeUploadedFile($request->file('surat_tugas_event_file'), 'unit-events/event-assignment-letters');
            $data['surat_tugas_event_uploaded_at'] = now();
        }

        if ($request->boolean('hapus_penandatangan_signature') && $unitEvent->penandatangan_signature_path) {
            MediaStorage::delete($unitEvent->penandatangan_signature_path);
            $data['penandatangan_signature_path'] = null;
        }

        if ($request->hasFile('penandatangan_signature_file')) {
            MediaStorage::delete($unitEvent->penandatangan_signature_path);
            $data['penandatangan_signature_path'] = MediaStorage::storeUploadedFile($request->file('penandatangan_signature_file'), 'unit-events/signatures');
        } elseif ($request->filled('penandatangan_signature_data')) {
            $sigData = (string) $request->input('penandatangan_signature_data');
            if (preg_match('#^data:image/(\w+);base64,(.+)$#i', $sigData, $matches)) {
                MediaStorage::delete($unitEvent->penandatangan_signature_path);
                $binary = base64_decode($matches[2]);
                $path = sprintf('unit-events/signatures/%d/penanggungjawab-%s.png', $unitEvent->id, Str::random(10));
                $data['penandatangan_signature_path'] = MediaStorage::put($path, $binary, 'image/png');
            }
        }

        unset(
            $data['flyer'],
            $data['direktur_foto'],
            $data['hapus_direktur_foto'],
            $data['surat_tugas_event_file'],
            $data['hapus_surat_tugas_event'],
            $data['penandatangan_signature_file'],
            $data['penandatangan_signature_data'],
            $data['hapus_penandatangan_signature']
        );

        $unitEvent->update($data);

        return redirect()->route('admin.unit-events.edit', [$unitPage, $unitEvent])
            ->with('success', 'Event unit berhasil diperbarui.');
    }

    public function signatureForm(UnitPage $unitPage, UnitEvent $unitEvent): View
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        return view('admin.unit-events.signature', compact('unitPage', 'unitEvent'));
    }

    public function signatureStore(Request $request, UnitPage $unitPage, UnitEvent $unitEvent): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        $request->validate([
            'penandatangan_jabatan' => 'nullable|string|max:255',
            'penandatangan_nama' => 'nullable|string|max:255',
            'penandatangan_nip' => 'nullable|string|max:50',
            'penandatangan_signature_data' => 'nullable|string',
            'penandatangan_signature_file' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:4096',
        ]);

        $updateData = [];
        if ($request->has('penandatangan_jabatan')) {
            $updateData['penandatangan_jabatan'] = $request->input('penandatangan_jabatan');
        }
        if ($request->has('penandatangan_nama')) {
            $updateData['penandatangan_nama'] = $request->input('penandatangan_nama');
        }
        if ($request->has('penandatangan_nip')) {
            $updateData['penandatangan_nip'] = $request->input('penandatangan_nip');
        }

        if ($request->hasFile('penandatangan_signature_file')) {
            MediaStorage::delete($unitEvent->penandatangan_signature_path);
            $updateData['penandatangan_signature_path'] = MediaStorage::storeUploadedFile($request->file('penandatangan_signature_file'), 'unit-events/signatures');
        } elseif ($request->filled('penandatangan_signature_data')) {
            $sigData = (string) $request->input('penandatangan_signature_data');
            if (preg_match('#^data:image/(\w+);base64,(.+)$#i', $sigData, $matches)) {
                MediaStorage::delete($unitEvent->penandatangan_signature_path);
                $binary = base64_decode($matches[2]);
                $path = sprintf('unit-events/signatures/%d/penanggungjawab-%s.png', $unitEvent->id, Str::random(10));
                $updateData['penandatangan_signature_path'] = MediaStorage::put($path, $binary, 'image/png');
            }
        }

        if (!empty($updateData)) {
            $unitEvent->update($updateData);
        }

        return redirect()->route('admin.unit-events.signature', [$unitPage, $unitEvent])
            ->with('success', 'Tanda tangan penanggung jawab berhasil disimpan dan langsung diterapkan ke seluruh dokumen kegiatan.');
    }

    public function signatureDestroy(UnitPage $unitPage, UnitEvent $unitEvent): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        if ($unitEvent->penandatangan_signature_path) {
            MediaStorage::delete($unitEvent->penandatangan_signature_path);
            $unitEvent->update(['penandatangan_signature_path' => null]);
        }

        return redirect()->route('admin.unit-events.signature', [$unitPage, $unitEvent])
            ->with('success', 'Tanda tangan penanggung jawab berhasil dihapus dari seluruh dokumen kegiatan.');
    }

    public function destroy(UnitPage $unitPage, UnitEvent $unitEvent): RedirectResponse
    {
        $this->authorizeUnitEvent($unitPage, $unitEvent);

        MediaStorage::delete($unitEvent->flyer_path);
        MediaStorage::delete($unitEvent->direktur_foto_path);
        MediaStorage::delete($unitEvent->surat_tugas_event_path);
        MediaStorage::delete($unitEvent->penandatangan_signature_path);

        foreach ($unitEvent->materials as $material) {
            MediaStorage::delete($material->file_path);
        }

        foreach ($unitEvent->participants as $participant) {
            MediaStorage::delete($participant->sertifikat_path);
            MediaStorage::delete($participant->surat_tugas_path);

            foreach ($participant->attendances as $attendance) {
                MediaStorage::delete($attendance->signature_path);
            }
        }

        foreach ($unitEvent->photos as $photo) {
            MediaStorage::delete($photo->image_path);
        }

        $unitEvent->delete();

        return redirect()->route('admin.unit-events.index', $unitPage)
            ->with('success', 'Event unit berhasil dihapus.');
    }

    private function validatedData(Request $request, ?UnitEvent $unitEvent = null): array
    {
        $data = $request->validate([
            'judul' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'ringkasan' => 'nullable|string|max:1000',
            'deskripsi' => 'nullable|string',
            'jenis_kegiatan' => 'required|string|in:' . implode(',', array_keys(UnitEvent::$jenisLabels)),
            'sub_jenis_kegiatan' => 'nullable|string|in:kuliah,praktik,gabungan',
            'shifts' => 'nullable|string',
            'metode' => 'required|string|in:' . implode(',', array_keys(UnitEvent::$metodeLabels)),
            'status' => 'required|string|in:' . implode(',', array_keys(UnitEvent::$statusLabels)),
            'lokasi' => 'nullable|string|max:255',
            'lokasi_praktik' => 'nullable|string',
            'alamat_detail' => 'nullable|string|max:4000',
            'maps_embed' => 'nullable|string|max:4000',
            'zoom_link' => 'nullable|url|max:2000',
            'whatsapp_group_link' => 'nullable|url|max:2000',
            'narahubung_nama' => 'nullable|string|max:255',
            'narahubung_whatsapp' => 'nullable|string|max:50',
            'mulai_at' => 'required|date',
            'selesai_at' => 'nullable|date|after_or_equal:mulai_at',
            'tahun_akademik' => 'nullable|string|max:255',
            'tingkat' => 'nullable|string|max:255',
            'semester' => 'nullable|string|max:255',
            'attendance_print_start_time' => 'nullable|date_format:H:i',
            'attendance_print_end_time' => 'nullable|date_format:H:i',
            'kapasitas' => 'nullable|integer|min:1|max:100000',
            'is_featured' => 'boolean',
            'registrasi_aktif' => 'boolean',
            'registrasi_mulai_at' => 'nullable|date',
            'registrasi_selesai_at' => 'nullable|date|after_or_equal:registrasi_mulai_at',
            'daftar_hadir_aktif' => 'boolean',
            'allow_direct_attendance' => 'boolean',
            'berita_acara_catatan' => 'nullable|string',
            'berita_acara_judul' => 'nullable|string|max:255',
            'berita_acara_ringkasan' => 'nullable|string|max:1000',
            'berita_acara_form_aktif' => 'boolean',
            'penandatangan_jabatan' => 'nullable|string|max:255',
            'penandatangan_nama' => 'nullable|string|max:255',
            'penandatangan_nip' => 'nullable|string|max:50',
            'koordinator_nama' => 'nullable|string|max:255',
            'koordinator_nip' => 'nullable|string|max:50',
            'direktur_nama' => 'nullable|string|max:255',
            'direktur_foto' => 'nullable|image|max:4096',
            'hapus_direktur_foto' => 'boolean',
            'flyer_theme' => 'nullable|string|in:' . implode(',', array_keys(UnitEvent::$flyerThemeLabels)),
            'flyer_tagline' => 'nullable|string|max:255',
            'flyer_benefits' => 'nullable|string|max:4000',
            'pre_test_aktif' => 'boolean',
            'post_test_aktif' => 'boolean',
            'evaluasi_aktif' => 'boolean',
            'rubrik_aktif' => 'boolean',
            'sertifikat_peserta_aktif' => 'boolean',
            'sertifikat_narasumber_aktif' => 'boolean',
            'sertifikat_panitia_aktif' => 'boolean',
            'flyer' => 'nullable|image|max:4096',
            'surat_tugas_event_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'hapus_surat_tugas_event' => 'boolean',
            'penandatangan_signature_file' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:4096',
            'penandatangan_signature_data' => 'nullable|string',
            'hapus_penandatangan_signature' => 'boolean',
        ]);

        foreach ([
            'is_featured',
            'registrasi_aktif',
            'daftar_hadir_aktif',
            'allow_direct_attendance',
            'berita_acara_form_aktif',
            'pre_test_aktif',
            'post_test_aktif',
            'evaluasi_aktif',
            'rubrik_aktif',
            'sertifikat_peserta_aktif',
            'sertifikat_narasumber_aktif',
            'sertifikat_panitia_aktif',
        ] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        foreach (['attendance_print_start_time', 'attendance_print_end_time'] as $field) {
            $data[$field] = filled($data[$field] ?? null)
                ? substr((string) $data[$field], 0, 5)
                : null;
        }

        $startTime = $data['attendance_print_start_time'] ?? null;
        $endTime = $data['attendance_print_end_time'] ?? null;

        if ($startTime && $endTime && strcmp($endTime, $startTime) < 0) {
            throw ValidationException::withMessages([
                'attendance_print_end_time' => 'Jam selesai cetak daftar hadir harus sama atau sesudah jam mulai.',
            ]);
        }

        if (in_array($data['status'], ['published', 'completed'], true) && ! $unitEvent?->published_at) {
            $data['published_at'] = now();
        }

        if (in_array($data['status'], ['draft', 'cancelled'], true)) {
            $data['published_at'] = null;
        }

        // Clean PBM fields if not PBM
        if (($data['jenis_kegiatan'] ?? '') !== 'pbm') {
            $data['sub_jenis_kegiatan'] = null;
            $data['shifts'] = null;
            $data['lokasi_praktik'] = null;
            $data['tahun_akademik'] = null;
            $data['tingkat'] = null;
            $data['semester'] = null;
        } else {
            $subJenis = $data['sub_jenis_kegiatan'] ?? null;
            if (!in_array($subJenis, ['praktik', 'gabungan'], true)) {
                $data['shifts'] = null;
                $data['lokasi_praktik'] = null;
            } else {
                if (isset($data['shifts']) && is_string($data['shifts'])) {
                    $data['shifts'] = json_decode($data['shifts'], true);
                }
                if (isset($data['lokasi_praktik']) && is_string($data['lokasi_praktik'])) {
                    $data['lokasi_praktik'] = json_decode($data['lokasi_praktik'], true);
                }
            }
        }

        return $data;
    }

    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($source) ?: 'event-unit';
        $slug = $baseSlug;
        $counter = 2;

        while (UnitEvent::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}