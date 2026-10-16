<?php

use App\Models\Osdm\OsdmKebugaranSubmission;
use App\Models\Osdm\OsdmWfaSubmission;

echo "=== IMPORTING FIRESTORE KEBUGARAN DATA ===" . PHP_EOL;
$kbgFile = '/tmp/firestore_kebugaran.json';
if (file_exists($kbgFile)) {
    $items = json_decode(file_get_contents($kbgFile), true) ?: [];
    echo "Found " . count($items) . " records in JSON." . PHP_EOL;
    $importedKbg = 0;
    $skippedKbg = 0;

    foreach ($items as $item) {
        $cleanNip = preg_replace('/[\s.\-]/', '', $item['nip'] ?? '');
        $periode = trim($item['periode'] ?? '');

        // Check if exists by NIP & Periode or exact match
        $exists = OsdmKebugaranSubmission::whereRaw("REPLACE(REPLACE(REPLACE(nip, ' ', ''), '.', ''), '-', '') = ?", [$cleanNip])
            ->whereRaw("LOWER(periode) = ?", [strtolower($periode)])
            ->first();

        $createdAt = !empty($item['createdAt']) && is_string($item['createdAt']) 
            ? \Carbon\Carbon::parse($item['createdAt']) 
            : now();

        $data = [
            'tanggal_periksa' => !empty($item['tanggalPeriksa']) ? $item['tanggalPeriksa'] : now()->format('Y-m-d'),
            'periode' => $periode ?: 'Triwulan III',
            'nip' => $item['nip'] ?? '',
            'nama_pegawai' => $item['namaPegawai'] ?? '',
            'tanggal_lahir' => $item['tanggalLahir'] ?? null,
            'unit_kerja' => $item['unitKerja'] ?? '',
            'nik' => $item['nik'] ?? null,
            'tensi_sistolik' => (int)($item['tensiSistolik'] ?? 120),
            'tensi_diastolik' => (int)($item['tensiDiastolik'] ?? 80),
            'berat_badan' => (float)($item['beratBadan'] ?? 60),
            'tinggi_badan' => (float)($item['tinggiBadan'] ?? 160),
            'lingkar_pinggang' => (float)($item['lingkarPinggang'] ?? 75),
            'tipe_gula_darah' => $item['tipeGulaDarah'] ?? 'GDS',
            'gula_darah' => (int)($item['gulaDarah'] ?? 100),
            'kolesterol' => (int)($item['kolesterol'] ?? 180),
            'nomor_wa' => $item['nomorWa'] ?? null,
            'fasyankes' => $item['fasyankes'] ?? 'Klinik Pratama Poltekkes Kemenkes Bandung',
            'catatan' => $item['catatan'] ?? null,
        ];

        if (!$exists) {
            $record = new OsdmKebugaranSubmission($data);
            $record->created_at = $createdAt;
            $record->updated_at = $createdAt;
            $record->save();
            $importedKbg++;
        } else {
            $skippedKbg++;
        }
    }
    echo "Kebugaran imported: $importedKbg, skipped existing: $skippedKbg, total in DB: " . OsdmKebugaranSubmission::count() . PHP_EOL;
}

echo "=== IMPORTING FIRESTORE WFA DATA ===" . PHP_EOL;
$wfaFile = '/tmp/firestore_wfa.json';
if (file_exists($wfaFile)) {
    $wfaItems = json_decode(file_get_contents($wfaFile), true) ?: [];
    echo "Found " . count($wfaItems) . " WFA records in JSON." . PHP_EOL;
    $importedWfa = 0;
    $skippedWfa = 0;

    foreach ($wfaItems as $item) {
        $cleanNip = preg_replace('/[\s.\-]/', '', $item['nip'] ?? '');
        $tanggalWfa = $item['tanggalWfa'] ?? '';

        $exists = OsdmWfaSubmission::whereRaw("REPLACE(REPLACE(REPLACE(nip, ' ', ''), '.', ''), '-', '') = ?", [$cleanNip])
            ->where('tanggal_wfa', $tanggalWfa)
            ->first();

        $createdAt = !empty($item['createdAt']) && is_string($item['createdAt']) 
            ? \Carbon\Carbon::parse($item['createdAt']) 
            : now();

        $validatedAt = !empty($item['validatedAt']) && is_string($item['validatedAt'])
            ? \Carbon\Carbon::parse($item['validatedAt'])
            : null;

        $data = [
            'nip' => $item['nip'] ?? '',
            'employee_name' => $item['employeeName'] ?? '',
            'unit_kerja' => $item['unitKerja'] ?? '',
            'jabatan' => $item['jabatan'] ?? '',
            'nomor_wa' => $item['nomorWa'] ?? '',
            'tanggal_wfa' => $tanggalWfa ?: now()->format('Y-m-d'),
            'nama_kegiatan' => $item['namaKegiatan'] ?? '',
            'lokasi_kegiatan' => $item['lokasiKegiatan'] ?? '',
            'lokasi_lahan_bimbingan' => $item['lokasiLahanBimbingan'] ?? '',
            'status_wfa' => $item['statusWfa'] ?? 'WFA Datang',
            'link_surat_tugas' => $item['linkSuratTugas'] ?? '',
            'status' => $item['status'] ?? 'Menunggu Validasi',
            'catatan_pengelola' => $item['catatanPengelola'] ?? null,
            'validated_by' => $item['validatedBy'] ?? null,
            'validated_at' => $validatedAt,
        ];

        if (!$exists) {
            $sub = new OsdmWfaSubmission($data);
            $sub->created_at = $createdAt;
            $sub->updated_at = $createdAt;
            $sub->save();
            $importedWfa++;
        } else {
            // Update status & catatan if exists in case admin modified it in Firestore
            if (!empty($item['status']) && $item['status'] !== 'Menunggu Validasi') {
                $exists->status = $item['status'];
                $exists->catatan_pengelola = $item['catatanPengelola'] ?? $exists->catatan_pengelola;
                $exists->validated_by = $item['validatedBy'] ?? $exists->validated_by;
                $exists->validated_at = $validatedAt ?? $exists->validated_at;
                $exists->save();
            }
            $skippedWfa++;
        }
    }
    echo "WFA imported: $importedWfa, skipped/updated existing: $skippedWfa, total in DB: " . OsdmWfaSubmission::count() . PHP_EOL;
}
