<?php

namespace App\Http\Controllers\Pbm;

use App\Http\Controllers\Controller;
use App\Models\ClinicalClass;
use App\Models\ClinicalClassMember;
use App\Models\User;
use App\Models\Mahasiswa;
use App\Support\MediaStorage;
use App\Services\AI\AiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClassController extends Controller
{
    public function index()
    {
        if (Auth::user()->role === 'ci') {
            return redirect()->route('pbm.ci.dashboard');
        }

        $classes = ClinicalClass::with(['unitPage', 'creator'])->withCount('sessions')
            ->where('is_active', true)
            ->latest()
            ->paginate(12);
            
        $unitPages = \App\Models\UnitPage::where('aktif', true)
            ->where('role', 'like', 'kaprodi_%')
            ->orderBy('judul')->get();

        return view('pbm.classes.index', compact('classes', 'unitPages'));
    }

    public function create()
    {
        return view('pbm.classes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'course_code' => 'nullable|string|max:255',
            'course_name' => 'nullable|string|max:255',
            'unit_page_id' => 'nullable|exists:unit_pages,id',
            'academic_year' => 'nullable|string|max:255',
            'semester' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['is_active'] = true;

        $class = ClinicalClass::create($validated);

        // Add creator as dosen
        $class->members()->create([
            'user_id' => Auth::id(),
            'role' => 'dosen',
        ]);

        return redirect()->route('pbm.classes.index')->with('success', 'Kelas berhasil dibuat.');
    }

    public function show(ClinicalClass $class)
    {
        $class->load(['sessions' => function($q) {
            $q->orderBy('start_time', 'asc');
        }, 'members.user', 'members.mahasiswa', 'unitPage']);

        // Get available users (dosen/ci) and mahasiswa for the add member modals
        $availableDosen = User::orderBy('name')
            ->whereNotIn('id', $class->members->where('user_id', '!=', null)->pluck('user_id')->toArray())
            ->get();

        $availableMahasiswa = Mahasiswa::whereNotIn('id', $class->members->where('mahasiswa_id', '!=', null)->pluck('mahasiswa_id')->toArray())
            ->orderBy('name')
            ->get();

        $availableProdi = \App\Models\ProgramStudi::leftJoin('jurusan', 'program_studi.jurusan_id', '=', 'jurusan.id')
            ->select('program_studi.*')
            ->orderBy('jurusan.urutan')
            ->orderBy('program_studi.jenjang')
            ->orderBy('program_studi.nama')
            ->get();
        
        $availableAngkatan = Mahasiswa::whereNotNull('angkatan')
            ->distinct()
            ->orderBy('angkatan', 'desc')
            ->pluck('angkatan');

        return view('pbm.classes.show', compact('class', 'availableDosen', 'availableMahasiswa', 'availableProdi', 'availableAngkatan'));
    }

    public function edit(ClinicalClass $class)
    {
        $unitPages = \App\Models\UnitPage::where('aktif', true)
            ->where('role', 'like', 'kaprodi_%')
            ->orderBy('judul')->get();
            
        $coverPresets = \App\Services\Pbm\ClassCoverDiagramService::getPresets();

        return view('pbm.classes.edit', compact('class', 'unitPages', 'coverPresets'));
    }

    public function update(Request $request, ClinicalClass $class)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'course_code' => 'nullable|string|max:255',
            'course_name' => 'nullable|string|max:255',
            'unit_page_id' => 'nullable|exists:unit_pages,id',
            'academic_year' => 'nullable|string|max:255',
            'semester' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'ai_cover_path' => 'nullable|string',
            'remove_cover' => 'nullable|boolean',
        ]);

        if ($request->boolean('remove_cover')) {
            if ($class->cover_image) {
                MediaStorage::delete($class->cover_image);
                $class->cover_image = null;
            }
        } elseif ($request->hasFile('cover_image')) {
            if ($class->cover_image) {
                MediaStorage::delete($class->cover_image);
            }
            $class->cover_image = MediaStorage::storeUploadedFile($request->file('cover_image'), 'pbm/classes/covers');
        } elseif ($request->filled('ai_cover_path')) {
            $aiPath = $request->input('ai_cover_path');
            if ($class->cover_image && $class->cover_image !== $aiPath) {
                MediaStorage::delete($class->cover_image);
            }
            $class->cover_image = $aiPath;
        }

        $class->fill(collect($validated)->except(['cover_image', 'ai_cover_path', 'remove_cover'])->toArray());
        $class->save();

        return redirect()->route('pbm.classes.show', $class)->with('success', 'Kelas berhasil diperbarui.');
    }

    public function generateCoverAi(Request $request, ClinicalClass $class)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'course_name' => 'nullable|string|max:255',
            'style' => 'nullable|string|in:vector_infographic,isometric_3d,data_diagram,minimalist_medical',
        ]);

        $className = $request->input('name') ?: $class->name;
        $description = $request->input('description') ?: $class->description;
        $courseName = $request->input('course_name') ?: $class->course_name;
        $prodi = $class->unitPage ? $class->unitPage->judul : '';
        $style = $request->input('style', 'vector_infographic');

        // 0. If style is data_diagram, build a high-precision, authentic vector diagram (SVG)
        if ($style === 'data_diagram') {
            try {
                $diagram = \App\Services\Pbm\ClassCoverDiagramService::generate($className, $courseName, $description, $prodi);
                $filename = 'diagram-cover-' . $class->id . '-' . time() . '.svg';
                $storagePath = 'pbm/classes/covers/' . $filename;
                $savedRef = MediaStorage::put($storagePath, $diagram['svg'], 'image/svg+xml');
                $publicUrl = MediaStorage::url($savedRef);

                return response()->json([
                    'success' => true,
                    'cover_path' => $savedRef,
                    'cover_url' => $publicUrl,
                    'prompt_used' => $diagram['label'] . ' (Vektor Presisi Resolusi Tinggi)',
                ]);
            } catch (\Throwable $e) {
                Log::error('[ClassController] Diagram generation error: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyusun diagram cover: ' . $e->getMessage(),
                ], 500);
            }
        }

        // Style modifiers (kept concise for fast and reliable AI generation)
        $styleModifiers = match ($style) {
            'isometric_3d' => [
                'type' => 'isometric 3D illustration',
                'suffix' => ', modern isometric 3D illustration, vibrant pastel colors, clean vector design, no text',
            ],
            'data_diagram' => [
                'type' => 'scientific infographic data visualization',
                'suffix' => ', clean science diagram and infographic data charts, flat vector art, vibrant colors, no text',
            ],
            'minimalist_medical' => [
                'type' => 'minimalist medical icon vector illustration',
                'suffix' => ', minimalist medical vector line art, clean pastel background, modern banner, no text',
            ],
            default => [
                'type' => 'educational vector illustration and infographic',
                'suffix' => ', modern educational vector illustration, clean infographic graphics, flat art, vibrant colors, no text, no photo',
            ],
        };

        // 1. Synthesize visual prompt with AI text model
        $imagePrompt = "";
        try {
            $ai = new AiClient();
            $systemPrompt = "You are an expert educational illustrator for health sciences (Poltekkes). " .
                "Create a concise English descriptive prompt (12-15 words) for a {$styleModifiers['type']} banner for a class cover. " .
                "Strictly base the theme on the Class Name ({$className}). Describe colorful stylized vector graphics, scientific charts, relevant medical icons, and vibrant modern aesthetic. " .
                "CRITICAL RULES: " .
                "- Must be an illustration / infographic graphic design, NEVER a realistic photograph, NEVER a clinical room interior. " .
                "- Absolutely NO human faces or people. " .
                "- Absolutely NO text, NO letters, NO words, NO watermark. " .
                "- Output ONLY the prompt text, no quotes.";

            $userContent = "Class: {$className}\nCourse: {$courseName}\nProgram: {$prodi}\nDescription: {$description}";
            $refined = trim($ai->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userContent],
            ], 0.7, 60));

            // Clean any potential markdown/quotes/prefixes
            $refined = preg_replace('/^(prompt:|"|\'|Output:\s*)/i', '', $refined);
            $refined = trim($refined, " \t\n\r\0\x0B\"'");

            if (!empty($refined) && strlen($refined) > 15 && !preg_match('/^(error|sorry|i cannot)/i', $refined)) {
                $imagePrompt = $refined;
            }
        } catch (\Throwable $e) {
            Log::warning('[ClassController] AI prompt generation fallback: ' . $e->getMessage());
        }

        // If AI prompt empty or failed, use smart domain fallback based on Class Name first, then Prodi
        if (empty($imagePrompt)) {
            $classLower = strtolower($className . ' ' . $courseName . ' ' . $description);
            $prodiLower = strtolower($prodi);

            if (str_contains($classLower, 'epidemiolog') || str_contains($classLower, 'surveilans') || str_contains($classLower, 'wabah')) {
                $imagePrompt = "vector art illustration of epidemiology and disease surveillance, world map data, virus transmission models, colorful health charts";
            } elseif (str_contains($classLower, 'farmas') || str_contains($classLower, 'obat') || str_contains($classLower, 'farmako') || str_contains($prodiLower, 'farmasi')) {
                $imagePrompt = "vector art illustration of pharmaceutical science, colorful medicine capsules, molecular structure bonds, chemistry laboratory glassware";
            } elseif (str_contains($classLower, 'gizi') || str_contains($classLower, 'nutrisi') || str_contains($classLower, 'diet') || str_contains($prodiLower, 'gizi')) {
                $imagePrompt = "vector art illustration of clinical nutrition and dietetics, healthy balanced food pyramid, fruit and vegetable icons, nutrition health charts";
            } elseif (str_contains($classLower, 'bidan') || str_contains($classLower, 'kebidanan') || str_contains($classLower, 'maternal') || str_contains($prodiLower, 'kebidanan')) {
                $imagePrompt = "vector art illustration of maternal and newborn healthcare, gentle warm pastel palette, mother infant care, stethoscope";
            } elseif (str_contains($classLower, 'gigi') || str_contains($classLower, 'dental') || str_contains($prodiLower, 'gigi')) {
                $imagePrompt = "vector art illustration of dental health and oral care, stylized clean tooth model, dental hygiene instruments, colorful flat design";
            } elseif (str_contains($classLower, 'lingkungan') || str_contains($classLower, 'sanitasi') || str_contains($prodiLower, 'sanitasi')) {
                $imagePrompt = "vector art illustration of environmental health and sanitation engineering, clean water ecology, green nature, sustainable science";
            } elseif (str_contains($classLower, 'tlm') || str_contains($classLower, 'analis') || str_contains($classLower, 'laborator') || str_contains($prodiLower, 'tlm') || str_contains($prodiLower, 'analis')) {
                $imagePrompt = "vector art illustration of medical laboratory technology, microscope, blood cells, DNA helix, test tubes, science graphics";
            } elseif (str_contains($classLower, 'anatomi') || str_contains($classLower, 'fisiologi')) {
                $imagePrompt = "vector art illustration of human anatomy and physiology, stylized medical organs, cardiovascular system, clean graphic design";
            } elseif (str_contains($classLower, 'perawat') || str_contains($classLower, 'keperawatan') || str_contains($prodiLower, 'keperawatan')) {
                $imagePrompt = "vector art illustration of clinical nursing and patient healthcare, heartbeat pulse wave, medical stethoscope, clinical tools";
            } else {
                $imagePrompt = "vector art illustration of medical health science and clinical education, stylized stethoscope, medical cross, healthcare data icons";
            }
        }

        // 2. Query Pollinations.ai image generator with robust retry
        $cleanPrompt = substr(preg_replace('/[^a-zA-Z0-9\s,.-]/', '', $imagePrompt), 0, 140);
        $encodedPrompt = urlencode($cleanPrompt . $styleModifiers['suffix']);

        $seed = rand(1000, 999999);
        // 1200x630 is standard widescreen OpenGraph ratio (~1.9:1) that generates rich horizontal banner graphics without distortion
        $imageUrl = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=1200&height=630&seed={$seed}&nologo=true";

        $imageData = null;
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $targetUrl = $attempt === 1 
                    ? $imageUrl 
                    : "https://image.pollinations.ai/prompt/" . urlencode($cleanPrompt . ", vector art illustration, flat design, vibrant colors, no text") . "?width=1200&height=630&seed=" . rand(1000, 999999) . "&nologo=true";

                $response = Http::timeout($attempt === 1 ? 45 : 30)->get($targetUrl);

                if ($response->successful() 
                    && !empty($response->body()) 
                    && str_starts_with($response->header('Content-Type') ?? '', 'image/')) {
                    $imageData = $response->body();
                    break;
                } else {
                    Log::warning("[ClassController] AI image attempt {$attempt} unsuccessful: status={$response->status()} type={$response->header('Content-Type')}");
                }
            } catch (\Throwable $err) {
                Log::warning("[ClassController] AI image attempt {$attempt} exception: " . $err->getMessage());
            }

            if ($attempt === 1) {
                sleep(2);
            }
        }

        if (!$imageData) {
            return response()->json([
                'success' => false,
                'message' => 'Layanan AI pembuat ilustrasi sedang sibuk atau antrean penuh. Silakan klik tombol Generate kembali sesaat lagi.'
            ], 500);
        }

        try {
            // Save to storage
            $filename = 'ai-cover-' . $class->id . '-' . time() . '.jpg';
            $storagePath = 'pbm/classes/covers/' . $filename;
            $savedRef = MediaStorage::put($storagePath, $imageData, 'image/jpeg');

            $publicUrl = MediaStorage::url($savedRef);

            return response()->json([
                'success' => true,
                'cover_path' => $savedRef,
                'cover_url' => $publicUrl,
                'prompt_used' => $imagePrompt,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ClassController] AI cover storage error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan gambar ilustrasi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function applyPresetCover(Request $request, ClinicalClass $class)
    {
        $request->validate([
            'preset_key' => 'required|string',
            'name' => 'nullable|string|max:255',
            'course_name' => 'nullable|string|max:255',
        ]);

        $presetKey = $request->input('preset_key');
        $className = $request->input('name') ?: $class->name;
        $courseName = $request->input('course_name') ?: $class->course_name;
        $title = !empty($courseName) ? "{$courseName} : {$className}" : $className;

        try {
            $diagram = \App\Services\Pbm\ClassCoverDiagramService::buildByKey($presetKey, $title);
            $filename = 'preset-cover-' . $presetKey . '-' . $class->id . '-' . time() . '.svg';
            $storagePath = 'pbm/classes/covers/' . $filename;
            $savedRef = MediaStorage::put($storagePath, $diagram['svg'], 'image/svg+xml');
            $publicUrl = MediaStorage::url($savedRef);

            return response()->json([
                'success' => true,
                'cover_path' => $savedRef,
                'cover_url' => $publicUrl,
                'label' => $diagram['label'],
            ]);
        } catch (\Throwable $e) {
            Log::error('[ClassController] Apply preset error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menerapkan cover banner dari galeri: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(ClinicalClass $class)
    {
        if ($class->cover_image) {
            MediaStorage::delete($class->cover_image);
        }
        $class->delete();
        return redirect()->route('pbm.classes.index')->with('success', 'Kelas berhasil dihapus.');
    }

    public function addMember(Request $request, ClinicalClass $class)
    {
        $validated = $request->validate([
            'member_type' => 'required|in:dosen,ci,mahasiswa',
            'user_id' => 'nullable|exists:users,id',
            'mahasiswa_id' => 'nullable|exists:mahasiswa,id',
            'group_name' => 'nullable|string|max:255',
        ]);

        if ($validated['member_type'] === 'mahasiswa') {
            if (!$validated['mahasiswa_id']) {
                return redirect()->back()->with('error', 'Pilih mahasiswa terlebih dahulu.');
            }

            // Check duplicate
            $exists = ClinicalClassMember::where('clinical_class_id', $class->id)
                ->where('mahasiswa_id', $validated['mahasiswa_id'])
                ->where('role', 'mahasiswa')
                ->exists();
            if ($exists) {
                return redirect()->back()->with('error', 'Mahasiswa sudah tergabung di kelas ini.');
            }

            $class->members()->create([
                'mahasiswa_id' => $validated['mahasiswa_id'],
                'role' => 'mahasiswa',
                'group_name' => $validated['group_name'] ?? null,
            ]);

            return redirect()->back()->with('success', 'Mahasiswa berhasil ditambahkan.');
        } else {
            if (!$validated['user_id']) {
                return redirect()->back()->with('error', 'Pilih dosen/CI terlebih dahulu.');
            }

            // Check duplicate
            $exists = ClinicalClassMember::where('clinical_class_id', $class->id)
                ->where('user_id', $validated['user_id'])
                ->where('role', $validated['member_type'])
                ->exists();
            if ($exists) {
                return redirect()->back()->with('error', 'User sudah tergabung di kelas ini.');
            }

            $class->members()->create([
                'user_id' => $validated['user_id'],
                'role' => $validated['member_type'],
            ]);

            return redirect()->back()->with('success', ucfirst($validated['member_type']) . ' berhasil ditambahkan.');
        }
    }

    public function addBatchMembers(Request $request, ClinicalClass $class)
    {
        $validated = $request->validate([
            'program_studi_id' => 'required|exists:program_studi,id',
            'angkatan' => 'required|string',
            'group_assignment' => 'nullable|string|in:none,single,auto',
            'group_name' => 'nullable|string|max:255',
            'auto_amount' => 'nullable|integer|min:1',
            'auto_prefix' => 'nullable|string|max:50',
        ]);

        $existingMemberIds = $class->members()->where('role', 'mahasiswa')->pluck('mahasiswa_id')->toArray();

        $mahasiswaToInsert = Mahasiswa::where('program_studi_id', $validated['program_studi_id'])
            ->where('angkatan', $validated['angkatan'])
            ->whereNotIn('id', $existingMemberIds)
            ->get();

        if ($mahasiswaToInsert->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada mahasiswa baru dari prodi dan angkatan tersebut (mungkin semua sudah ada di kelas).');
        }

        $groupAssignment = $validated['group_assignment'] ?? 'none';
        
        // If auto assignment, shuffle and calculate groups
        if ($groupAssignment === 'auto') {
            $shuffled = $mahasiswaToInsert->shuffle();
            $numGroups = $validated['auto_amount'] ?? 1;
            $prefix = $validated['auto_prefix'] ?? 'Kelompok';
            $totalStudents = $shuffled->count();
            $membersPerGroup = ceil($totalStudents / $numGroups);
            
            $groupIndex = 1;
            $count = 0;
            
            $insertData = [];
            $now = now();
            foreach ($shuffled as $mhs) {
                $groupName = trim($prefix . ' ' . $groupIndex);
                $insertData[] = [
                    'clinical_class_id' => $class->id,
                    'mahasiswa_id' => $mhs->id,
                    'role' => 'mahasiswa',
                    'group_name' => $groupName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $count++;
                if ($count >= $membersPerGroup && $groupIndex < $numGroups) {
                    $groupIndex++;
                    $count = 0;
                }
            }
        } else {
            $insertData = [];
            $now = now();
            $groupName = ($groupAssignment === 'single') ? ($validated['group_name'] ?? null) : null;
            
            foreach ($mahasiswaToInsert as $mhs) {
                $insertData[] = [
                    'clinical_class_id' => $class->id,
                    'mahasiswa_id' => $mhs->id,
                    'role' => 'mahasiswa',
                    'group_name' => $groupName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        \App\Models\ClinicalClassMember::insert($insertData);

        return redirect()->back()->with('success', 'Berhasil menambahkan ' . count($insertData) . ' mahasiswa ke dalam kelas.');
    }

    public function removeMember(ClinicalClass $class, ClinicalClassMember $member)
    {
        if ($member->clinical_class_id !== $class->id) {
            abort(403);
        }

        $member->delete();
        return redirect()->back()->with('success', 'Anggota berhasil dihapus dari kelas.');
    }

    public function bulkDestroy(Request $request, ClinicalClass $class)
    {
        $validated = $request->validate([
            'member_ids' => 'required|array',
            'member_ids.*' => 'exists:clinical_class_members,id',
        ]);

        ClinicalClassMember::where('clinical_class_id', $class->id)
            ->whereIn('id', $validated['member_ids'])
            ->delete();

        return redirect()->back()->with('success', count($validated['member_ids']) . ' anggota berhasil dihapus dari kelas.');
    }

    public function bulkAssignGroup(Request $request, ClinicalClass $class)
    {
        $validated = $request->validate([
            'member_ids' => 'required|array',
            'member_ids.*' => 'exists:clinical_class_members,id',
            'group_name' => 'nullable|string|max:255',
        ]);

        ClinicalClassMember::where('clinical_class_id', $class->id)
            ->whereIn('id', $validated['member_ids'])
            ->where('role', 'mahasiswa')
            ->update(['group_name' => $validated['group_name']]);

        return redirect()->back()->with('success', 'Kelompok ' . count($validated['member_ids']) . ' mahasiswa berhasil diset.');
    }

    public function updateMemberGroup(Request $request, ClinicalClass $class, ClinicalClassMember $member)
    {
        if ($member->clinical_class_id !== $class->id) {
            abort(403);
        }

        $validated = $request->validate([
            'group_name' => 'nullable|string|max:255',
        ]);

        $member->update(['group_name' => $validated['group_name']]);

        return redirect()->back()->with('success', 'Kelompok mahasiswa berhasil diperbarui.');
    }

    public function autoAssignGroups(Request $request, ClinicalClass $class)
    {
        $validated = $request->validate([
            'division_type' => 'required|in:by_groups,by_members,by_prodi',
            'amount' => 'nullable|integer|min:1',
            'prefix' => 'nullable|string|max:50',
            'filter_target' => 'nullable|in:all,specific',
            'filter_prodi_id' => 'nullable|required_if:filter_target,specific|exists:program_studi,id',
            'filter_angkatan' => 'nullable|required_if:filter_target,specific|string',
        ]);

        $query = $class->members()->with('mahasiswa.programStudi')->where('role', 'mahasiswa');

        if (($validated['filter_target'] ?? 'all') === 'specific') {
            $query->whereHas('mahasiswa', function ($q) use ($validated) {
                $q->where('program_studi_id', $validated['filter_prodi_id'])
                  ->where('angkatan', $validated['filter_angkatan']);
            });
        }

        $mahasiswaMembers = $query->get();
        if ($mahasiswaMembers->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada mahasiswa yang sesuai dengan kriteria untuk dibagi kelompok.');
        }

        $prefix = $validated['prefix'] ?? 'Kelompok';

        if ($validated['division_type'] === 'by_prodi') {
            $count = 0;
            foreach ($mahasiswaMembers as $member) {
                $prodi = $member->mahasiswa->programStudi->singkatan ?? 'Unknown Prodi';
                $angkatan = $member->mahasiswa->angkatan ?? 'Unknown Angkatan';
                $groupName = trim($prefix . ' ' . $prodi . ' ' . $angkatan);
                
                // If prefix is empty, don't leave leading space
                if (empty($validated['prefix'])) {
                    $groupName = trim($prodi . ' ' . $angkatan);
                }

                $member->update(['group_name' => $groupName]);
                $count++;
            }
            return redirect()->back()->with('success', 'Berhasil membagi ' . $count . ' mahasiswa ke dalam kelompok berdasarkan Prodi dan Angkatan.');
        }

        // Shuffle students for random assignment
        $shuffled = $mahasiswaMembers->shuffle();
        $totalStudents = $shuffled->count();

        // Amount is required for by_groups and by_members
        if (empty($validated['amount'])) {
            return redirect()->back()->with('error', 'Jumlah wajib diisi untuk tipe pembagian ini.');
        }

        if ($validated['division_type'] === 'by_groups') {
            $numGroups = $validated['amount'];
        } else { // by_members
            $numGroups = ceil($totalStudents / $validated['amount']);
        }

        // Divide into groups
        $groupIndex = 1;
        $count = 0;
        
        $membersPerGroup = ceil($totalStudents / $numGroups);
        if ($validated['division_type'] === 'by_members') {
            $membersPerGroup = $validated['amount'];
        }

        foreach ($shuffled as $member) {
            $groupName = trim($prefix . ' ' . $groupIndex);
            $member->update(['group_name' => $groupName]);

            $count++;
            if ($count >= $membersPerGroup && $groupIndex < $numGroups) {
                $groupIndex++;
                $count = 0;
            }
        }

        return redirect()->back()->with('success', 'Berhasil membagi ' . $totalStudents . ' mahasiswa ke dalam ' . $numGroups . ' kelompok secara acak.');
    }
}
