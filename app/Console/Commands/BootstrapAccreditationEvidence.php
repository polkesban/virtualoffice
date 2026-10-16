<?php

namespace App\Console\Commands;

use App\Models\AccreditationEvidence;
use App\Models\AccreditationFolder;
use App\Models\DokumenMutu;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BootstrapAccreditationEvidence extends Command
{
    protected $signature = 'akreditasi:bootstrap-evidence
                            {--dry-run : Tampilkan rencana bootstrap tanpa menulis ke database}
                            {--include-inactive : Ikutkan dokumen mutu yang tidak aktif}
                            {--folder-code=7 : Kode folder tujuan default untuk import dokumen mutu}
                            {--status=draft : Status awal eviden (draft, ready, verified, archived)}';

    protected $description = 'Sinkronkan folder default dan buat eviden awal dari Dokumen Mutu untuk modul akreditasi';

    private const DEFAULT_FOLDERS = [
        [
            'code' => '1',
            'title' => 'Visi, Misi, Tujuan, dan Strategi',
            'description' => 'Eviden VMTS, renstra, renop, sosialisasi, dan keterlibatan pemangku kepentingan.',
            'sort_order' => 10,
        ],
        [
            'code' => '2',
            'title' => 'Kurikulum',
            'description' => 'CPL, struktur kurikulum, evaluasi kurikulum, dan bukti keterlibatan stakeholder.',
            'sort_order' => 20,
        ],
        [
            'code' => '3',
            'title' => 'Penilaian',
            'description' => 'Kebijakan penilaian, blueprint asesmen, rubrik, moderasi, dan evaluasi sistem penilaian.',
            'sort_order' => 30,
        ],
        [
            'code' => '4',
            'title' => 'Mahasiswa',
            'description' => 'Seleksi mahasiswa baru, dukungan mahasiswa, survei kepuasan, dan keselamatan mahasiswa.',
            'sort_order' => 40,
        ],
        [
            'code' => '5',
            'title' => 'SDM, Penelitian, dan Pengabdian',
            'description' => 'Profil dosen, tendik, pengembangan profesional, penelitian, dan pengabdian kepada masyarakat.',
            'sort_order' => 50,
        ],
        [
            'code' => '6',
            'title' => 'Sarana, Prasarana, dan Keuangan',
            'description' => 'Fasilitas pembelajaran, wahana praktik, sumber informasi, dan ringkasan kecukupan pendanaan.',
            'sort_order' => 60,
        ],
        [
            'code' => '7',
            'title' => 'Penjaminan Mutu',
            'description' => 'SPMI, AMI, tindak lanjut, monitoring, dan pengendalian mutu.',
            'sort_order' => 70,
        ],
        [
            'code' => '8',
            'title' => 'Tata Kelola dan Administrasi',
            'description' => 'Struktur organisasi, SOP, SK, notulen, administrasi akademik, dan kepatuhan tata kelola.',
            'sort_order' => 80,
        ],
    ];

    public function handle(): int
    {
        if (! Schema::hasTable('accreditation_folders') || ! Schema::hasTable('accreditation_evidences')) {
            $this->error('Tabel modul eviden akreditasi belum tersedia. Jalankan migrasi modul terlebih dahulu pada waktu yang aman.');

            return self::FAILURE;
        }

        $status = (string) $this->option('status');
        $statusOptions = AccreditationEvidence::verificationStatusOptions();

        if (! array_key_exists($status, $statusOptions)) {
            $this->error('Status tidak valid. Gunakan salah satu dari: ' . implode(', ', array_keys($statusOptions)));

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $folderCode = trim((string) $this->option('folder-code'));

        $this->info($dryRun
            ? 'Menjalankan dry-run bootstrap eviden akreditasi. Tidak ada data yang akan diubah.'
            : 'Menjalankan bootstrap eviden akreditasi. Folder default akan disinkronkan dan eviden awal akan dibuat dari Dokumen Mutu.');

        [$createdFolders, $updatedFolders] = $this->syncDefaultFolders($dryRun);

        $folder = AccreditationFolder::query()->where('code', $folderCode)->first();

        if (! $folder) {
            $this->error("Folder dengan kode {$folderCode} tidak ditemukan. Pilih kode folder yang tersedia.");
            $this->renderFolderTable();

            return self::FAILURE;
        }

        if (! Schema::hasTable('dokumen_mutu')) {
            $this->warn('Tabel dokumen_mutu tidak tersedia. Sinkronisasi folder selesai tanpa import eviden awal.');
            $this->renderFolderSummary($createdFolders, $updatedFolders);

            return self::SUCCESS;
        }

        $documents = DokumenMutu::query()
            ->when(! $this->option('include-inactive'), fn ($query) => $query->where('aktif', true))
            ->orderByDesc('tanggal_terbit')
            ->orderBy('judul')
            ->get();

        if ($documents->isEmpty()) {
            $this->warn('Tidak ada Dokumen Mutu yang cocok untuk di-bootstrap.');
            $this->renderFolderSummary($createdFolders, $updatedFolders);

            return self::SUCCESS;
        }

        $stats = [
            'parsed' => $documents->count(),
            'created' => 0,
            'skipped_existing' => 0,
            'skipped_no_source' => 0,
        ];

        $previewRows = [];

        $runner = function () use ($documents, $folder, $status, $dryRun, &$stats, &$previewRows): void {
            $bar = $this->output->createProgressBar($documents->count());
            $bar->start();

            foreach ($documents as $document) {
                if (AccreditationEvidence::query()->where('dokumen_mutu_id', $document->id)->exists()) {
                    $stats['skipped_existing']++;
                    $bar->advance();
                    continue;
                }

                if (! filled($document->file_url)) {
                    $stats['skipped_no_source']++;
                    $previewRows[] = [
                        $document->judul,
                        $document->jenis_label,
                        'Dilewati',
                        'Dokumen Mutu belum memiliki URL',
                    ];
                    $bar->advance();
                    continue;
                }

                $payload = [
                    'folder_id' => $folder->id,
                    'dokumen_mutu_id' => $document->id,
                    'title' => $document->judul,
                    'verification_status' => $status,
                    'summary' => $this->buildSummary($document),
                    'notes' => $this->buildNotes($document),
                    'issued_at' => $document->tanggal_terbit,
                    'expires_at' => $document->berlaku_sampai,
                    'review_due_at' => $document->berlaku_sampai,
                    'created_by' => null,
                    'updated_by' => null,
                ];

                $previewRows[] = [
                    $document->judul,
                    $document->jenis_label,
                    $dryRun ? 'Preview' : 'Dibuat',
                    'Folder ' . $folder->code . ' · Status ' . $status,
                ];

                if (! $dryRun) {
                    AccreditationEvidence::query()->create($payload);
                }

                $stats['created']++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
        };

        if ($dryRun) {
            $runner();
        } else {
            DB::transaction($runner);
        }

        $this->renderFolderSummary($createdFolders, $updatedFolders);

        if ($previewRows !== []) {
            $this->table(['Judul', 'Jenis', 'Aksi', 'Catatan'], array_slice($previewRows, 0, 20));

            if (count($previewRows) > 20) {
                $this->line('... ' . (count($previewRows) - 20) . ' baris lainnya tidak ditampilkan.');
            }
        }

        $this->table(['Ringkasan', 'Jumlah'], [
            ['Dokumen diproses', (string) $stats['parsed']],
            ['Eviden dibuat', (string) $stats['created']],
            ['Dilewati karena sudah tertaut', (string) $stats['skipped_existing']],
            ['Dilewati karena belum ada sumber URL', (string) $stats['skipped_no_source']],
        ]);

        if ($dryRun) {
            $this->info('Dry-run selesai. Jalankan ulang tanpa --dry-run saat jendela aktivasi off-peak sudah disetujui.');
        } else {
            $this->info('Bootstrap eviden akreditasi selesai.');
        }

        return self::SUCCESS;
    }

    private function syncDefaultFolders(bool $dryRun): array
    {
        $created = 0;
        $updated = 0;

        foreach (self::DEFAULT_FOLDERS as $definition) {
            $existing = AccreditationFolder::query()->where('code', $definition['code'])->first();

            if (! $existing) {
                if (! $dryRun) {
                    AccreditationFolder::query()->create([
                        'code' => $definition['code'],
                        'title' => $definition['title'],
                        'description' => $definition['description'],
                        'sort_order' => $definition['sort_order'],
                        'is_default' => true,
                        'is_active' => true,
                    ]);
                }

                $created++;
                continue;
            }

            $attributes = [
                'title' => $definition['title'],
                'description' => $definition['description'],
                'sort_order' => $definition['sort_order'],
                'is_default' => true,
                'is_active' => true,
            ];

            $dirty = false;
            foreach ($attributes as $key => $value) {
                if ($existing->{$key} !== $value) {
                    $dirty = true;
                    break;
                }
            }

            if ($dirty) {
                if (! $dryRun) {
                    $existing->forceFill($attributes)->save();
                }

                $updated++;
            }
        }

        return [$created, $updated];
    }

    private function buildSummary(DokumenMutu $document): ?string
    {
        $summaryParts = array_filter([
            $document->jenis_label,
            $document->nomor_dok ? 'Nomor ' . $document->nomor_dok : null,
            $document->versi ? 'Versi ' . $document->versi : null,
            filled($document->keterangan) ? Str::limit(trim(strip_tags((string) $document->keterangan)), 220) : null,
        ]);

        $summary = implode(' · ', $summaryParts);

        return $summary !== '' ? $summary : null;
    }

    private function buildNotes(DokumenMutu $document): string
    {
        $notes = [
            'Bootstrap otomatis dari modul Dokumen Mutu.',
            'Jenis sumber: tautan Dokumen Mutu.',
        ];

        if ($document->tanggal_terbit) {
            $notes[] = 'Tanggal dokumen: ' . $document->tanggal_terbit->format('d/m/Y') . '.';
        }

        if ($document->berlaku_sampai) {
            $notes[] = 'Masa berlaku sampai: ' . $document->berlaku_sampai->format('d/m/Y') . '.';
        }

        return implode(' ', $notes);
    }

    private function renderFolderSummary(int $created, int $updated): void
    {
        $this->table(['Sinkronisasi Folder', 'Jumlah'], [
            ['Folder baru', (string) $created],
            ['Folder diperbarui', (string) $updated],
        ]);
    }

    private function renderFolderTable(): void
    {
        $rows = AccreditationFolder::query()
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(fn (AccreditationFolder $folder) => [
                $folder->code,
                $folder->title,
            ])
            ->all();

        $this->table(['Kode', 'Folder'], $rows);
    }
}