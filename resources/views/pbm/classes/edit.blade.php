@extends('pbm.layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('pbm.classes.show', $class) }}" class="text-sky-600 hover:underline text-sm flex items-center gap-1 mb-2">
        <i data-feather="arrow-left" class="w-4 h-4"></i> Kembali
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Edit Kelas</h1>
    <p class="text-gray-500 mt-1">Perbarui data kelas praktik klinik</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden max-w-2xl">
    <form action="{{ route('pbm.classes.update', $class) }}" method="POST" enctype="multipart/form-data" class="p-6">
        @csrf
        @method('PUT')
        
        <!-- Cover Kelas Section -->
        <div class="mb-6 border border-gray-200 rounded-xl p-4 bg-gray-50/60">
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-bold text-gray-800">Cover Kelas</label>
                <span class="text-xs text-gray-400">Rasio 16:9 atau Banner horizontal (Maks. 5MB)</span>
            </div>
            
            <!-- Preview Container -->
            <div class="relative w-full h-48 md:h-52 rounded-xl overflow-hidden border border-gray-300 bg-gradient-to-r from-sky-500 to-indigo-600 mb-3 shadow-inner flex items-center justify-center group" id="coverPreviewContainer">
                <img id="coverPreviewImg" 
                     src="{{ $class->cover_url ?? '' }}" 
                     alt="Cover Kelas" 
                     class="w-full h-full object-cover {{ $class->cover_url ? '' : 'hidden' }}">
                
                <div id="coverPlaceholder" class="text-center p-6 text-white {{ $class->cover_url ? 'hidden' : '' }}">
                    <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center mx-auto mb-2 text-white">
                        <i data-feather="image" class="w-6 h-6"></i>
                    </div>
                    <p class="text-sm font-semibold">Belum ada cover kelas</p>
                    <p class="text-xs text-sky-100 mt-0.5">Upload gambar sendiri atau generate otomatis dengan AI</p>
                </div>

                <!-- Overlay loading spinner when generating AI -->
                <div id="aiLoadingOverlay" class="absolute inset-0 bg-gray-950/75 backdrop-blur-sm flex flex-col items-center justify-center text-white hidden z-20 transition-opacity">
                    <svg class="animate-spin h-9 w-9 text-sky-400 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span class="text-sm font-bold text-white tracking-wide" id="aiLoadingText">Menyusun Visual Cover dengan AI...</span>
                    <span class="text-xs text-gray-300 mt-1">Berdasarkan nama, blok, & deskripsi kelas</span>
                </div>

                <div id="coverStatusBadge" class="absolute top-3 left-3 bg-black/60 backdrop-blur-md text-white px-2.5 py-1 rounded-lg text-xs font-semibold {{ $class->cover_url ? '' : 'hidden' }}">
                    Cover Tersimpan
                </div>
            </div>

            <!-- Hidden inputs -->
            <input type="file" name="cover_image" id="coverFileInput" accept="image/jpeg,image/png,image/jpg,image/webp" class="hidden" onchange="handleFileUpload(event)">
            <input type="hidden" name="ai_cover_path" id="aiCoverPath" value="">
            <input type="hidden" name="remove_cover" id="removeCoverInput" value="0">

            <!-- Action Controls -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                <div class="flex items-center gap-2 overflow-x-auto pb-1.5 -mb-1 swipe-scroll flex-nowrap sm:flex-wrap">
                    <!-- Upload Button -->
                    <button type="button" onclick="document.getElementById('coverFileInput').click()" class="px-3 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer shrink-0">
                        <i data-feather="upload" class="w-3.5 h-3.5 text-gray-500"></i> Upload Cover
                    </button>

                    <!-- Gallery Preset Button -->
                    <button type="button" onclick="openPresetGalleryModal()" class="px-3 py-2 bg-sky-50 border border-sky-200 hover:bg-sky-100 text-sky-800 rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer shrink-0">
                        <i data-feather="grid" class="w-3.5 h-3.5 text-sky-600"></i> Galeri Banner
                    </button>

                    <!-- Style Selector Dropdown -->
                    <div class="relative shrink-0">
                        <select id="coverAiStyle" class="px-3 py-2 bg-purple-50 hover:bg-purple-100/70 border border-purple-200 text-purple-900 rounded-lg text-xs font-medium shadow-xs transition focus:border-purple-500 focus:ring-1 focus:ring-purple-500 cursor-pointer">
                            <option value="data_diagram">📊 Diagram &amp; Visual Data (Vektor Presisi)</option>
                            <option value="vector_infographic">🎨 Ilustrasi / Infografis Edukasi</option>
                            <option value="isometric_3d">🌐 3D Isometrik Modern</option>
                            <option value="minimalist_medical">🩺 Simbol Medis Minimalis</option>
                        </select>
                    </div>

                    <!-- AI Generate Button -->
                    <button type="button" id="btnGenerateAi" onclick="generateCoverWithAi()" 
                            class="px-3.5 py-2 text-white rounded-lg text-xs font-semibold transition flex items-center gap-1.5 shadow-xs hover:opacity-95 cursor-pointer shrink-0"
                            style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%) !important; color: #ffffff !important;">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="stroke: #ffffff !important;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span id="btnGenerateAiText" style="color: #ffffff !important;">Generate Cover by AI</span>
                    </button>
                </div>

                <!-- Remove Button -->
                <button type="button" id="btnRemoveCover" onclick="removeCover()" class="text-xs text-red-600 hover:text-red-700 font-medium flex items-center gap-1 px-2.5 py-2 rounded-lg hover:bg-red-50 transition cursor-pointer shrink-0 {{ $class->cover_url ? '' : 'hidden' }}">
                    <i data-feather="trash-2" class="w-3.5 h-3.5"></i> Hapus Cover
                </button>
            </div>

            <div id="aiPromptNotice" class="mt-2.5 p-2 bg-purple-50 rounded-lg border border-purple-100 text-xs text-purple-900 hidden">
                <span class="font-bold">Tema Ilustrasi AI:</span> <span id="aiPromptText"></span>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kelas <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $class->name) }}" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kode Mata Kuliah</label>
                    <input type="text" name="course_code" value="{{ old('course_code', $class->course_code) }}" placeholder="Cth: BDG123" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Mata Kuliah / Blok</label>
                    <input type="text" name="course_name" value="{{ old('course_name', $class->course_name) }}" placeholder="Cth: Asuhan Kebidanan" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Program Studi</label>
                <select name="unit_page_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                    <option value="">-- Pilih Program Studi --</option>
                    @foreach($unitPages as $page)
                        <option value="{{ $page->id }}" {{ $class->unit_page_id == $page->id ? 'selected' : '' }}>{{ $page->judul }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Ajaran</label>
                    <select name="academic_year" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                        @php $currentYear = date('Y'); @endphp
                        @for($i = $currentYear - 2; $i <= $currentYear + 2; $i++)
                            @php $yr = $i . '/' . ($i+1); @endphp
                            <option value="{{ $yr }}" {{ $class->academic_year == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                    <select name="semester" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                        <option value="">-- Pilih Semester --</option>
                        @for($s = 1; $s <= 8; $s++)
                            <option value="{{ $s }}" {{ $class->semester == $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endfor
                    </select>
                </div>
            </div>      </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">{{ old('description', $class->description) }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-5">
            <a href="{{ route('pbm.classes.show', $class) }}" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-100 rounded-lg">Batal</a>
            <button type="submit" class="pbm-primary px-5 py-2 rounded-lg font-medium hover:opacity-90 shadow-sm">Simpan Perubahan</button>
        </div>
    </form>
</div>

<!-- Preset Gallery Modal -->
<div id="presetGalleryModal" class="fixed inset-0 z-50 overflow-y-auto p-2 sm:p-4 md:p-6 bg-gray-950/75 backdrop-blur-xs hidden transition-opacity flex items-center justify-center min-h-screen">
    <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-200 w-full max-w-4xl max-h-[92vh] my-auto flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <!-- Modal Header -->
        <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-sky-50/70 via-indigo-50/50 to-purple-50/70 shrink-0">
            <div>
                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-sky-600 text-white flex items-center justify-center text-sm shadow-sm">🖼️</span>
                    Galeri Banner &amp; Diagram Edukasi Kesehatan
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">Pilih banner diagram ilmiah presisi tinggi siap pakai (100% jernih, tajam &amp; bebas blur) sesuai bidang ilmu Anda.</p>
            </div>
            <button type="button" onclick="closePresetGalleryModal()" class="w-8 h-8 rounded-full hover:bg-gray-200/80 text-gray-400 hover:text-gray-700 flex items-center justify-center transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Filter Tabs (Swipeable) -->
        <div class="px-4 sm:px-6 py-2.5 border-b border-gray-100 bg-gray-50/80 flex items-center gap-1.5 overflow-x-auto swipe-scroll text-xs shrink-0">
            <button type="button" onclick="filterPresetCategory('all', this)" class="preset-filter-btn px-3 py-1.5 rounded-full font-semibold transition bg-sky-600 text-white shadow-xs cursor-pointer shrink-0">
                Semua Bidang
            </button>
            <button type="button" onclick="filterPresetCategory('Epidemiologi & Kesmas', this)" class="preset-filter-btn px-3 py-1.5 rounded-full font-semibold transition bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 cursor-pointer shrink-0">
                Epidemiologi
            </button>
            <button type="button" onclick="filterPresetCategory('Keperawatan', this)" class="preset-filter-btn px-3 py-1.5 rounded-full font-semibold transition bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 cursor-pointer shrink-0">
                Keperawatan
            </button>
            <button type="button" onclick="filterPresetCategory('Farmasi', this)" class="preset-filter-btn px-3 py-1.5 rounded-full font-semibold transition bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 cursor-pointer shrink-0">
                Farmasi
            </button>
            <button type="button" onclick="filterPresetCategory('Lab Medis / TLM', this)" class="preset-filter-btn px-3 py-1.5 rounded-full font-semibold transition bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 cursor-pointer shrink-0">
                TLM / Lab
            </button>
            <button type="button" onclick="filterPresetCategory('Kesehatan Gigi', this)" class="preset-filter-btn px-3 py-1.5 rounded-full font-semibold transition bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 cursor-pointer shrink-0">
                Kesehatan Gigi
            </button>
            <button type="button" onclick="filterPresetCategory('Gizi & Dietetika', this)" class="preset-filter-btn px-3 py-1.5 rounded-full font-semibold transition bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 cursor-pointer shrink-0">
                Gizi
            </button>
            <button type="button" onclick="filterPresetCategory('Kebidanan', this)" class="preset-filter-btn px-3 py-1.5 rounded-full font-semibold transition bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 cursor-pointer shrink-0">
                Kebidanan
            </button>
            <button type="button" onclick="filterPresetCategory('Sanitasi Lingkungan', this)" class="preset-filter-btn px-3 py-1.5 rounded-full font-semibold transition bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 cursor-pointer shrink-0">
                Sanitasi
            </button>
        </div>

        <!-- Preset Cards Grid (Responsive & Fluid) -->
        <div class="p-4 sm:p-6 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 max-h-[58vh]">
            @foreach($coverPresets ?? [] as $preset)
                <div class="preset-card group relative bg-slate-900 border border-slate-700/80 hover:border-sky-400 rounded-xl p-4 shadow-sm hover:shadow-lg transition flex flex-col justify-between cursor-pointer"
                     data-category="{{ $preset['category'] }}"
                     onclick="applyPresetCover('{{ $preset['key'] }}', '{{ addslashes($preset['title']) }}')">
                    
                    <div>
                        <!-- Header / Badge -->
                        <div class="flex items-center justify-between gap-2 mb-2.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase bg-sky-500/20 text-sky-300 border border-sky-500/30">
                                {{ $preset['badge'] }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-medium">
                                {{ $preset['category'] }}
                            </span>
                        </div>

                        <!-- Title -->
                        <h4 class="text-sm font-bold text-white group-hover:text-sky-300 transition line-clamp-1 mb-1">
                            {{ $preset['title'] }}
                        </h4>

                        <!-- Description -->
                        <p class="text-xs text-slate-300 line-clamp-2 leading-relaxed mb-3">
                            {{ $preset['desc'] }}
                        </p>
                    </div>

                    <!-- Action Link / Button -->
                    <div class="pt-3 border-t border-slate-800 flex items-center justify-between text-xs">
                        <span class="text-sky-400 font-semibold flex items-center gap-1 group-hover:underline">
                            Gunakan Banner Ini &rarr;
                        </span>
                        <span class="text-[10px] text-slate-400 bg-slate-800 px-2 py-0.5 rounded">
                            Vektor SVG
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-3 border-t border-gray-100 bg-gray-50 flex items-center justify-between text-xs text-gray-500">
            <span>💡 Banner vektor SVG otomatis mencantumkan nama kelas Anda di header diagram.</span>
            <button type="button" onclick="closePresetGalleryModal()" class="px-4 py-1.5 bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 rounded-lg font-semibold transition cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function handleFileUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        alert('Ukuran file maksimal 5MB.');
        event.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.getElementById('coverPreviewImg');
        img.src = e.target.result;
        img.classList.remove('hidden');
        document.getElementById('coverPlaceholder').classList.add('hidden');
        
        const badge = document.getElementById('coverStatusBadge');
        badge.textContent = 'Preview File Baru';
        badge.classList.remove('hidden');
        
        document.getElementById('removeCoverInput').value = '0';
        document.getElementById('aiCoverPath').value = '';
        document.getElementById('btnRemoveCover').classList.remove('hidden');
        document.getElementById('aiPromptNotice').classList.add('hidden');
    };
    reader.readAsDataURL(file);
}

function removeCover() {
    document.getElementById('coverFileInput').value = '';
    document.getElementById('aiCoverPath').value = '';
    document.getElementById('removeCoverInput').value = '1';
    
    const img = document.getElementById('coverPreviewImg');
    img.src = '';
    img.classList.add('hidden');
    
    document.getElementById('coverPlaceholder').classList.remove('hidden');
    document.getElementById('coverStatusBadge').classList.add('hidden');
    document.getElementById('btnRemoveCover').classList.add('hidden');
    document.getElementById('aiPromptNotice').classList.add('hidden');
}

function openPresetGalleryModal() {
    const modal = document.getElementById('presetGalleryModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
}

function closePresetGalleryModal() {
    const modal = document.getElementById('presetGalleryModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

function filterPresetCategory(category, btnElement) {
    const cards = document.querySelectorAll('.preset-card');
    document.querySelectorAll('.preset-filter-btn').forEach(b => {
        b.classList.remove('bg-sky-600', 'text-white');
        b.classList.add('bg-white', 'border', 'border-gray-200', 'text-gray-700', 'hover:bg-gray-100');
    });
    btnElement.classList.remove('bg-white', 'border', 'border-gray-200', 'text-gray-700', 'hover:bg-gray-100');
    btnElement.classList.add('bg-sky-600', 'text-white');

    cards.forEach(card => {
        if (category === 'all' || card.dataset.category === category) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
}

async function applyPresetCover(presetKey, presetTitle) {
    const nameInput = document.querySelector('input[name="name"]').value.trim();
    const courseNameInput = document.querySelector('input[name="course_name"]').value.trim();

    closePresetGalleryModal();

    const overlay = document.getElementById('aiLoadingOverlay');
    const loadingText = document.getElementById('aiLoadingText');
    if (loadingText) loadingText.textContent = 'Menerapkan banner ' + presetTitle + '...';
    overlay.classList.remove('hidden');

    try {
        const response = await fetch("{{ route('pbm.classes.apply-preset-cover', $class->id) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                preset_key: presetKey,
                name: nameInput,
                course_name: courseNameInput
            })
        });

        const data = await response.json();
        if (response.ok && data.success) {
            const img = document.getElementById('coverPreviewImg');
            img.src = data.cover_url;
            img.classList.remove('hidden');
            document.getElementById('coverPlaceholder').classList.add('hidden');

            const badge = document.getElementById('coverStatusBadge');
            badge.textContent = 'Banner Galeri Terpilih';
            badge.classList.remove('hidden');

            document.getElementById('aiCoverPath').value = data.cover_path;
            document.getElementById('removeCoverInput').value = '0';
            document.getElementById('coverFileInput').value = '';
            document.getElementById('btnRemoveCover').classList.remove('hidden');

            document.getElementById('aiPromptText').textContent = data.label + ' (Vektor Presisi Resolusi Tinggi)';
            document.getElementById('aiPromptNotice').classList.remove('hidden');
        } else {
            alert(data.message || 'Gagal menerapkan cover dari galeri.');
        }
    } catch (err) {
        console.error('Apply preset error:', err);
        alert('Terjadi kesalahan jaringan saat menerapkan banner.');
    } finally {
        overlay.classList.add('hidden');
        if (loadingText) loadingText.textContent = 'Menyusun Visual Cover dengan AI...';
    }
}

async function generateCoverWithAi() {
    const nameInput = document.querySelector('input[name="name"]').value.trim();
    const courseNameInput = document.querySelector('input[name="course_name"]').value.trim();
    const descInput = document.querySelector('textarea[name="description"]').value.trim();
    const styleSelect = document.getElementById('coverAiStyle');
    const style = styleSelect ? styleSelect.value : 'data_diagram';

    if (!nameInput) {
        alert('Harap isi Nama Kelas terlebih dahulu sebelum generate cover AI.');
        document.querySelector('input[name="name"]').focus();
        return;
    }

    const overlay = document.getElementById('aiLoadingOverlay');
    const loadingText = document.getElementById('aiLoadingText');
    const btn = document.getElementById('btnGenerateAi');
    const btnText = document.getElementById('btnGenerateAiText');

    if (style === 'data_diagram') {
        loadingText.textContent = 'Menyusun Diagram Ilmiah Presisi Vektor...';
    } else {
        loadingText.textContent = 'Menyusun Visual Cover dengan AI...';
    }

    overlay.classList.remove('hidden');
    btn.disabled = true;
    btn.classList.add('opacity-70', 'cursor-not-allowed');

    try {
        const response = await fetch("{{ route('pbm.classes.generate-cover-ai', $class->id) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                name: nameInput,
                course_name: courseNameInput,
                description: descInput,
                style: style
            })
        });

        const data = await response.json();
        if (response.ok && data.success) {
            const img = document.getElementById('coverPreviewImg');
            img.src = data.cover_url;
            img.classList.remove('hidden');
            document.getElementById('coverPlaceholder').classList.add('hidden');

            const badge = document.getElementById('coverStatusBadge');
            badge.textContent = style === 'data_diagram' ? 'Preview Diagram Presisi' : 'Preview Ilustrasi AI';
            badge.classList.remove('hidden');

            document.getElementById('aiCoverPath').value = data.cover_path;
            document.getElementById('removeCoverInput').value = '0';
            document.getElementById('coverFileInput').value = '';
            document.getElementById('btnRemoveCover').classList.remove('hidden');

            btnText.textContent = 'Generate Ulang by AI';

            if (data.prompt_used) {
                document.getElementById('aiPromptText').textContent = data.prompt_used;
                document.getElementById('aiPromptNotice').classList.remove('hidden');
            }
        } else {
            alert(data.message || 'Gagal membuat cover dengan AI. Silakan coba lagi.');
        }
    } catch (err) {
        console.error('AI Cover error:', err);
        alert('Terjadi kesalahan jaringan saat membuat cover dengan AI.');
    } finally {
        overlay.classList.add('hidden');
        loadingText.textContent = 'Menyusun Visual Cover dengan AI...';
        btn.disabled = false;
        btn.classList.remove('opacity-70', 'cursor-not-allowed');
    }
}
</script>
@endpush
