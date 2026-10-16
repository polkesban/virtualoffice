<?php

namespace App\Support;

use App\Models\Mahasiswa;
use App\Models\PageSection;
use App\Models\PageSectionSubmission;
use App\Models\UnitEvent;
use App\Models\UnitEventParticipant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PageFormBlock
{
    private const MODE_FORM = 'form';
    private const MODE_QUIZ = 'quiz';

    private const ACCESS_PUBLIC = 'public';
    private const ACCESS_EMPLOYEE_ONLY = 'employee_only';
    private const ACCESS_STUDENT_ONLY = 'student_only';
    private const ACCESS_EMPLOYEE_OR_STUDENT = 'employee_or_student';
    private const ACCESS_EVENT_PARTICIPANTS = 'event_participants';

    private const RESPONSE_LIMIT_NONE = 'none';
    private const RESPONSE_LIMIT_SESSION = 'session';
    private const RESPONSE_LIMIT_EMAIL = 'email';

    public static function normalizeConfigForStorage(array $config, string $type): array
    {
        if (in_array($type, ['ai_teks', 'hero'])) {
            return self::persistAiTextMedia(self::normalizeAiTextConfig($config));
        }

        if ($type === 'produk') {
            return self::persistProdukMedia(self::normalizeProdukConfig($config));
        }

        if ($type !== 'formulir') {
            return $config;
        }

        return self::persistInlineMediaAssets(self::normalizeConfig($config));
    }

    public static function attachAiTextUploadedMedia(array $config, ?UploadedFile $uploadedMedia = null, ?string $fallbackAlt = null): array
    {
        if (($config['gambar_url'] ?? '') !== '') {
            $config['gambar_url'] = self::persistMediaDataUrl($config['gambar_url'] ?? '', 'ai-teks-image');
        }

        if ($uploadedMedia instanceof UploadedFile) {
            $storedPath = MediaStorage::storeUploadedFile($uploadedMedia, 'page-builder-media/ai-teks');
            $config['gambar_url'] = (string) (MediaStorage::url($storedPath) ?? MediaStorage::browserUrl($storedPath) ?? $storedPath);
        }

        $config['gambar_alt'] = trim((string) ($config['gambar_alt'] ?? ''));

        if (($config['gambar_url'] ?? '') === '') {
            $config['gambar_alt'] = '';

            return $config;
        }

        if ($config['gambar_alt'] === '') {
            $config['gambar_alt'] = trim((string) $fallbackAlt);
        }

        if ($config['gambar_alt'] === '') {
            $config['gambar_alt'] = trim((string) ($config['_prompt_acara'] ?? ''));
        }

        if ($config['gambar_alt'] === '') {
            $config['gambar_alt'] = 'Ilustrasi blok AI teks';
        }

        return $config;
    }

    private static function normalizeAiTextConfig(array $config): array
    {
        return array_merge($config, [
            'konten' => trim((string) ($config['konten'] ?? '')),
            'gambar_url' => self::normalizeMediaUrl((string) ($config['gambar_url'] ?? '')),
            'gambar_alt' => trim((string) ($config['gambar_alt'] ?? '')),
            '_prompt_acara' => trim((string) ($config['_prompt_acara'] ?? '')),
            '_prompt_tempat' => trim((string) ($config['_prompt_tempat'] ?? '')),
            '_prompt_tanggal' => trim((string) ($config['_prompt_tanggal'] ?? '')),
            '_prompt_catatan' => trim((string) ($config['_prompt_catatan'] ?? '')),
        ]);
    }

    private static function persistAiTextMedia(array $config): array
    {
        $config['gambar_url'] = self::persistMediaDataUrl($config['gambar_url'] ?? '', 'ai-teks-image');

        if (($config['gambar_url'] ?? '') === '') {
            $config['gambar_alt'] = '';
        }

        return $config;
    }

    public static function normalizeProdukConfig(array $config): array
    {
        $items = array_values(array_filter(array_map(function ($item) {
            if (! is_array($item)) {
                return null;
            }

            $nama = trim((string) ($item['nama'] ?? ''));
            if ($nama === '') {
                return null;
            }

            $item['nama'] = $nama;
            $item['kategori'] = trim((string) ($item['kategori'] ?? 'produk')) ?: 'produk';
            $item['deskripsi'] = trim((string) ($item['deskripsi'] ?? ''));
            $item['harga'] = isset($item['harga']) && $item['harga'] !== '' ? (float) $item['harga'] : null;
            $item['satuan_harga'] = trim((string) ($item['satuan_harga'] ?? ''));
            $item['gambar'] = trim((string) ($item['gambar'] ?? ''));
            $item['link_pesan'] = trim((string) ($item['link_pesan'] ?? ''));
            $item['qris_image'] = trim((string) ($item['qris_image'] ?? ''));
            $item['qris_label'] = trim((string) ($item['qris_label'] ?? ''));
            $item['opsi_varian'] = is_array($item['opsi_varian'] ?? null)
                ? array_values(array_filter(array_map(function ($opt) {
                    if (! is_array($opt)) return null;
                    $label = trim((string) ($opt['label'] ?? ''));
                    if ($label === '') return null;
                    return [
                        'label'        => $label,
                        'harga'        => isset($opt['harga']) && $opt['harga'] !== '' ? (float) $opt['harga'] : null,
                        'satuan_harga' => trim((string) ($opt['satuan_harga'] ?? '')),
                        'gambar'       => trim((string) ($opt['gambar'] ?? '')),
                    ];
                }, $item['opsi_varian'])))
                : [];

            return $item;
        }, (array) ($config['items'] ?? []))));

        $config['items'] = $items;
        return $config;
    }

    public static function persistProdukMedia(array $config): array
    {
        if (! empty($config['items']) && is_array($config['items'])) {
            foreach ($config['items'] as $index => $item) {
                if (! empty($item['gambar'])) {
                    $config['items'][$index]['gambar'] = self::persistMediaDataUrl(
                        $item['gambar'],
                        'produk-' . Str::slug($item['nama'] ?? 'item', '-')
                    );
                }

                if (! empty($item['qris_image'])) {
                    $config['items'][$index]['qris_image'] = self::persistMediaDataUrl(
                        $item['qris_image'],
                        'qris-' . Str::slug($item['nama'] ?? 'item', '-')
                    );
                }

                if (! empty($item['opsi_varian']) && is_array($item['opsi_varian'])) {
                    foreach ($item['opsi_varian'] as $vIndex => $varian) {
                        if (! empty($varian['gambar'])) {
                            $config['items'][$index]['opsi_varian'][$vIndex]['gambar'] = self::persistMediaDataUrl(
                                $varian['gambar'],
                                'varian-' . Str::slug(($item['nama'] ?? 'item') . '-' . ($varian['label'] ?? 'opt'), '-')
                            );
                        }
                    }
                }
            }
        }

        self::syncProdukToLayananBisnis($config);

        return $config;
    }

    public static function syncProdukToLayananBisnis(array $config): void
    {
        if (empty($config['items']) || ! is_array($config['items'])) {
            return;
        }

        foreach ($config['items'] as $item) {
            $nama = trim((string) ($item['nama'] ?? ''));
            if ($nama === '') {
                continue;
            }

            $slug = Str::slug($nama);
            $layanan = \App\Models\LayananBisnis::where('nama', $nama)
                ->orWhere('slug', $slug)
                ->first();

            if (! $layanan) {
                $layanan = new \App\Models\LayananBisnis();
                $layanan->nama = $nama;
                $layanan->slug = $slug;
                $layanan->kategori = $item['kategori'] ?? 'produk';
                $layanan->aktif = true;
            }

            if (isset($item['harga']) && $item['harga'] !== '' && (float) $item['harga'] > 0) {
                $layanan->harga = (float) $item['harga'];
            }
            if (! empty($item['satuan_harga']) && $item['satuan_harga'] !== '0') {
                $layanan->satuan_harga = (string) $item['satuan_harga'];
            }
            if (! empty($item['deskripsi'])) {
                $layanan->deskripsi = (string) $item['deskripsi'];
            }
            if (! empty($item['gambar'])) {
                $layanan->gambar = $item['gambar'];
            }
            if (! empty($item['qris_image'])) {
                $layanan->qris_link = $item['qris_image'];
            }
            if (! empty($item['qris_label'])) {
                $layanan->qris_label = $item['qris_label'];
            }
            if (isset($item['opsi_varian']) && is_array($item['opsi_varian'])) {
                $layanan->opsi_varian = $item['opsi_varian'];
            }

            $layanan->save();
        }
    }

    public static function normalizeConfig(array $config = []): array
    {
        $fields = self::normalizeFields($config['fields'] ?? []);

        if ($fields === []) {
            $fields = self::normalizeFields([
                ['nama' => 'Nama Lengkap', 'tipe' => 'text', 'wajib' => true],
                ['nama' => 'Email', 'tipe' => 'email', 'wajib' => true],
                ['nama' => 'Pesan', 'tipe' => 'textarea', 'wajib' => true],
            ]);
        }

        return [
            'mode' => self::normalizeMode((string) ($config['mode'] ?? $config['form_mode'] ?? self::MODE_FORM)),
            'pengantar' => trim((string) ($config['pengantar'] ?? $config['intro'] ?? '')),
            'header_logo_url' => self::normalizeMediaUrl((string) ($config['header_logo_url'] ?? $config['logo_url'] ?? '')),
            'header_logo_alt' => trim((string) ($config['header_logo_alt'] ?? 'Logo formulir')),
            'header_image_url' => self::normalizeMediaUrl((string) ($config['header_image_url'] ?? $config['kop_url'] ?? $config['banner_url'] ?? '')),
            'header_image_alt' => trim((string) ($config['header_image_alt'] ?? 'Header formulir')),
            'pengantar_image_url' => self::normalizeMediaUrl((string) ($config['pengantar_image_url'] ?? $config['intro_image_url'] ?? '')),
            'pengantar_image_alt' => trim((string) ($config['pengantar_image_alt'] ?? 'Gambar pengantar formulir')),
            'passing_score' => self::normalizePassingScore($config['passing_score'] ?? $config['pass_mark'] ?? 70),
            'duration_minutes' => max(0, (int) ($config['duration_minutes'] ?? $config['timer_minutes'] ?? 0)),
            'opens_at' => self::normalizeDateTimeValue((string) ($config['opens_at'] ?? $config['open_at'] ?? '')),
            'closes_at' => self::normalizeDateTimeValue((string) ($config['closes_at'] ?? $config['close_at'] ?? '')),
            'scheduled_message' => trim((string) ($config['scheduled_message'] ?? '')) ?: 'Formulir ini belum dibuka.',
            'closed_message' => trim((string) ($config['closed_message'] ?? '')) ?: 'Akses formulir saat ini ditutup.',
            'time_limit_message' => trim((string) ($config['time_limit_message'] ?? '')) ?: 'Waktu pengerjaan untuk formulir ini sudah habis.',
            'show_rationales_after_submit' => array_key_exists('show_rationales_after_submit', $config) ? ! empty($config['show_rationales_after_submit']) : true,
            'access_scope' => self::normalizeAccessScope((string) ($config['access_scope'] ?? self::ACCESS_PUBLIC)),
            'access_event_id' => self::normalizeAccessEventId($config['access_event_id'] ?? null),
            'access_message' => trim((string) ($config['access_message'] ?? '')),
            'access_registration_code_label' => trim((string) ($config['access_registration_code_label'] ?? '')) ?: 'Kode registrasi event',
            'proctoring_enabled' => ! empty($config['proctoring_enabled']),
            'proctoring_require_fullscreen' => ! empty($config['proctoring_require_fullscreen']),
            'proctoring_block_copy_paste' => array_key_exists('proctoring_block_copy_paste', $config) ? ! empty($config['proctoring_block_copy_paste']) : true,
            'proctoring_block_context_menu' => array_key_exists('proctoring_block_context_menu', $config) ? ! empty($config['proctoring_block_context_menu']) : true,
            'proctoring_capture_selfie' => ! empty($config['proctoring_capture_selfie']),
            'proctoring_auto_submit' => ! empty($config['proctoring_auto_submit']),
            'proctoring_max_violations' => max(0, (int) ($config['proctoring_max_violations'] ?? 0)),
            'proctoring_notice' => trim((string) ($config['proctoring_notice'] ?? '')) ?: 'Mode proctoring aktif. Aktivitas seperti pindah tab, keluar fullscreen, dan percobaan copy atau paste akan dicatat.',
            'proctoring_selfie_label' => trim((string) ($config['proctoring_selfie_label'] ?? '')) ?: 'Ambil foto verifikasi peserta',
            'fields' => $fields,
            'email_tujuan' => trim((string) ($config['email_tujuan'] ?? '')),
            'pesan_sukses' => trim((string) ($config['pesan_sukses'] ?? '')) ?: 'Terima kasih, tanggapan Anda telah diterima.',
            'judul_submit' => trim((string) ($config['judul_submit'] ?? '')) ?: 'Kirim',
            'response_limit' => self::normalizeResponseLimit((string) ($config['response_limit'] ?? self::RESPONSE_LIMIT_NONE)),
            'limit_message' => trim((string) ($config['limit_message'] ?? '')) ?: 'Formulir ini hanya dapat dikirim satu kali.',
        ];
    }

    public static function modeOptions(): array
    {
        return [
            self::MODE_FORM,
            self::MODE_QUIZ,
        ];
    }

    public static function accessScopeOptions(): array
    {
        return [
            self::ACCESS_PUBLIC,
            self::ACCESS_EMPLOYEE_ONLY,
            self::ACCESS_STUDENT_ONLY,
            self::ACCESS_EMPLOYEE_OR_STUDENT,
            self::ACCESS_EVENT_PARTICIPANTS,
        ];
    }

    public static function responseLimitOptions(): array
    {
        return [
            self::RESPONSE_LIMIT_NONE,
            self::RESPONSE_LIMIT_SESSION,
            self::RESPONSE_LIMIT_EMAIL,
        ];
    }

    public static function fieldTypes(): array
    {
        return [
            'text',
            'email',
            'url',
            'date',
            'time',
            'number',
            'tel',
            'textarea',
            'radio',
            'checkbox',
            'select',
            'scale',
            'matrix',
            'file',
            'signature',
            'section',
            'divider',
        ];
    }

    public static function isQuiz(array $config): bool
    {
        return (self::normalizeConfig($config)['mode'] ?? self::MODE_FORM) === self::MODE_QUIZ;
    }

    public static function availabilityState(array $config, ?Carbon $now = null): array
    {
        $normalized = self::normalizeConfig($config);
        $now ??= now();

        $opensAt = self::parseDateTimeValue($normalized['opens_at'] ?? null);
        $closesAt = self::parseDateTimeValue($normalized['closes_at'] ?? null);

        if ($opensAt && $now->lt($opensAt)) {
            return [
                'status' => 'scheduled',
                'is_open' => false,
                'message' => (string) ($normalized['scheduled_message'] ?? 'Formulir ini belum dibuka.'),
                'opens_at' => $opensAt->toIso8601String(),
                'closes_at' => $closesAt?->toIso8601String(),
            ];
        }

        if ($closesAt && $now->gt($closesAt)) {
            return [
                'status' => 'closed',
                'is_open' => false,
                'message' => (string) ($normalized['closed_message'] ?? 'Akses formulir saat ini ditutup.'),
                'opens_at' => $opensAt?->toIso8601String(),
                'closes_at' => $closesAt->toIso8601String(),
            ];
        }

        return [
            'status' => 'open',
            'is_open' => true,
            'message' => null,
            'opens_at' => $opensAt?->toIso8601String(),
            'closes_at' => $closesAt?->toIso8601String(),
        ];
    }

    public static function accessState(PageSection $section, array $config, ?Request $request = null): array
    {
        $normalized = self::normalizeConfig($config);
        $scope = (string) ($normalized['access_scope'] ?? self::ACCESS_PUBLIC);
        $event = self::resolveAccessEvent($section, $normalized);
        $sessionAccess = $request?->session()->get(self::eventAccessSessionKey($section))
            ?? session(self::eventAccessSessionKey($section));
        $employee = Auth::user();
        $student = Auth::guard('mahasiswa')->user();

        if ($scope === self::ACCESS_PUBLIC) {
            return [
                'scope' => $scope,
                'authorized' => true,
                'message' => null,
                'event' => null,
                'participant' => null,
                'requires_registration_code' => false,
            ];
        }

        if ($scope === self::ACCESS_EMPLOYEE_ONLY) {
            return [
                'scope' => $scope,
                'authorized' => $employee instanceof User,
                'message' => $normalized['access_message'] !== ''
                    ? $normalized['access_message']
                    : 'Formulir ini hanya dapat diakses oleh pegawai Poltekkes Bandung.',
                'event' => null,
                'participant' => null,
                'requires_registration_code' => false,
            ];
        }

        if ($scope === self::ACCESS_STUDENT_ONLY) {
            return [
                'scope' => $scope,
                'authorized' => $student instanceof Mahasiswa,
                'message' => $normalized['access_message'] !== ''
                    ? $normalized['access_message']
                    : 'Formulir ini hanya dapat diakses oleh mahasiswa Poltekkes saja.',
                'event' => null,
                'participant' => null,
                'requires_registration_code' => false,
            ];
        }

        if ($scope === self::ACCESS_EMPLOYEE_OR_STUDENT) {
            return [
                'scope' => $scope,
                'authorized' => $employee instanceof User || $student instanceof Mahasiswa,
                'message' => $normalized['access_message'] !== ''
                    ? $normalized['access_message']
                    : 'Formulir ini hanya dapat diakses oleh pegawai atau mahasiswa Poltekkes.',
                'event' => null,
                'participant' => null,
                'requires_registration_code' => false,
            ];
        }

        $participant = null;

        if ($event && is_array($sessionAccess)) {
            $participant = $event->participants()->whereKey((int) ($sessionAccess['participant_id'] ?? 0))->first();
        }

        if (! $participant && $event) {
            $participant = app(UnitEventParticipantAccountResolver::class)
                ->findAuthenticatedParticipant($event, null, self::currentAccessPortal());
        }

        return [
            'scope' => $scope,
            'authorized' => $participant instanceof UnitEventParticipant,
            'message' => $normalized['access_message'] !== ''
                ? $normalized['access_message']
                : 'Formulir ini hanya dapat diakses oleh peserta event yang sudah terdaftar.',
            'event' => $event,
            'participant' => $participant,
            'requires_registration_code' => $participant === null,
        ];
    }

    public static function authorizeAccess(PageSection $section, array $config, Request $request): array
    {
        $state = self::accessState($section, $config, $request);

        if ($state['authorized'] ?? false) {
            return $state;
        }

        if (($state['scope'] ?? self::ACCESS_PUBLIC) !== self::ACCESS_EVENT_PARTICIPANTS) {
            return $state;
        }

        $event = $state['event'] ?? null;
        if (! $event instanceof UnitEvent) {
            return $state + ['message' => 'Event akses untuk formulir ini belum dikonfigurasi.'];
        }

        $registrationCode = Str::upper(trim((string) $request->input('_access_registration_code', '')));
        if ($registrationCode === '') {
            return $state + ['message' => 'Masukkan kode registrasi event untuk membuka formulir ini.'];
        }

        $participant = $event->participants()->where('kode_registrasi', $registrationCode)->first();
        if (! $participant instanceof UnitEventParticipant) {
            return $state + ['message' => 'Kode registrasi event tidak valid atau belum terdaftar pada kegiatan yang dipilih.'];
        }

        $request->session()->put(self::eventAccessSessionKey($section), [
            'participant_id' => $participant->id,
            'event_id' => $event->id,
            'granted_at' => now()->toIso8601String(),
        ]);

        return [
            'scope' => self::ACCESS_EVENT_PARTICIPANTS,
            'authorized' => true,
            'message' => null,
            'event' => $event,
            'participant' => $participant,
            'requires_registration_code' => false,
        ];
    }

    public static function visibleFields(array $fields, array $input = []): array
    {
        return array_values(array_filter($fields, static fn (array $field): bool => self::fieldIsVisible($field, $input)));
    }

    public static function fieldIsVisible(array $field, array $input = []): bool
    {
        $mode = self::normalizeVisibilityMode((string) ($field['visibility_mode'] ?? 'always'));
        $rules = self::normalizeVisibilityRules($field['visibility_rules'] ?? []);

        if ($mode !== 'conditional' || $rules === []) {
            return true;
        }

        $match = self::normalizeVisibilityMatch((string) ($field['visibility_match'] ?? 'all'));
        $results = array_map(static fn (array $rule): bool => self::ruleMatches($rule, $input), $rules);

        return $match === 'any'
            ? in_array(true, $results, true)
            : ! in_array(false, $results, true);
    }

    public static function acceptsResponse(array $field): bool
    {
        return ! in_array((string) ($field['tipe'] ?? 'text'), ['section', 'divider'], true);
    }

    public static function answerableFields(array $fields): array
    {
        return array_values(array_filter($fields, static fn (array $field): bool => self::acceptsResponse($field)));
    }

    public static function scaleValues(array $field): array
    {
        $min = max(1, (int) ($field['min'] ?? 1));
        $max = max($min + 1, (int) ($field['max'] ?? 4));

        return array_map(static fn (int $value): string => (string) $value, range($min, $max));
    }

    public static function validationRules(array $fields, array $input = []): array
    {
        $rules = [];
        $candidateFields = $input !== [] ? self::visibleFields($fields, $input) : $fields;

        foreach ($candidateFields as $field) {
            $key = (string) ($field['key'] ?? '');
            $type = (string) ($field['tipe'] ?? 'text');
            $required = ! empty($field['wajib']);

            if ($key === '' || ! self::acceptsResponse($field)) {
                continue;
            }

            switch ($type) {
                case 'email':
                    $rules["fields.{$key}"] = self::filterRules([
                        $required ? 'required' : 'nullable',
                        'string',
                        'max:2000',
                        'email:rfc',
                    ]);
                    break;

                case 'url':
                    $rules["fields.{$key}"] = self::filterRules([
                        $required ? 'required' : 'nullable',
                        'string',
                        'max:2000',
                        'url:http,https',
                    ]);
                    break;

                case 'date':
                    $rules["fields.{$key}"] = self::filterRules([
                        $required ? 'required' : 'nullable',
                        'date',
                    ]);
                    break;

                case 'time':
                    $rules["fields.{$key}"] = self::filterRules([
                        $required ? 'required' : 'nullable',
                        'date_format:H:i',
                    ]);
                    break;

                case 'number':
                    $rules["fields.{$key}"] = self::filterRules([
                        $required ? 'required' : 'nullable',
                        'numeric',
                    ]);
                    break;

                case 'radio':
                case 'select':
                    $rules["fields.{$key}"] = self::filterRules([
                        $required ? 'required' : 'nullable',
                        'string',
                        Rule::in(self::optionValues($field)),
                    ]);
                    break;

                case 'checkbox':
                    $rules["fields.{$key}"] = self::filterRules([
                        $required ? 'required' : 'nullable',
                        'array',
                        $required ? 'min:1' : null,
                    ]);
                    $rules["fields.{$key}.*"] = [
                        'string',
                        Rule::in(self::optionValues($field)),
                    ];
                    break;

                case 'scale':
                    $rules["fields.{$key}"] = self::filterRules([
                        $required ? 'required' : 'nullable',
                        Rule::in(self::scaleValues($field)),
                    ]);
                    break;

                case 'matrix':
                    $rules["fields.{$key}"] = self::filterRules([
                        $required ? 'required' : 'nullable',
                        'array',
                    ]);

                    foreach (self::statementRows($field) as $row) {
                        $rowKey = (string) ($row['key'] ?? '');

                        if ($rowKey === '') {
                            continue;
                        }

                        $rules["fields.{$key}.{$rowKey}"] = self::filterRules([
                            $required ? 'required' : 'nullable',
                            Rule::in(self::scaleValues($field)),
                        ]);
                    }
                    break;

                case 'file':
                    $extensions = self::acceptedFileExtensions($field);
                    $fileRules = self::filterRules([
                        'file',
                        'max:' . self::maxFileSizeKilobytes($field),
                        $extensions !== [] ? 'mimes:' . implode(',', $extensions) : null,
                    ]);

                    if (! empty($field['multiple'])) {
                        $rules["fields.{$key}"] = self::filterRules([
                            $required ? 'required' : 'nullable',
                            'array',
                            $required ? 'min:1' : null,
                            'max:' . self::maxFiles($field),
                        ]);
                        $rules["fields.{$key}.*"] = $fileRules;
                    } else {
                        $rules["fields.{$key}"] = self::filterRules([
                            $required ? 'required' : 'nullable',
                            ...$fileRules,
                        ]);
                    }
                    break;

                case 'signature':
                    $rules["fields.{$key}"] = self::filterRules([
                        $required ? 'required' : 'nullable',
                        'string',
                        'starts_with:data:image/png;base64,',
                        'max:' . self::maxSignatureCharacters($field),
                    ]);
                    break;

                case 'textarea':
                    $rules["fields.{$key}"] = self::filterRules([
                        $required ? 'required' : 'nullable',
                        'string',
                        'max:5000',
                        static function (string $attribute, mixed $value, $fail) use ($required): void {
                            if (! $required || ! is_string($value)) {
                                return;
                            }

                            if (self::normalizeRichTextToPlainText($value) === '') {
                                $fail('Kolom ini wajib diisi.');
                            }
                        },
                    ]);
                    break;

                case 'tel':
                    $rules["fields.{$key}"] = self::filterRules([
                        $required ? 'required' : 'nullable',
                        'string',
                        'max:50',
                    ]);
                    break;

                case 'text':
                default:
                    $rules["fields.{$key}"] = self::filterRules([
                        $required ? 'required' : 'nullable',
                        'string',
                        'max:2000',
                    ]);
                    break;
            }
        }

        return $rules;
    }

    public static function normalizeAnswers(array $fields, array $input, bool $respectVisibility = false): array
    {
        $answers = [];

        if ($respectVisibility) {
            $fields = self::visibleFields($fields, $input);
        }

        foreach ($fields as $field) {
            $key = (string) ($field['key'] ?? '');
            $type = (string) ($field['tipe'] ?? 'text');

            if ($key === '' || ! self::acceptsResponse($field)) {
                continue;
            }

            $value = $input[$key] ?? null;

            switch ($type) {
                case 'checkbox':
                    $selected = [];
                    foreach ((array) $value as $candidate) {
                        $resolved = self::resolveOptionValue($field, $candidate);
                        if ($resolved !== null && ! in_array($resolved, $selected, true)) {
                            $selected[] = $resolved;
                        }
                    }
                    $answers[$key] = $selected;
                    break;

                case 'radio':
                case 'select':
                    $answers[$key] = self::resolveOptionValue($field, $value);
                    break;

                case 'scale':
                    $answers[$key] = self::resolveScaleValue($field, $value);
                    break;

                case 'matrix':
                    $rowAnswers = [];
                    $rowsInput = is_array($value) ? $value : [];
                    foreach (self::statementRows($field) as $row) {
                        $rowKey = (string) ($row['key'] ?? '');
                        if ($rowKey === '') {
                            continue;
                        }

                        $resolved = self::resolveScaleValue($field, $rowsInput[$rowKey] ?? null);
                        if ($resolved !== null) {
                            $rowAnswers[$rowKey] = $resolved;
                        }
                    }
                    $answers[$key] = $rowAnswers;
                    break;

                case 'number':
                    $answers[$key] = $value === null || $value === '' ? null : (string) $value;
                    break;

                case 'textarea':
                    if (! is_string($value)) {
                        $answers[$key] = null;
                        break;
                    }

                    $trimmed = trim($value);
                    $answers[$key] = self::normalizeRichTextToPlainText($trimmed) === '' ? null : $trimmed;
                    break;

                case 'file':
                    $answers[$key] = ! empty($field['multiple']) ? [] : null;
                    break;

                default:
                    $answers[$key] = is_string($value) ? trim($value) : null;
                    break;
            }
        }

        return $answers;
    }

    public static function normalizeAnswersFromRequest(PageSection $section, array $fields, Request $request, bool $respectVisibility = false): array
    {
        $rawInput = (array) $request->input('fields', []);
        if ($respectVisibility) {
            $fields = self::visibleFields($fields, $rawInput);
        }

        $answers = self::normalizeAnswers($fields, $rawInput);

        foreach ($fields as $field) {
            if (($field['tipe'] ?? null) !== 'file' || ! self::acceptsResponse($field)) {
                continue;
            }

            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $storedFiles = self::storeUploadedFiles($section, $field, $request->file('fields.' . $key));
            $answers[$key] = ! empty($field['multiple'])
                ? $storedFiles
                : ($storedFiles[0] ?? null);
        }

        foreach ($fields as $field) {
            if (($field['tipe'] ?? null) !== 'signature' || ! self::acceptsResponse($field)) {
                continue;
            }

            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $answers[$key] = self::storeSignature($section, $field, $request->input('fields.' . $key));
        }

        return $answers;
    }

    public static function buildQuizAttemptPayload(array $config, array $fields, array $answers, ?string $startedAt = null, ?Carbon $submittedAt = null): array
    {
        $normalizedConfig = self::normalizeConfig($config);
        $submittedAt ??= now();
        $started = self::parseDateTimeValue($startedAt)
            ?? self::parseDateTimeValue((string) data_get($answers, '__quiz.started_at'));

        $visibleFields = self::visibleFields($fields, $answers);
        $answerableFields = self::answerableFields($visibleFields);
        $items = [];
        $totalScore = 0.0;
        $maxScore = 0.0;
        $correctCount = 0;
        $scorableCount = 0;

        foreach ($answerableFields as $field) {
            if (! self::isScoredField($field)) {
                continue;
            }

            $result = self::fieldScoreResult($field, data_get($answers, $field['key'] ?? ''));
            $items[$field['key']] = $result;
            $totalScore += $result['earned_score'];
            $maxScore += $result['max_score'];
            $scorableCount++;

            if ($result['correct'] === true) {
                $correctCount++;
            }
        }

        $durationLimitSeconds = max(0, (int) ($normalizedConfig['duration_minutes'] ?? 0)) * 60;
        $durationSeconds = $started instanceof Carbon
            ? max(0, (int) $started->diffInSeconds($submittedAt, false))
            : 0;
        $timedOut = $durationLimitSeconds > 0 && $durationSeconds > $durationLimitSeconds;
        $percent = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : null;
        $passed = $percent !== null ? $percent >= (float) ($normalizedConfig['passing_score'] ?? 70) : null;

        return [
            'started_at' => $started?->toIso8601String(),
            'submitted_at' => $submittedAt->toIso8601String(),
            'duration_seconds' => $durationSeconds,
            'duration_limit_seconds' => $durationLimitSeconds > 0 ? $durationLimitSeconds : null,
            'timed_out' => $timedOut,
            'score' => round(max(0, $totalScore), 2),
            'max_score' => round(max(0, $maxScore), 2),
            'percent' => $percent,
            'passing_score' => (float) ($normalizedConfig['passing_score'] ?? 70),
            'passed' => $passed,
            'scorable_count' => $scorableCount,
            'correct_count' => $correctCount,
            'show_rationales_after_submit' => ! empty($normalizedConfig['show_rationales_after_submit']),
            'items' => $items,
        ];
    }

    public static function extractProctoringPayload(PageSection $section, array $config, Request $request): ?array
    {
        $normalized = self::normalizeConfig($config);

        if (! self::isQuiz($normalized) || empty($normalized['proctoring_enabled'])) {
            return null;
        }

        $raw = (array) $request->input('_proctoring', []);
        $events = [];

        if (is_string($raw['events_json'] ?? null) && trim((string) $raw['events_json']) !== '') {
            $decoded = json_decode((string) $raw['events_json'], true);
            if (is_array($decoded)) {
                $events = array_values(array_filter($decoded, static fn ($event): bool => is_array($event)));
            }
        }

        $device = [];
        if (is_string($raw['device_json'] ?? null) && trim((string) $raw['device_json']) !== '') {
            $decoded = json_decode((string) $raw['device_json'], true);
            if (is_array($decoded)) {
                $device = $decoded;
            }
        }

        $counts = collect($events)
            ->pluck('type')
            ->filter(static fn ($type): bool => is_string($type) && trim($type) !== '')
            ->countBy();

        return [
            'enabled' => true,
            'require_fullscreen' => ! empty($normalized['proctoring_require_fullscreen']),
            'block_copy_paste' => ! empty($normalized['proctoring_block_copy_paste']),
            'block_context_menu' => ! empty($normalized['proctoring_block_context_menu']),
            'capture_selfie' => ! empty($normalized['proctoring_capture_selfie']),
            'auto_submit' => ! empty($normalized['proctoring_auto_submit']),
            'max_violations' => max(0, (int) ($normalized['proctoring_max_violations'] ?? 0)),
            'started_at' => is_string($raw['started_at'] ?? null) ? trim((string) $raw['started_at']) : null,
            'events' => array_slice($events, -60),
            'event_count' => count($events),
            'suspicious_count' => max(0, (int) ($raw['suspicious_count'] ?? count($events))),
            'tab_switch_count' => (int) ($counts['visibility_hidden'] ?? 0) + (int) ($counts['window_blur'] ?? 0),
            'fullscreen_exit_count' => (int) ($counts['fullscreen_exit'] ?? 0),
            'clipboard_violation_count' => (int) ($counts['copy_attempt'] ?? 0) + (int) ($counts['paste_attempt'] ?? 0) + (int) ($counts['cut_attempt'] ?? 0),
            'device' => $device,
            'selfie' => self::storeProctoringSelfie($section, $raw['selfie'] ?? null),
            'user_agent' => (string) $request->userAgent(),
            'ip' => $request->ip(),
        ];
    }

    public static function mailLines(array $fields, array $answers): array
    {
        $lines = [];

        foreach (self::answerableFields($fields) as $field) {
            $label = trim((string) ($field['nama'] ?? 'Pertanyaan'));
            $value = $answers[$field['key'] ?? ''] ?? null;
            $display = self::displayAnswer($field, $value, true);

            if ($display === '') {
                continue;
            }

            $lines[] = $label . ':';

            foreach (preg_split('/\r?\n/', $display) ?: [] as $line) {
                $lines[] = '  ' . $line;
            }
        }

        return $lines;
    }

    public static function displayAnswer(array $field, mixed $value, bool $includeUrls = false): string
    {
        $type = (string) ($field['tipe'] ?? 'text');

        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        if (in_array($type, ['file', 'signature'], true)) {
            $files = self::normalizedStoredFiles($value);
            if ($files === []) {
                return '';
            }

            return implode("\n", array_map(static function (array $file) use ($includeUrls): string {
                $label = trim((string) ($file['name'] ?? 'Berkas'));
                $size = trim((string) ($file['size_label'] ?? ''));
                $meta = $size !== '' ? ' (' . $size . ')' : '';

                if ($includeUrls && ! empty($file['url'])) {
                    return $label . $meta . ' - ' . $file['url'];
                }

                return $label . $meta;
            }, $files));
        }

        if (in_array($type, ['radio', 'select'], true)) {
            return self::optionLabelForValue($field, (string) $value);
        }

        if ($type === 'checkbox') {
            $labels = [];
            foreach ((array) $value as $item) {
                $labels[] = self::optionLabelForValue($field, (string) $item);
            }

            return implode(', ', array_filter($labels));
        }

        if ($type === 'matrix') {
            $lines = [];
            $rowMap = collect(self::statementRows($field))->keyBy('key');
            foreach ((array) $value as $rowKey => $selected) {
                $label = (string) ($rowMap->get($rowKey)['label'] ?? $rowKey);
                $lines[] = $label . ': ' . $selected;
            }

            return implode("\n", $lines);
        }

        if ($type === 'textarea') {
            return self::normalizeRichTextToPlainText($value);
        }

        if (is_array($value)) {
            return implode(', ', array_filter(array_map('strval', $value)));
        }

        return trim((string) $value);
    }

    private static function normalizeRichTextToPlainText(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $text = is_scalar($value) ? (string) $value : '';
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/<\s*br\s*\/??\s*>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<(li|tr)[^>]*>/i', '- ', $text) ?? $text;
        $text = preg_replace('/<\/(p|div|li|h[1-6]|tr|blockquote|ul|ol)>/i', "\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $lines = array_map(static function (string $line): string {
            $normalized = preg_replace('/\s+/u', ' ', $line) ?? $line;
            return trim($normalized);
        }, explode("\n", $text));

        return trim(implode("\n", array_values(array_filter($lines, static fn (string $line): bool => $line !== ''))));
    }

    public static function summary(PageSection $section, int $recentLimit = 12, array $filters = []): array
    {
        $config = self::normalizeConfig($section->konfigurasi ?? []);
        $fields = $config['fields'];
        $answerableFields = self::answerableFields($fields);
        $submissions = self::submissions($section, $filters);
        $quizSummary = self::isQuiz($config) ? self::summarizeQuiz($config, $fields, $submissions) : null;

        return [
            'section_id' => $section->id,
            'section_title' => $section->judul ?: 'Formulir',
            'mode' => $config['mode'] ?? self::MODE_FORM,
            'access_scope' => $config['access_scope'] ?? self::ACCESS_PUBLIC,
            'total_responses' => $submissions->count(),
            'field_count' => count($answerableFields),
            'updated_at' => optional($submissions->first()?->created_at)->toIso8601String(),
            'updated_label' => optional($submissions->first()?->created_at)?->diffForHumans(),
            'filters' => self::normalizeFilters($filters),
            'availability' => self::availabilityState($config),
            'response_limit' => $config['response_limit'] ?? self::RESPONSE_LIMIT_NONE,
            'quiz' => $quizSummary,
            'fields' => array_map(
                static fn (array $field): array => self::summarizeField($field, $submissions),
                $answerableFields,
            ),
            'responses' => $submissions->take($recentLimit)->map(
                fn ($submission): array => self::formatRecentResponse(
                    $answerableFields,
                    $submission->data ?? [],
                    $submission->id,
                    $submission->created_at?->format('d M Y H:i'),
                    self::isQuiz($config) ? self::quizPayloadForSubmission($config, $fields, $submission) : null
                )
            )->values()->all(),
        ];
    }

    public static function submissions(PageSection $section, array $filters = []): Collection
    {
        $normalized = self::normalizeFilters($filters);

        return PageSectionSubmission::query()
            ->where('section_id', $section->id)
            ->when($normalized['from'] !== null, fn ($query) => $query->whereDate('created_at', '>=', $normalized['from']))
            ->when($normalized['until'] !== null, fn ($query) => $query->whereDate('created_at', '<=', $normalized['until']))
            ->latest()
            ->get();
    }

    public static function firstEmailFieldKey(array $fields): ?string
    {
        foreach ($fields as $field) {
            if (($field['tipe'] ?? null) === 'email' && ! empty($field['key'])) {
                return (string) $field['key'];
            }
        }

        return null;
    }

    public static function hasDuplicateEmailSubmission(PageSection $section, array $fields, array $answers): bool
    {
        $emailKey = self::firstEmailFieldKey($fields);
        $email = $emailKey ? trim(strtolower((string) ($answers[$emailKey] ?? ''))) : '';

        if ($email === '') {
            return false;
        }

        return self::submissions($section)
            ->contains(static function (PageSectionSubmission $submission) use ($emailKey, $email): bool {
                return trim(strtolower((string) data_get($submission->data ?? [], $emailKey, ''))) === $email;
            });
    }

    public static function duplicateMessage(array $config): string
    {
        $normalized = self::normalizeConfig($config);

        return (string) ($normalized['limit_message'] ?? 'Formulir ini hanya dapat dikirim satu kali.');
    }

    public static function exportHeadingRow(array $fields, array $config = []): array
    {
        $answerableFields = self::answerableFields($fields);

        $headings = array_merge(
            ['ID Respons', 'Waktu Submit'],
            array_map(static fn (array $field): string => (string) ($field['nama'] ?? 'Pertanyaan'), $answerableFields)
        );

        if (! self::isQuiz($config)) {
            return $headings;
        }

        return array_merge($headings, ['Skor Quiz', 'Skor Maksimum', 'Persentase', 'Lulus', 'Durasi (detik)', 'Waktu Habis']);
    }

    public static function exportDataRows(array $fields, Collection $submissions, array $config = []): array
    {
        $answerableFields = self::answerableFields($fields);

        return $submissions->map(function (PageSectionSubmission $submission) use ($answerableFields, $fields, $config): array {
            $row = [
                $submission->id,
                $submission->created_at?->format('Y-m-d H:i:s') ?? '',
            ];

            foreach ($answerableFields as $field) {
                $value = self::displayAnswer($field, data_get($submission->data ?? [], $field['key'] ?? ''), true);
                $row[] = str_replace(["\r\n", "\r", "\n"], ' | ', $value);
            }

            if (self::isQuiz($config)) {
                $quiz = self::quizPayloadForSubmission($config, $fields, $submission);
                $row[] = $quiz['score'] ?? '';
                $row[] = $quiz['max_score'] ?? '';
                $row[] = $quiz['percent'] ?? '';
                $row[] = isset($quiz['passed']) ? ($quiz['passed'] ? 'Lulus' : 'Belum Lulus') : '';
                $row[] = $quiz['duration_seconds'] ?? '';
                $row[] = ! empty($quiz['timed_out']) ? 'Ya' : 'Tidak';
            }

            return $row;
        })->values()->all();
    }

    private static function summarizeField(array $field, Collection $submissions): array
    {
        $key = (string) ($field['key'] ?? '');
        $type = (string) ($field['tipe'] ?? 'text');
        $base = [
            'key' => $key,
            'label' => (string) ($field['nama'] ?? 'Pertanyaan'),
            'type' => $type,
            'description' => (string) ($field['deskripsi'] ?? ''),
            'required' => ! empty($field['wajib']),
        ];

        if ($key === '') {
            return $base + ['response_count' => 0];
        }

        if (in_array($type, ['radio', 'select', 'checkbox'], true)) {
            return $base + self::summarizeChoiceField($field, $submissions);
        }

        if ($type === 'scale') {
            return $base + self::summarizeScaleField($field, $submissions);
        }

        if ($type === 'matrix') {
            return $base + self::summarizeMatrixField($field, $submissions);
        }

        if (in_array($type, ['file', 'signature'], true)) {
            return $base + self::summarizeFileField($field, $submissions);
        }

        return $base + self::summarizeTextField($field, $submissions);
    }

    private static function summarizeFileField(array $field, Collection $submissions): array
    {
        $responseCount = 0;
        $fileCount = 0;
        $counts = [];
        $recentFiles = collect();

        foreach ($submissions as $submission) {
            $files = self::normalizedStoredFiles($submission->data[$field['key']] ?? null);
            if ($files === []) {
                continue;
            }

            $responseCount++;

            foreach ($files as $file) {
                $fileCount++;
                $extension = self::fileExtensionLabel($file['name'] ?? $file['path'] ?? 'tanpa-ekstensi');
                $counts[$extension] = ($counts[$extension] ?? 0) + 1;
                $recentFiles->push($file + [
                    'submitted_at' => $submission->created_at?->format('d M Y H:i'),
                ]);
            }
        }

        arsort($counts);

        return [
            'response_count' => $responseCount,
            'file_count' => $fileCount,
            'visualization' => 'files',
            'chart' => $fileCount > 0 ? 'bar' : null,
            'accept' => (string) ($field['accept'] ?? ''),
            'multiple' => ! empty($field['multiple']),
            'items' => collect($counts)->map(static fn (int $count, string $extension): array => [
                'value' => $extension,
                'label' => strtoupper($extension),
                'count' => $count,
                'percent' => self::percentage($count, $fileCount),
            ])->values()->all(),
            'files' => $recentFiles->take(10)->values()->all(),
        ];
    }

    private static function summarizeChoiceField(array $field, Collection $submissions): array
    {
        $options = self::normalizedOptions($field);
        $counts = collect($options)->mapWithKeys(static fn (array $option): array => [$option['value'] => 0]);
        $responseCount = 0;

        foreach ($submissions as $submission) {
            $value = $submission->data[$field['key']] ?? null;

            if ($field['tipe'] === 'checkbox') {
                $selected = [];
                foreach ((array) $value as $candidate) {
                    $resolved = self::resolveOptionValue($field, $candidate);
                    if ($resolved !== null && ! in_array($resolved, $selected, true)) {
                        $selected[] = $resolved;
                    }
                }

                if ($selected !== []) {
                    $responseCount++;
                }

                foreach ($selected as $selectedValue) {
                    $counts[$selectedValue] = ($counts[$selectedValue] ?? 0) + 1;
                }

                continue;
            }

            $resolved = self::resolveOptionValue($field, $value);
            if ($resolved === null) {
                continue;
            }

            $responseCount++;
            $counts[$resolved] = ($counts[$resolved] ?? 0) + 1;
        }

        return [
            'response_count' => $responseCount,
            'visualization' => 'bar',
            'chart' => 'bar',
            'items' => collect($options)->map(static fn (array $option) => [
                'value' => $option['value'],
                'label' => $option['label'],
                'media_url' => $option['media_url'] ?? '',
                'media_alt' => $option['media_alt'] ?? $option['label'],
                'score' => round((float) ($option['score'] ?? 0), 2),
                'is_correct' => ! empty($option['is_correct']),
                'count' => $counts[$option['value']] ?? 0,
                'percent' => self::percentage($counts[$option['value']] ?? 0, $responseCount),
            ])->values()->all(),
            'options' => collect($options)->map(static fn (array $option) => [
                'value' => $option['value'],
                'label' => $option['label'],
                'media_url' => $option['media_url'] ?? '',
                'media_alt' => $option['media_alt'] ?? $option['label'],
                'score' => round((float) ($option['score'] ?? 0), 2),
                'is_correct' => ! empty($option['is_correct']),
                'count' => $counts[$option['value']] ?? 0,
                'percent' => self::percentage($counts[$option['value']] ?? 0, $responseCount),
            ])->values()->all(),
        ];
    }

    private static function summarizeScaleField(array $field, Collection $submissions): array
    {
        $values = self::scaleValues($field);
        $counts = array_fill_keys($values, 0);
        $responseCount = 0;
        $total = 0;

        foreach ($submissions as $submission) {
            $resolved = self::resolveScaleValue($field, $submission->data[$field['key']] ?? null);
            if ($resolved === null) {
                continue;
            }

            $responseCount++;
            $counts[$resolved] = ($counts[$resolved] ?? 0) + 1;
            $total += (int) $resolved;
        }

        return [
            'response_count' => $responseCount,
            'visualization' => 'bar',
            'chart' => 'bar',
            'average' => $responseCount > 0 ? round($total / $responseCount, 2) : null,
            'scale' => [
                'min' => (int) ($field['min'] ?? 1),
                'max' => (int) ($field['max'] ?? 4),
                'min_label' => (string) ($field['min_label'] ?? ''),
                'max_label' => (string) ($field['max_label'] ?? ''),
            ],
            'range' => [
                'min' => (int) ($field['min'] ?? 1),
                'max' => (int) ($field['max'] ?? 4),
                'min_label' => (string) ($field['min_label'] ?? ''),
                'max_label' => (string) ($field['max_label'] ?? ''),
            ],
            'items' => array_map(static fn (string $value) => [
                'value' => $value,
                'label' => $value,
                'count' => $counts[$value] ?? 0,
                'percent' => self::percentage($counts[$value] ?? 0, $responseCount),
            ], $values),
            'options' => array_map(static fn (string $value) => [
                'value' => $value,
                'label' => $value,
                'count' => $counts[$value] ?? 0,
                'percent' => self::percentage($counts[$value] ?? 0, $responseCount),
            ], $values),
        ];
    }

    private static function summarizeMatrixField(array $field, Collection $submissions): array
    {
        $values = self::scaleValues($field);
        $rows = [];

        foreach (self::statementRows($field) as $row) {
            $rowKey = (string) ($row['key'] ?? '');
            $counts = array_fill_keys($values, 0);
            $responseCount = 0;
            $total = 0;

            foreach ($submissions as $submission) {
                $raw = $submission->data[$field['key']] ?? [];
                $resolved = self::resolveScaleValue($field, is_array($raw) ? ($raw[$rowKey] ?? null) : null);
                if ($resolved === null) {
                    continue;
                }

                $responseCount++;
                $counts[$resolved] = ($counts[$resolved] ?? 0) + 1;
                $total += (int) $resolved;
            }

            $rows[] = [
                'key' => $rowKey,
                'label' => $row['label'],
                'response_count' => $responseCount,
                'average' => $responseCount > 0 ? round($total / $responseCount, 2) : null,
                'items' => array_map(static fn (string $value) => [
                    'value' => $value,
                    'label' => $value,
                    'count' => $counts[$value] ?? 0,
                    'percent' => self::percentage($counts[$value] ?? 0, $responseCount),
                ], $values),
                'options' => array_map(static fn (string $value) => [
                    'value' => $value,
                    'label' => $value,
                    'count' => $counts[$value] ?? 0,
                    'percent' => self::percentage($counts[$value] ?? 0, $responseCount),
                ], $values),
            ];
        }

        return [
            'response_count' => collect($rows)->max('response_count') ?? 0,
            'visualization' => 'matrix',
            'chart' => 'matrix',
            'scale' => [
                'min' => (int) ($field['min'] ?? 1),
                'max' => (int) ($field['max'] ?? 4),
                'min_label' => (string) ($field['min_label'] ?? ''),
                'max_label' => (string) ($field['max_label'] ?? ''),
            ],
            'range' => [
                'min' => (int) ($field['min'] ?? 1),
                'max' => (int) ($field['max'] ?? 4),
                'min_label' => (string) ($field['min_label'] ?? ''),
                'max_label' => (string) ($field['max_label'] ?? ''),
            ],
            'rows' => $rows,
        ];
    }

    private static function summarizeTextField(array $field, Collection $submissions): array
    {
        $entries = collect();

        foreach ($submissions as $submission) {
            $display = self::displayAnswer($field, $submission->data[$field['key']] ?? null);
            if ($display === '') {
                continue;
            }

            $entries->push([
                'value' => $display,
                'submitted_at' => $submission->created_at?->format('d M Y H:i'),
            ]);
        }

        $topAnswers = $entries
            ->pluck('value')
            ->countBy()
            ->sortDesc()
            ->take(8)
            ->map(static fn (int $count, string $value): array => [
                'value' => $value,
                'count' => $count,
            ])
            ->values()
            ->all();

        return [
            'response_count' => $entries->count(),
            'visualization' => 'list',
            'chart' => null,
            'items' => $entries->pluck('value')->take(10)->values()->all(),
            'top_answers' => $topAnswers,
            'responses' => $entries->take(10)->values()->all(),
        ];
    }

    private static function formatRecentResponse(array $fields, array $answers, int $id, ?string $submittedAt, ?array $quizPayload = null): array
    {
        $formattedAnswers = [];

        foreach ($fields as $field) {
            $key = (string) ($field['key'] ?? '');

            if (in_array((string) ($field['tipe'] ?? null), ['file', 'signature'], true)) {
                $files = self::normalizedStoredFiles($answers[$key] ?? null);
                if ($files === []) {
                    continue;
                }

                $formattedAnswers[] = [
                    'label' => (string) ($field['nama'] ?? 'Pertanyaan'),
                    'type' => (string) ($field['tipe'] ?? 'file'),
                    'value' => implode(', ', array_map(static fn (array $file): string => (string) ($file['name'] ?? 'Berkas'), $files)),
                    'files' => $files,
                ];

                continue;
            }

            $value = self::displayAnswer($field, $answers[$key] ?? null);
            if ($value === '') {
                continue;
            }

            $formattedAnswers[] = [
                'label' => (string) ($field['nama'] ?? 'Pertanyaan'),
                'type' => (string) ($field['tipe'] ?? 'text'),
                'value' => $value,
            ];
        }

        return [
            'id' => $id,
            'submitted_at' => $submittedAt,
            'quiz' => $quizPayload,
            'proctoring' => is_array($answers['__proctoring'] ?? null) ? $answers['__proctoring'] : null,
            'answers' => $formattedAnswers,
        ];
    }

    private static function normalizeFields(mixed $rawFields): array
    {
        $fields = [];
        $usedKeys = [];

        foreach (is_array($rawFields) ? $rawFields : [] as $index => $field) {
            if (! is_array($field)) {
                continue;
            }

            $label = trim((string) ($field['nama'] ?? $field['label'] ?? ''));
            $type = strtolower(trim((string) ($field['tipe'] ?? 'text')));
            if (! in_array($type, self::fieldTypes(), true)) {
                $type = 'text';
            }

            $label = $label !== ''
                ? $label
                : match ($type) {
                    'section' => 'Bagian Baru',
                    'divider' => 'Pemisah',
                    default => 'Pertanyaan ' . ($index + 1),
                };

            $fields[] = [
                'key' => self::uniqueSlug((string) ($field['key'] ?? ''), $label, 'field_' . ($index + 1), $usedKeys),
                'nama' => $label,
                'tipe' => $type,
                'wajib' => self::acceptsResponse(['tipe' => $type])
                    ? (! empty($field['wajib']) || ! empty($field['required']))
                    : false,
                'deskripsi' => trim((string) ($field['deskripsi'] ?? $field['help'] ?? '')),
                'deskripsi_media_url' => self::normalizeMediaUrl((string) ($field['deskripsi_media_url'] ?? $field['help_media_url'] ?? '')),
                'deskripsi_media_alt' => trim((string) ($field['deskripsi_media_alt'] ?? ($label !== '' ? 'Ilustrasi ' . $label : 'Ilustrasi pertanyaan'))),
                'visibility_mode' => self::normalizeVisibilityMode((string) ($field['visibility_mode'] ?? (! empty($field['visibility_rules'] ?? $field['show_if'] ?? []) ? 'conditional' : 'always'))),
                'visibility_match' => self::normalizeVisibilityMatch((string) ($field['visibility_match'] ?? 'all')),
                'visibility_rules' => self::normalizeVisibilityRules($field['visibility_rules'] ?? $field['show_if'] ?? []),
                'correct_rationale' => trim((string) ($field['correct_rationale'] ?? $field['rationale_correct'] ?? '')),
                'incorrect_rationale' => trim((string) ($field['incorrect_rationale'] ?? $field['rationale_incorrect'] ?? '')),
                'placeholder' => trim((string) ($field['placeholder'] ?? '')),
                'opsi' => self::normalizeOptions($field['opsi'] ?? $field['options'] ?? [], $type),
                'question_weight' => max(0, round((float) ($field['question_weight'] ?? $field['weight'] ?? 1), 2)),
                'answer_key' => self::normalizeAnswerKey($field['answer_key'] ?? $field['kunci_jawaban'] ?? null, $type),
                'answer_key_case_sensitive' => ! empty($field['answer_key_case_sensitive']) || ! empty($field['case_sensitive']),
                'min' => max(1, (int) ($field['min'] ?? $field['scale_min'] ?? 1)),
                'max' => max(
                    max(1, (int) ($field['min'] ?? $field['scale_min'] ?? 1)) + 1,
                    (int) ($field['max'] ?? $field['scale_max'] ?? 4)
                ),
                'min_label' => trim((string) ($field['min_label'] ?? '')), 
                'max_label' => trim((string) ($field['max_label'] ?? '')),
                'pernyataan' => self::normalizeStatements($field['pernyataan'] ?? $field['rows'] ?? [], $type),
                'multiple' => ! empty($field['multiple']) || ! empty($field['allow_multiple']),
                'max_files' => max(1, (int) ($field['max_files'] ?? 1)),
                'max_size_mb' => max(1, (int) ($field['max_size_mb'] ?? 10)),
                'accept' => self::normalizeAcceptList((string) ($field['accept'] ?? $field['accepted_types'] ?? '.pdf,.doc,.docx,.jpg,.jpeg,.png')),
                'canvas_height' => max(120, (int) ($field['canvas_height'] ?? 220)),
                'stroke_color' => self::normalizeHexColor((string) ($field['stroke_color'] ?? '#111827'), '#111827'),
                'background_color' => self::normalizeHexColor((string) ($field['background_color'] ?? '#ffffff'), '#ffffff'),
            ];
        }

        return $fields;
    }

    private static function normalizeOptions(mixed $rawOptions, string $type): array
    {
        $options = [];
        $usedValues = [];

        foreach (is_array($rawOptions) ? $rawOptions : [] as $index => $option) {
            $label = '';
            $value = '';
            $mediaUrl = '';
            $mediaAlt = '';
            $score = 0.0;
            $isCorrect = false;

            if (is_array($option)) {
                $label = trim((string) ($option['label'] ?? $option['nama'] ?? ''));
                $value = trim((string) ($option['value'] ?? $option['key'] ?? ''));
                $mediaUrl = self::normalizeMediaUrl((string) ($option['media_url'] ?? $option['image_url'] ?? $option['icon_url'] ?? ''));
                $mediaAlt = trim((string) ($option['media_alt'] ?? $label));
                $score = round((float) ($option['score'] ?? $option['weight'] ?? 0), 2);
                $isCorrect = ! empty($option['is_correct']) || ! empty($option['benar']);
            } else {
                $label = trim((string) $option);
                $mediaAlt = $label;
            }

            if ($label === '') {
                continue;
            }

            $options[] = [
                'value' => self::uniqueSlug($value, $label, 'opsi_' . ($index + 1), $usedValues),
                'label' => $label,
                'media_url' => $mediaUrl,
                'media_alt' => $mediaAlt !== '' ? $mediaAlt : $label,
                'score' => $score,
                'is_correct' => $isCorrect,
                'rationale' => trim((string) ($option['rationale'] ?? $option['penjelasan'] ?? '')),
            ];
        }

        if ($options === [] && in_array($type, ['radio', 'checkbox', 'select'], true)) {
            return [
                ['value' => 'opsi_1', 'label' => 'Pilihan 1', 'media_url' => '', 'media_alt' => 'Pilihan 1', 'score' => 0.0, 'is_correct' => false, 'rationale' => ''],
                ['value' => 'opsi_2', 'label' => 'Pilihan 2', 'media_url' => '', 'media_alt' => 'Pilihan 2', 'score' => 0.0, 'is_correct' => false, 'rationale' => ''],
            ];
        }

        return $options;
    }

    private static function normalizeStatements(mixed $rawStatements, string $type): array
    {
        $rows = [];
        $usedKeys = [];

        foreach (is_array($rawStatements) ? $rawStatements : [] as $index => $row) {
            $label = '';
            $key = '';

            if (is_array($row)) {
                $label = trim((string) ($row['label'] ?? $row['nama'] ?? ''));
                $key = trim((string) ($row['key'] ?? ''));
            } else {
                $label = trim((string) $row);
            }

            if ($label === '') {
                continue;
            }

            $rows[] = [
                'key' => self::uniqueSlug($key, $label, 'baris_' . ($index + 1), $usedKeys),
                'label' => $label,
                'score' => max(0, round((float) ($row['score'] ?? $row['weight'] ?? 1), 2)),
                'correct_value' => self::normalizeAnswerKey($row['correct_value'] ?? $row['answer_key'] ?? null, 'scale'),
                'correct_rationale' => trim((string) ($row['correct_rationale'] ?? '')),
                'incorrect_rationale' => trim((string) ($row['incorrect_rationale'] ?? '')),
            ];
        }

        if ($rows === [] && $type === 'matrix') {
            return [
                ['key' => 'baris_1', 'label' => 'Pernyataan 1', 'score' => 1.0, 'correct_value' => null, 'correct_rationale' => '', 'incorrect_rationale' => ''],
                ['key' => 'baris_2', 'label' => 'Pernyataan 2', 'score' => 1.0, 'correct_value' => null, 'correct_rationale' => '', 'incorrect_rationale' => ''],
            ];
        }

        return $rows;
    }

    private static function filterRules(array $rules): array
    {
        return array_values(array_filter($rules, static fn ($rule): bool => $rule !== null && $rule !== ''));
    }

    private static function normalizeAcceptList(string $value): string
    {
        $parts = preg_split('/[,\s]+/', strtolower(trim($value))) ?: [];
        $normalized = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            if ($part[0] !== '.' && ! str_contains($part, '/')) {
                $part = '.' . ltrim($part, '.');
            }

            $normalized[$part] = true;
        }

        return implode(',', array_keys($normalized));
    }

    private static function normalizeMediaUrl(string $value): string
    {
        return trim($value);
    }

    private static function persistInlineMediaAssets(array $config): array
    {
        foreach (['header_logo_url', 'header_image_url', 'pengantar_image_url'] as $mediaKey) {
            $config[$mediaKey] = self::persistMediaDataUrl($config[$mediaKey] ?? '', $mediaKey);
        }

        $config['fields'] = array_map(static function (array $field): array {
            $field['deskripsi_media_url'] = self::persistMediaDataUrl(
                $field['deskripsi_media_url'] ?? '',
                'field-' . ($field['key'] ?? 'media') . '-help'
            );

            $field['opsi'] = array_map(static function (array $option) use ($field): array {
                $option['media_url'] = self::persistMediaDataUrl(
                    $option['media_url'] ?? '',
                    'field-' . ($field['key'] ?? 'media') . '-option-' . ($option['value'] ?? 'item')
                );

                return $option;
            }, $field['opsi'] ?? []);

            return $field;
        }, $config['fields'] ?? []);

        return $config;
    }

    private static function persistMediaDataUrl(mixed $value, string $prefix): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (! preg_match('/^data:image\/(png|jpe?g|webp|gif|svg\+xml);base64,(.+)$/i', $value, $matches)) {
            return $value;
        }

        $binary = base64_decode($matches[2], true);
        if ($binary === false || $binary === '') {
            return $value;
        }

        $extension = strtolower($matches[1]);
        $extension = $extension === 'jpeg' ? 'jpg' : str_replace('+xml', '', $extension);
        $safePrefix = Str::slug($prefix, '-');
        $path = 'page-builder-media/forms/' . now()->format('Y/m') . '/' . $safePrefix . '-' . Str::uuid() . '.' . $extension;
        $mimeType = $extension === 'svg' ? 'image/svg+xml' : 'image/' . $extension;
        $storedPath = MediaStorage::put($path, $binary, $mimeType);

        return (string) (MediaStorage::browserUrl($storedPath) ?? MediaStorage::url($storedPath) ?? $storedPath);
    }

    private static function normalizeMode(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, self::modeOptions(), true)
            ? $value
            : self::MODE_FORM;
    }

    private static function normalizePassingScore(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 70.0;
        }

        return round(min(100, max(0, (float) $value)), 2);
    }

    private static function normalizeAccessScope(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, self::accessScopeOptions(), true)
            ? $value
            : self::ACCESS_PUBLIC;
    }

    private static function normalizeAccessEventId(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private static function normalizeDateTimeValue(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d\TH:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private static function parseDateTimeValue(?string $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function resolveAccessEvent(PageSection $section, array $config): ?UnitEvent
    {
        $eventId = self::normalizeAccessEventId($config['access_event_id'] ?? null);

        if ($eventId === null) {
            return null;
        }

        if ($section->page) {
            return $section->page->events()->whereKey($eventId)->first();
        }

        return UnitEvent::query()->whereKey($eventId)->first();
    }

    private static function currentAccessPortal(): ?string
    {
        if (Auth::guard('mahasiswa')->user() instanceof Mahasiswa) {
            return 'mahasiswa';
        }

        if (Auth::user() instanceof User) {
            return 'pegawai';
        }

        return null;
    }

    private static function eventAccessSessionKey(PageSection $section): string
    {
        return 'page_form_access.' . $section->id;
    }

    private static function normalizeVisibilityMode(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, ['always', 'conditional'], true) ? $value : 'always';
    }

    private static function normalizeVisibilityMatch(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, ['all', 'any'], true) ? $value : 'all';
    }

    private static function normalizeVisibilityRules(mixed $rawRules): array
    {
        if (is_array($rawRules) && array_key_exists('source_key', $rawRules)) {
            $rawRules = [$rawRules];
        }

        $rules = [];

        foreach (is_array($rawRules) ? $rawRules : [] as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $sourceKey = trim((string) ($rule['source_key'] ?? $rule['field_key'] ?? $rule['key'] ?? ''));
            if ($sourceKey === '') {
                continue;
            }

            $value = $rule['value'] ?? $rule['expected'] ?? null;
            if (is_array($value)) {
                $value = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $value), static fn (string $item): bool => $item !== ''));
            } elseif ($value !== null && ! is_scalar($value)) {
                $value = null;
            } elseif ($value !== null) {
                $value = trim((string) $value);
            }

            $rules[] = [
                'source_key' => $sourceKey,
                'operator' => self::normalizeVisibilityOperator((string) ($rule['operator'] ?? $rule['op'] ?? 'equals')),
                'value' => $value,
            ];
        }

        return $rules;
    }

    private static function normalizeVisibilityOperator(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, ['equals', 'not_equals', 'contains', 'not_contains', 'filled', 'empty', 'gt', 'gte', 'lt', 'lte'], true)
            ? $value
            : 'equals';
    }

    private static function normalizeAnswerKey(mixed $value, string $type): mixed
    {
        return match ($type) {
            'checkbox' => self::normalizeAnswerKeyList($value),
            'matrix' => self::normalizeAnswerKeyMap($value),
            default => self::normalizeAnswerKeyScalar($value),
        };
    }

    private static function normalizeAnswerKeyList(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[,\n]+/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $item) {
            $item = trim((string) $item);
            if ($item === '' || in_array($item, $normalized, true)) {
                continue;
            }

            $normalized[] = $item;
        }

        return $normalized;
    }

    private static function normalizeAnswerKeyMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            $key = trim((string) $key);
            $item = self::normalizeAnswerKeyScalar($item);

            if ($key === '' || $item === null) {
                continue;
            }

            $normalized[$key] = $item;
        }

        return $normalized;
    }

    private static function normalizeAnswerKeyScalar(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private static function ruleMatches(array $rule, array $input): bool
    {
        $actual = $input[$rule['source_key']] ?? null;
        $expected = $rule['value'] ?? null;
        $operator = $rule['operator'] ?? 'equals';

        return match ($operator) {
            'filled' => ! self::isEmptyAnswer($actual),
            'empty' => self::isEmptyAnswer($actual),
            'contains' => self::containsValue($actual, $expected),
            'not_contains' => ! self::containsValue($actual, $expected),
            'gt' => self::compareNumeric($actual, $expected, '>'),
            'gte' => self::compareNumeric($actual, $expected, '>='),
            'lt' => self::compareNumeric($actual, $expected, '<'),
            'lte' => self::compareNumeric($actual, $expected, '<='),
            'not_equals' => ! self::equalsValue($actual, $expected),
            default => self::equalsValue($actual, $expected),
        };
    }

    private static function isEmptyAnswer(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }

    private static function containsValue(mixed $actual, mixed $expected): bool
    {
        if (is_array($actual)) {
            $actualValues = array_map(static fn ($item): string => trim((string) $item), $actual);
            if (is_array($expected)) {
                foreach ($expected as $expectedValue) {
                    if (in_array(trim((string) $expectedValue), $actualValues, true)) {
                        return true;
                    }
                }

                return false;
            }

            return in_array(trim((string) $expected), $actualValues, true);
        }

        if ($actual === null || $expected === null) {
            return false;
        }

        return str_contains(Str::lower(trim((string) $actual)), Str::lower(trim((string) $expected)));
    }

    private static function equalsValue(mixed $actual, mixed $expected): bool
    {
        if (is_array($actual)) {
            $actual = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $actual), static fn (string $item): bool => $item !== ''));
            $expected = is_array($expected)
                ? array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $expected), static fn (string $item): bool => $item !== ''))
                : [trim((string) $expected)];

            sort($actual);
            sort($expected);

            return $actual === $expected;
        }

        return trim((string) $actual) === trim((string) $expected);
    }

    private static function compareNumeric(mixed $actual, mixed $expected, string $operator): bool
    {
        if (! is_numeric($actual) || ! is_numeric($expected)) {
            return false;
        }

        $actual = (float) $actual;
        $expected = (float) $expected;

        return match ($operator) {
            '>' => $actual > $expected,
            '>=' => $actual >= $expected,
            '<' => $actual < $expected,
            '<=' => $actual <= $expected,
            default => false,
        };
    }

    private static function summarizeQuiz(array $config, array $fields, Collection $submissions): array
    {
        $attempts = $submissions
            ->map(fn (PageSectionSubmission $submission): array => self::quizPayloadForSubmission($config, $fields, $submission))
            ->values();

        $attemptCount = $attempts->count();
        $percents = $attempts->pluck('percent')->filter(static fn ($value): bool => $value !== null)->values();
        $scores = $attempts->pluck('score')->values();
        $passCount = $attempts->filter(static fn (array $attempt): bool => $attempt['passed'] === true)->count();
        $failCount = $attempts->filter(static fn (array $attempt): bool => $attempt['passed'] === false)->count();
        $timedOutCount = $attempts->filter(static fn (array $attempt): bool => ! empty($attempt['timed_out']))->count();

        return [
            'enabled' => true,
            'passing_score' => (float) ($config['passing_score'] ?? 70),
            'duration_minutes' => (int) ($config['duration_minutes'] ?? 0),
            'opens_at' => self::parseDateTimeValue($config['opens_at'] ?? null)?->toIso8601String(),
            'closes_at' => self::parseDateTimeValue($config['closes_at'] ?? null)?->toIso8601String(),
            'attempt_count' => $attemptCount,
            'pass_count' => $passCount,
            'fail_count' => $failCount,
            'pass_rate' => self::percentage($passCount, max(1, $attemptCount)),
            'timed_out_count' => $timedOutCount,
            'average_score' => $scores->isNotEmpty() ? round((float) $scores->avg(), 2) : null,
            'average_percent' => $percents->isNotEmpty() ? round((float) $percents->avg(), 2) : null,
            'highest_score' => $scores->isNotEmpty() ? round((float) $scores->max(), 2) : null,
            'lowest_score' => $scores->isNotEmpty() ? round((float) $scores->min(), 2) : null,
            'proctoring' => self::summarizeProctoring($submissions),
            'items' => array_values(array_filter(array_map(
                fn (array $field): ?array => self::isScoredField($field)
                    ? self::summarizeQuizField($field, $attempts)
                    : null,
                self::answerableFields(self::visibleFields($fields))
            ))),
        ];
    }

    private static function summarizeProctoring(Collection $submissions): array
    {
        $records = $submissions
            ->map(static fn (PageSectionSubmission $submission): ?array => is_array($submission->data ?? null) ? ($submission->data['__proctoring'] ?? null) : null)
            ->filter(static fn (?array $record): bool => is_array($record) && ! empty($record['enabled']))
            ->values();

        return [
            'enabled' => $records->isNotEmpty(),
            'submission_count' => $records->count(),
            'selfie_count' => $records->filter(static fn (array $record): bool => ! empty($record['selfie']))->count(),
            'total_suspicious_count' => $records->sum(static fn (array $record): int => (int) ($record['suspicious_count'] ?? 0)),
            'tab_switch_count' => $records->sum(static fn (array $record): int => (int) ($record['tab_switch_count'] ?? 0)),
            'fullscreen_exit_count' => $records->sum(static fn (array $record): int => (int) ($record['fullscreen_exit_count'] ?? 0)),
            'clipboard_violation_count' => $records->sum(static fn (array $record): int => (int) ($record['clipboard_violation_count'] ?? 0)),
        ];
    }

    private static function summarizeQuizField(array $field, Collection $attempts): array
    {
        $fieldKey = (string) ($field['key'] ?? '');
        $fieldResults = $attempts
            ->map(static fn (array $attempt): ?array => $attempt['items'][$fieldKey] ?? null)
            ->filter(static fn (?array $item): bool => is_array($item))
            ->values();

        $responseCount = $fieldResults->count();
        $answeredCount = $fieldResults->filter(static fn (array $result): bool => ! empty($result['answered']))->count();
        $correctCount = $fieldResults->filter(static fn (array $result): bool => $result['correct'] === true)->count();
        $maxScore = $fieldResults->max('max_score') ?? 0;
        $averageScore = $fieldResults->isNotEmpty() ? round((float) $fieldResults->avg('earned_score'), 2) : null;
        $averagePercent = $fieldResults
            ->pluck('percent')
            ->filter(static fn ($value): bool => $value !== null)
            ->avg();
        $difficultyIndex = $responseCount > 0 ? round(($correctCount / $responseCount) * 100, 2) : 0.0;

        return [
            'key' => $fieldKey,
            'label' => (string) ($field['nama'] ?? 'Pertanyaan'),
            'type' => (string) ($field['tipe'] ?? 'text'),
            'max_score' => round((float) $maxScore, 2),
            'response_count' => $responseCount,
            'answered_count' => $answeredCount,
            'correct_count' => $correctCount,
            'correct_rate' => $responseCount > 0 ? round(($correctCount / $responseCount) * 100, 2) : 0.0,
            'omission_rate' => $responseCount > 0 ? round((($responseCount - $answeredCount) / $responseCount) * 100, 2) : 0.0,
            'average_score' => $averageScore,
            'average_percent' => $averagePercent !== null ? round((float) $averagePercent, 2) : null,
            'difficulty_index' => $difficultyIndex,
            'difficulty_label' => self::difficultyLabel($difficultyIndex),
            'discrimination_index' => self::discriminationIndex($fieldKey, $attempts),
            'option_analysis' => self::optionAnalysis($field, $attempts),
        ];
    }

    private static function difficultyLabel(float $difficultyIndex): string
    {
        return match (true) {
            $difficultyIndex >= 80 => 'Sangat Mudah',
            $difficultyIndex >= 60 => 'Mudah',
            $difficultyIndex >= 40 => 'Sedang',
            $difficultyIndex >= 20 => 'Sulit',
            default => 'Sangat Sulit',
        };
    }

    private static function discriminationIndex(string $fieldKey, Collection $attempts): ?float
    {
        $count = $attempts->count();
        if ($count < 4) {
            return null;
        }

        $groupSize = max(1, (int) floor($count * 0.27));
        $ranked = $attempts
            ->sortByDesc(static fn (array $attempt): float => (float) ($attempt['percent'] ?? $attempt['score'] ?? 0))
            ->values();

        $upper = $ranked->take($groupSize);
        $lower = $ranked->slice(-$groupSize)->values();

        $upperAvg = self::groupItemAveragePercent($upper, $fieldKey);
        $lowerAvg = self::groupItemAveragePercent($lower, $fieldKey);

        if ($upperAvg === null || $lowerAvg === null) {
            return null;
        }

        return round($upperAvg - $lowerAvg, 3);
    }

    private static function groupItemAveragePercent(Collection $attempts, string $fieldKey): ?float
    {
        $percents = $attempts
            ->map(static fn (array $attempt) => $attempt['items'][$fieldKey]['percent'] ?? null)
            ->filter(static fn ($value): bool => $value !== null)
            ->values();

        return $percents->isNotEmpty() ? round(((float) $percents->avg()) / 100, 3) : null;
    }

    private static function optionAnalysis(array $field, Collection $attempts): array
    {
        $type = (string) ($field['tipe'] ?? 'text');
        if (! in_array($type, ['radio', 'checkbox', 'select'], true)) {
            return [];
        }

        return array_map(static function (array $option) use ($attempts, $field): array {
            $selectionCount = $attempts->filter(static function (array $attempt) use ($field, $option): bool {
                $selected = $attempt['items'][$field['key']]['selected_values'] ?? [];

                return in_array($option['value'], is_array($selected) ? $selected : [], true);
            })->count();

            return [
                'value' => $option['value'],
                'label' => $option['label'],
                'score' => round((float) ($option['score'] ?? 0), 2),
                'is_correct' => ! empty($option['is_correct']),
                'selection_count' => $selectionCount,
                'selection_rate' => self::percentage($selectionCount, max(1, $attempts->count())),
                'effective_distractor' => empty($option['is_correct']) && self::percentage($selectionCount, max(1, $attempts->count())) >= 5,
            ];
        }, self::normalizedOptions($field));
    }

    private static function quizPayloadForSubmission(array $config, array $fields, PageSectionSubmission $submission): array
    {
        $stored = is_array($submission->data ?? null) ? ($submission->data['__quiz'] ?? null) : null;
        $startedAt = is_array($stored) ? ($stored['started_at'] ?? null) : null;

        return self::buildQuizAttemptPayload(
            $config,
            $fields,
            is_array($submission->data ?? null) ? $submission->data : [],
            is_string($startedAt) ? $startedAt : null,
            $submission->created_at instanceof Carbon ? $submission->created_at : null,
        );
    }

    private static function isScoredField(array $field): bool
    {
        return self::configuredFieldMaxScore($field) > 0;
    }

    private static function configuredFieldMaxScore(array $field): float
    {
        $type = (string) ($field['tipe'] ?? 'text');
        $questionWeight = max(0, (float) ($field['question_weight'] ?? 0));
        $maxScore = 0.0;

        if (in_array($type, ['radio', 'checkbox', 'select'], true)) {
            if (self::correctOptionValues($field) !== []) {
                $maxScore += $questionWeight;
            }

            $maxScore += self::optionScoreMaximum($field);

            return round($maxScore, 2);
        }

        if ($type === 'matrix') {
            $hasCorrectRows = false;
            foreach (self::statementRows($field) as $row) {
                if (self::normalizeAnswerKeyScalar($row['correct_value'] ?? null) === null) {
                    continue;
                }

                $hasCorrectRows = true;
                $maxScore += max(0, (float) ($row['score'] ?? 1));
            }

            if ($hasCorrectRows) {
                $maxScore += $questionWeight;
            }

            return round($maxScore, 2);
        }

        if (in_array($type, ['file', 'signature', 'section', 'divider'], true)) {
            return 0.0;
        }

        return self::normalizeAnswerKeyScalar($field['answer_key'] ?? null) !== null
            ? round($questionWeight, 2)
            : 0.0;
    }

    private static function fieldScoreResult(array $field, mixed $value): array
    {
        $key = (string) ($field['key'] ?? '');
        $type = (string) ($field['tipe'] ?? 'text');
        $questionWeight = max(0, (float) ($field['question_weight'] ?? 0));
        $earned = 0.0;
        $maxScore = 0.0;
        $correct = null;
        $answered = ! self::isEmptyAnswer($value);
        $selectedValues = [];
        $rationale = '';

        if (in_array($type, ['radio', 'select'], true)) {
            $selectedValues = self::resolvedSelectedOptionValues($field, $value);
            $answered = $selectedValues !== [];
            $correctValues = self::correctOptionValues($field);

            if ($correctValues !== []) {
                $maxScore += $questionWeight;

                if ($answered) {
                    $correct = in_array($selectedValues[0] ?? null, $correctValues, true);
                    if ($correct) {
                        $earned += $questionWeight;
                    }
                }
            }

            $maxScore += self::optionScoreMaximum($field);
            $earned += self::selectedOptionScore($field, $selectedValues);
            $rationale = self::choiceFieldRationale($field, $selectedValues, $correct);
        } elseif ($type === 'checkbox') {
            $selectedValues = self::resolvedSelectedOptionValues($field, $value);
            $answered = $selectedValues !== [];
            $correctValues = self::correctOptionValues($field);

            if ($correctValues !== []) {
                $maxScore += $questionWeight;

                if ($answered) {
                    $selected = $selectedValues;
                    $expected = $correctValues;
                    sort($selected);
                    sort($expected);
                    $correct = $selected === $expected;
                    if ($correct) {
                        $earned += $questionWeight;
                    }
                }
            }

            $maxScore += self::optionScoreMaximum($field);
            $earned += self::selectedOptionScore($field, $selectedValues);
            $rationale = self::choiceFieldRationale($field, $selectedValues, $correct);
        } elseif ($type === 'scale') {
            $resolved = self::resolveScaleValue($field, $value);
            $answered = $resolved !== null;
            $answerKey = self::normalizeAnswerKeyScalar($field['answer_key'] ?? null);

            if ($answerKey !== null) {
                $maxScore += $questionWeight;
                if ($answered) {
                    $correct = $resolved === $answerKey;
                    if ($correct) {
                        $earned += $questionWeight;
                    }
                }
            }

            $rationale = self::fieldRationale($field, $correct);
        } elseif ($type === 'matrix') {
            $rows = self::statementRows($field);
            $answers = is_array($value) ? $value : [];
            $allCorrect = true;
            $hasCorrectRows = false;
            $answered = false;

            foreach ($rows as $row) {
                $rowKey = (string) ($row['key'] ?? '');
                $rowAnswer = self::resolveScaleValue($field, $answers[$rowKey] ?? null);
                $rowCorrect = self::normalizeAnswerKeyScalar($row['correct_value'] ?? null)
                    ?? self::normalizeAnswerKeyScalar($field['answer_key'][$rowKey] ?? null);

                if ($rowAnswer !== null) {
                    $answered = true;
                }

                if ($rowCorrect === null) {
                    continue;
                }

                $hasCorrectRows = true;
                $rowScore = max(0, (float) ($row['score'] ?? 1));
                $maxScore += $rowScore;

                if ($rowAnswer === $rowCorrect) {
                    $earned += $rowScore;
                    continue;
                }

                $allCorrect = false;
            }

            if ($hasCorrectRows) {
                $maxScore += $questionWeight;

                if ($answered) {
                    $correct = $allCorrect;
                    if ($correct) {
                        $earned += $questionWeight;
                    }
                }
            }

            $rationale = self::fieldRationale($field, $correct);
        } elseif (! in_array($type, ['file', 'signature', 'section', 'divider'], true)) {
            $answerKey = self::normalizeAnswerKeyScalar($field['answer_key'] ?? null);

            if ($answerKey !== null) {
                $maxScore += $questionWeight;

                if ($answered) {
                    $correct = self::matchesScalarAnswer($field, $value, $answerKey);
                    if ($correct) {
                        $earned += $questionWeight;
                    }
                }
            }

            $rationale = self::fieldRationale($field, $correct);
        }

        return [
            'key' => $key,
            'label' => (string) ($field['nama'] ?? 'Pertanyaan'),
            'type' => $type,
            'answered' => $answered,
            'correct' => $correct,
            'earned_score' => round(max(0, $earned), 2),
            'max_score' => round(max(0, $maxScore), 2),
            'percent' => $maxScore > 0 ? round((max(0, $earned) / $maxScore) * 100, 2) : null,
            'selected_values' => $selectedValues,
            'rationale' => $rationale,
        ];
    }

    private static function fieldRationale(array $field, ?bool $correct): string
    {
        if ($correct === true) {
            return trim((string) ($field['correct_rationale'] ?? ''));
        }

        if ($correct === false) {
            return trim((string) ($field['incorrect_rationale'] ?? ''));
        }

        return '';
    }

    private static function choiceFieldRationale(array $field, array $selectedValues, ?bool $correct): string
    {
        $optionRationales = collect(self::normalizedOptions($field))
            ->filter(static fn (array $option): bool => in_array((string) ($option['value'] ?? ''), $selectedValues, true))
            ->pluck('rationale')
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(static fn (string $value): string => trim($value))
            ->unique()
            ->values()
            ->all();

        if ($optionRationales !== []) {
            return implode("\n", $optionRationales);
        }

        return self::fieldRationale($field, $correct);
    }

    private static function correctOptionValues(array $field): array
    {
        $type = (string) ($field['tipe'] ?? 'text');
        if (! in_array($type, ['radio', 'checkbox', 'select'], true)) {
            return [];
        }

        $answerKey = $field['answer_key'] ?? null;
        if ($type === 'checkbox') {
            $normalized = self::normalizeAnswerKeyList($answerKey);
            if ($normalized !== []) {
                return $normalized;
            }
        } else {
            $normalized = self::normalizeAnswerKeyScalar($answerKey);
            if ($normalized !== null) {
                return [$normalized];
            }
        }

        return array_values(array_map(
            static fn (array $option): string => (string) $option['value'],
            array_filter(self::normalizedOptions($field), static fn (array $option): bool => ! empty($option['is_correct']))
        ));
    }

    private static function optionScoreMaximum(array $field): float
    {
        $type = (string) ($field['tipe'] ?? 'text');
        $positiveScores = array_map(
            static fn (array $option): float => max(0, (float) ($option['score'] ?? 0)),
            self::normalizedOptions($field)
        );

        if ($positiveScores === []) {
            return 0.0;
        }

        return round(in_array($type, ['radio', 'select'], true) ? max($positiveScores) : array_sum($positiveScores), 2);
    }

    private static function selectedOptionScore(array $field, array $selectedValues): float
    {
        if ($selectedValues === []) {
            return 0.0;
        }

        $scores = collect(self::normalizedOptions($field))
            ->filter(static fn (array $option): bool => in_array((string) ($option['value'] ?? ''), $selectedValues, true))
            ->sum(static fn (array $option): float => (float) ($option['score'] ?? 0));

        return round(max(0, (float) $scores), 2);
    }

    private static function resolvedSelectedOptionValues(array $field, mixed $value): array
    {
        if (($field['tipe'] ?? null) === 'checkbox') {
            $selected = [];
            foreach ((array) $value as $candidate) {
                $resolved = self::resolveOptionValue($field, $candidate);
                if ($resolved !== null && ! in_array($resolved, $selected, true)) {
                    $selected[] = $resolved;
                }
            }

            return $selected;
        }

        $resolved = self::resolveOptionValue($field, $value);

        return $resolved !== null ? [$resolved] : [];
    }

    private static function matchesScalarAnswer(array $field, mixed $value, string $answerKey): bool
    {
        if (($field['tipe'] ?? null) === 'textarea') {
            $actual = self::normalizeRichTextToPlainText($value);
            $expected = self::normalizeRichTextToPlainText($answerKey);
        } else {
            $actual = trim((string) $value);
            $expected = trim((string) $answerKey);
        }

        if (! empty($field['answer_key_case_sensitive'])) {
            return $actual === $expected;
        }

        return Str::lower($actual) === Str::lower($expected);
    }

    private static function storeProctoringSelfie(PageSection $section, mixed $value): ?array
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (! preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/i', $value, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[2], true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $extension = strtolower($matches[1]);
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $filename = 'proctoring-selfie-' . Str::uuid() . '.' . $extension;
        $path = 'page-form-uploads/section-' . $section->id . '/' . now()->format('Y/m') . '/' . $filename;
        $storedPath = MediaStorage::put($path, $binary, 'image/' . $extension);

        return [
            'path' => $storedPath,
            'name' => $filename,
            'size' => strlen($binary),
            'mime_type' => 'image/' . $extension,
            'url' => MediaStorage::downloadUrl($storedPath) ?? '',
            'browser_url' => MediaStorage::browserUrl($storedPath) ?? '',
            'is_image' => true,
        ];
    }

    private static function normalizeHexColor(string $value, string $fallback): string
    {
        $value = strtolower(trim($value));

        return preg_match('/^#[0-9a-f]{6}$/', $value) === 1 ? $value : $fallback;
    }

    private static function normalizeResponseLimit(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, self::responseLimitOptions(), true)
            ? $value
            : self::RESPONSE_LIMIT_NONE;
    }

    private static function normalizeFilters(array $filters): array
    {
        $from = trim((string) ($filters['from'] ?? ''));
        $until = trim((string) ($filters['until'] ?? ''));

        return [
            'from' => $from !== '' ? $from : null,
            'until' => $until !== '' ? $until : null,
        ];
    }

    private static function maxFiles(array $field): int
    {
        return max(1, (int) ($field['max_files'] ?? 1));
    }

    private static function maxFileSizeKilobytes(array $field): int
    {
        return max(1, (int) ($field['max_size_mb'] ?? 10)) * 1024;
    }

    private static function maxSignatureCharacters(array $field): int
    {
        return max(1, (int) ($field['max_size_mb'] ?? 2)) * 1_600_000;
    }

    private static function acceptedFileExtensions(array $field): array
    {
        $tokens = preg_split('/[,\s]+/', strtolower(trim((string) ($field['accept'] ?? '')))) ?: [];
        $extensions = [];

        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            if (str_starts_with($token, '.')) {
                $extensions[] = ltrim($token, '.');
                continue;
            }

            $extensions = array_merge($extensions, match ($token) {
                'image/*' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'],
                'audio/*' => ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'],
                'video/*' => ['mp4', 'mov', 'avi', 'mkv', 'webm', 'm4v'],
                'application/pdf' => ['pdf'],
                default => preg_match('/^[a-z0-9]+$/', $token) ? [$token] : [],
            });
        }

        return array_values(array_unique(array_filter($extensions)));
    }

    private static function storeUploadedFiles(PageSection $section, array $field, mixed $uploaded): array
    {
        $uploads = [];
        $files = is_array($uploaded) ? $uploaded : ($uploaded instanceof UploadedFile ? [$uploaded] : []);

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = MediaStorage::storeUploadedFile(
                $file,
                'page-form-uploads/section-' . $section->id . '/' . now()->format('Y/m')
            );

            $uploads[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'size' => (int) ($file->getSize() ?? 0),
                'mime_type' => (string) ($file->getClientMimeType() ?? 'application/octet-stream'),
            ];
        }

        return $uploads;
    }

    private static function storeSignature(PageSection $section, array $field, mixed $rawValue): ?array
    {
        if (! is_string($rawValue) || trim($rawValue) === '') {
            return null;
        }

        $rawValue = trim($rawValue);
        if (! preg_match('/^data:image\/(png);base64,(.+)$/', $rawValue, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[2], true);
        if ($binary === false || $binary === '') {
            return null;
        }

        $extension = strtolower($matches[1]);
        $filename = Str::slug((string) ($field['nama'] ?? 'signature'), '_') . '-' . Str::uuid() . '.' . $extension;
        $path = 'page-form-uploads/section-' . $section->id . '/' . now()->format('Y/m') . '/' . $filename;

        $storedPath = MediaStorage::put($path, $binary, 'image/' . $extension);

        return [
            'path' => $storedPath,
            'name' => 'signature-' . now()->format('Ymd-His') . '.' . $extension,
            'size' => strlen($binary),
            'mime_type' => 'image/' . $extension,
        ];
    }

    private static function normalizedStoredFiles(mixed $value): array
    {
        if (is_string($value) && trim($value) !== '') {
            return [self::normalizeStoredFilePayload(['path' => trim($value)])];
        }

        if (! is_array($value)) {
            return [];
        }

        if (self::looksLikeStoredFilePayload($value)) {
            return [self::normalizeStoredFilePayload($value)];
        }

        $files = [];
        foreach ($value as $item) {
            if (! is_array($item) && ! is_string($item)) {
                continue;
            }

            $normalized = self::normalizeStoredFilePayload(is_array($item) ? $item : ['path' => $item]);
            if ($normalized['url'] === '' && $normalized['name'] === '') {
                continue;
            }

            $files[] = $normalized;
        }

        return $files;
    }

    private static function looksLikeStoredFilePayload(array $value): bool
    {
        return array_key_exists('path', $value)
            || array_key_exists('url', $value)
            || array_key_exists('name', $value)
            || array_key_exists('mime_type', $value);
    }

    private static function normalizeStoredFilePayload(array $value): array
    {
        $path = trim((string) ($value['path'] ?? $value['url'] ?? ''));
        $name = trim((string) ($value['name'] ?? MediaStorage::fileName($path, 'berkas')));
        $size = max(0, (int) ($value['size'] ?? 0));

        return [
            'path' => $path,
            'name' => $name,
            'size' => $size,
            'size_label' => self::formatFileSize($size),
            'mime_type' => trim((string) ($value['mime_type'] ?? MediaStorage::mimeType($path) ?? '')),
            'url' => trim((string) ($value['url'] ?? MediaStorage::downloadUrl($path) ?? '')),
            'browser_url' => trim((string) ($value['browser_url'] ?? MediaStorage::browserUrl($path) ?? '')),
            'is_image' => str_starts_with(trim((string) ($value['mime_type'] ?? MediaStorage::mimeType($path) ?? '')), 'image/'),
        ];
    }

    private static function formatFileSize(int $size): string
    {
        if ($size <= 0) {
            return '';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $value = (float) $size;
        $unitIndex = 0;

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        return number_format($value, $value >= 100 || $unitIndex === 0 ? 0 : 1, '.', '') . ' ' . $units[$unitIndex];
    }

    private static function fileExtensionLabel(string $name): string
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : 'lainnya';
    }

    private static function optionValues(array $field): array
    {
        return array_map(static fn (array $option): string => (string) $option['value'], self::normalizedOptions($field));
    }

    private static function normalizedOptions(array $field): array
    {
        return self::normalizeOptions($field['opsi'] ?? [], (string) ($field['tipe'] ?? 'text'));
    }

    private static function statementRows(array $field): array
    {
        return self::normalizeStatements($field['pernyataan'] ?? [], (string) ($field['tipe'] ?? 'text'));
    }

    private static function optionLabelForValue(array $field, string $value): string
    {
        foreach (self::normalizedOptions($field) as $option) {
            if ($option['value'] === $value || $option['label'] === $value) {
                return $option['label'];
            }
        }

        return $value;
    }

    private static function resolveOptionValue(array $field, mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        foreach (self::normalizedOptions($field) as $option) {
            if ($option['value'] === $value || $option['label'] === $value) {
                return $option['value'];
            }
        }

        return null;
    }

    private static function resolveScaleValue(array $field, mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return in_array($value, self::scaleValues($field), true) ? $value : null;
    }

    private static function uniqueSlug(string $existing, string $label, string $fallback, array &$used): string
    {
        $candidate = Str::slug($existing !== '' ? $existing : $label, '_');
        $candidate = $candidate !== '' ? $candidate : $fallback;
        $base = $candidate;
        $suffix = 2;

        while (in_array($candidate, $used, true)) {
            $candidate = $base . '_' . $suffix;
            $suffix++;
        }

        $used[] = $candidate;

        return $candidate;
    }

    private static function percentage(int $count, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($count / $total) * 100, 1);
    }
}