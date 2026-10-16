<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buku Portofolio OBE - {{ $mahasiswa->name }} ({{ $obe->nama_mk }})</title>
    <style>
        * { box-sizing: border-box; }
        @page {
            size: A4;
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #111827;
            background: #f3f4f6;
            margin: 0;
            padding: 0;
        }

        .no-print {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #0f172a;
            color: #ffffff;
            padding: 12px 24px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .no-print button {
            background: #0d9488;
            color: #ffffff;
            border: none;
            padding: 9px 20px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .no-print button:hover {
            background: #0f766e;
        }
        .no-print a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .sheet {
            max-width: 820px;
            margin: 20px auto;
            background: #ffffff;
            padding: 24px 30px 36px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            min-height: 275mm;
            position: relative;
        }

        /* Standardized Kop Surat Resmi Poltekkes Bandung Bahasa Indonesia */
        .letterhead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .letterhead-brand {
            flex: 0 0 190px;
        }
        .letterhead-brand img {
            display: block;
            width: 100%;
            height: auto;
        }
        .letterhead-copy {
            flex: 1;
            padding-top: 2px;
        }
        .letterhead-copy .line-ministry {
            color: #0d9488;
            font-size: 15px;
            font-weight: 800;
            line-height: 1.15;
            text-transform: uppercase;
        }
        .letterhead-copy .line-directorate {
            color: #1e293b;
            font-size: 13.5px;
            font-weight: 800;
            line-height: 1.15;
            text-transform: uppercase;
        }
        .letterhead-copy .line-campus {
            margin-top: 4px;
            color: #334155;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.2;
            text-transform: uppercase;
        }
        .letterhead-copy .line-contact {
            margin-top: 4px;
            color: #475569;
            font-size: 9.5px;
            line-height: 1.35;
        }
        .letterhead-copy .line-contact span {
            display: inline-block;
            min-width: 48px;
            font-weight: 700;
            color: #334155;
        }
        .separator {
            margin-top: 10px;
            margin-bottom: 20px;
            border-top: 3px solid #0f172a;
            border-bottom: 1px solid #0f172a;
            height: 4px;
        }

        /* Typography & Layout */
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .doc-title {
            font-size: 14pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 20px;
            margin-bottom: 25px;
            line-height: 1.4;
            color: #0f172a;
        }

        .section-header {
            font-size: 11pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
            margin-top: 25px;
            margin-bottom: 10px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
        }

        /* Tables */
        table.meta-table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
        }
        table.meta-table td {
            padding: 6px 8px;
            vertical-align: top;
            font-size: 10.5pt;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #0f172a;
            padding: 8px 10px;
            font-size: 10pt;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            text-align: center;
        }

        .cover-bottom {
            margin-top: 50px;
            text-align: center;
            font-size: 11pt;
            font-weight: 800;
            line-height: 1.5;
            color: #0f172a;
        }

        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff; }
            .sheet {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                page-break-after: always !important;
                break-after: page !important;
                min-height: auto;
            }
            .sheet:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
        }
    </style>
</head>
<body>

    <!-- Header Control Bar -->
    <div class="no-print">
        <a href="{{ route('mahasiswa.obe.edit', $obe->id) }}">← Kembali ke Editor Form</a>
        <div>
            <span style="margin-right: 15px; font-size: 13px;">Preview Dokumen Resmi Buku Portofolio OBE</span>
            <button type="button" onclick="window.print()">🖨️ Cetak Dokumen / Simpan PDF</button>
        </div>
    </div>

    @php
        $namaJurusan = 'KESEHATAN';
        if ($mahasiswa->programStudi) {
            if (is_object($mahasiswa->programStudi->jurusan) && isset($mahasiswa->programStudi->jurusan->nama)) {
                $namaJurusan = $mahasiswa->programStudi->jurusan->nama;
            } elseif (is_string($mahasiswa->programStudi->jurusan)) {
                $namaJurusan = $mahasiswa->programStudi->jurusan;
            } else {
                $namaJurusan = 'JURUSAN ' . ($mahasiswa->programStudi->nama ?? 'KEPERAWATAN');
            }
        }
        $namaJurusanClean = preg_replace('/^JURUSAN\s+/i', '', trim($namaJurusan));
        $namaProdiClean = $mahasiswa->programStudi?->nama ?? $mahasiswa->programStudi?->nama_prodi ?? 'PROGRAM STUDI D3 / D4';
    @endphp

    <!-- HALAMAN 1: COVER -->
    <section class="sheet">
        <!-- Kop Surat Bahasa Indonesia Resmi -->
        <div class="letterhead">
            <div class="letterhead-brand">
                <img src="{{ asset('images/logo-polkesban.png') }}" alt="Kemenkes Poltekkes Bandung" onerror="this.src='https://ui-avatars.com/api/?name=Poltekkes+Bandung&size=160&background=0d9488&color=fff'">
            </div>
            <div class="letterhead-copy">
                <div class="line-ministry">Kementerian Kesehatan Republik Indonesia</div>
                <div class="line-directorate">Direktorat Jenderal Tenaga Kesehatan</div>
                <div class="line-campus">Politeknik Kesehatan Kemenkes Bandung</div>
                <div class="line-contact"><span>Alamat</span>: Jalan Pajajaran No.56, Pasir Kaliki, Cicendo, Bandung, Jawa Barat 40171</div>
                <div class="line-contact"><span>Telepon</span>: (022) 4231627</div>
                <div class="line-contact"><span>Situs</span>: https://poltekkesbandung.ac.id</div>
            </div>
        </div>
        <div class="separator"></div>

        <!-- Cover Body -->
        <div class="text-center doc-title" style="margin-top: 35px; margin-bottom: 30px;">
            BUKU PORTOFOLIO PEMBELAJARAN MAHASISWA<br>
            BERBASIS OUTCOME BASED EDUCATION (OBE)
        </div>

        <table class="meta-table">
            <tr>
                <td style="width: 35%;">Nama Mahasiswa</td>
                <td style="width: 4%;">:</td>
                <td style="width: 61%;" class="font-bold">{{ $mahasiswa->name }}</td>
            </tr>
            <tr>
                <td>NIM (Nomor Induk Mahasiswa)</td>
                <td>:</td>
                <td class="font-bold">{{ $mahasiswa->nim }}</td>
            </tr>
            <tr>
                <td>Kelas</td>
                <td>:</td>
                <td>{{ $obe->kelas ?? 'Reguler A' }}</td>
            </tr>
            <tr>
                <td>Program Studi</td>
                <td>:</td>
                <td>{{ $namaProdiClean }}</td>
            </tr>
            <tr>
                <td>Semester</td>
                <td>:</td>
                <td>Semester {{ $obe->semester }}</td>
            </tr>
            <tr>
                <td>Dosen Pembimbing Akademik</td>
                <td>:</td>
                <td>{{ $obe->dosen_pa ?? '-' }}</td>
            </tr>
            <tr>
                <td>Lahan Praktik</td>
                <td>:</td>
                <td>{{ $obe->lahan_praktik ?? '-' }}</td>
            </tr>
            <tr>
                <td>Pembimbing Lahan / CI</td>
                <td>:</td>
                <td>{{ $obe->pembimbing_lahan ?? '-' }}</td>
            </tr>
        </table>

        <div class="cover-bottom">
            KEMENTERIAN KESEHATAN REPUBLIK INDONESIA<br>
            POLITEKNIK KESEHATAN KEMENKES BANDUNG<br>
            JURUSAN {{ strtoupper($namaJurusanClean) }}<br>
            PROGRAM STUDI {{ strtoupper($namaProdiClean) }}<br>
            TAHUN AKADEMIK {{ $obe->tahun_akademik }}
        </div>
    </section>

    <!-- HALAMAN 2: LEMBAR PENGESAHAN -->
    <section class="sheet">
        <div class="letterhead">
            <div class="letterhead-brand">
                <img src="{{ asset('images/logo-polkesban.png') }}" alt="Kemenkes Poltekkes Bandung" onerror="this.src='https://ui-avatars.com/api/?name=Poltekkes+Bandung&size=160&background=0d9488&color=fff'">
            </div>
            <div class="letterhead-copy">
                <div class="line-ministry">Kementerian Kesehatan Republik Indonesia</div>
                <div class="line-directorate">Direktorat Jenderal Tenaga Kesehatan</div>
                <div class="line-campus">Politeknik Kesehatan Kemenkes Bandung</div>
                <div class="line-contact"><span>Alamat</span>: Jalan Pajajaran No.56, Pasir Kaliki, Cicendo, Bandung, Jawa Barat 40171</div>
                <div class="line-contact"><span>Telepon</span>: (022) 4231627</div>
                <div class="line-contact"><span>Situs</span>: https://poltekkesbandung.ac.id</div>
            </div>
        </div>
        <div class="separator"></div>

        <div class="text-center doc-title" style="margin-top: 30px; margin-bottom: 40px;">
            LEMBAR PENGESAHAN<br>
            BUKU PORTOFOLIO PEMBELAJARAN MAHASISWA
        </div>

        <p class="text-justify" style="line-height: 1.8; font-size: 11pt; margin-bottom: 80px;">
            Disusun sebagai dokumen penilaian pembelajaran berbasis <em>Outcome Based Education (OBE)</em> pada Jurusan {{ $namaJurusanClean }} Poltekkes Kemenkes Bandung.
        </p>

        <table style="width: 100%; font-size: 11pt;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    Mengetahui,<br>
                    <strong>Ketua Jurusan,</strong>
                    <div style="height: 75px;"></div>
                    <strong><u>{{ $obe->ketua_jurusan ?? '........................................' }}</u></strong><br>
                    NIP. {{ $obe->nip_ketua_jurusan ?? '....................' }}
                </td>
                <td style="width: 50%; vertical-align: top;">
                    Disahkan oleh,<br>
                    <strong>Ketua Program Studi,</strong>
                    <div style="height: 75px;"></div>
                    <strong><u>{{ $obe->ketua_prodi ?? '........................................' }}</u></strong><br>
                    NIP. {{ $obe->nip_ketua_prodi ?? '....................' }}
                </td>
            </tr>
        </table>
    </section>

    <!-- HALAMAN 3: KATA PENGANTAR -->
    <section class="sheet">
        <div class="letterhead">
            <div class="letterhead-brand">
                <img src="{{ asset('images/logo-polkesban.png') }}" alt="Kemenkes Poltekkes Bandung" onerror="this.src='https://ui-avatars.com/api/?name=Poltekkes+Bandung&size=160&background=0d9488&color=fff'">
            </div>
            <div class="letterhead-copy">
                <div class="line-ministry">Kementerian Kesehatan Republik Indonesia</div>
                <div class="line-directorate">Direktorat Jenderal Tenaga Kesehatan</div>
                <div class="line-campus">Politeknik Kesehatan Kemenkes Bandung</div>
                <div class="line-contact"><span>Alamat</span>: Jalan Pajajaran No.56, Pasir Kaliki, Cicendo, Bandung, Jawa Barat 40171</div>
                <div class="line-contact"><span>Telepon</span>: (022) 4231627</div>
                <div class="line-contact"><span>Situs</span>: https://poltekkesbandung.ac.id</div>
            </div>
        </div>
        <div class="separator"></div>

        <div class="text-center doc-title" style="margin-top: 25px; margin-bottom: 30px;">
            KATA PENGANTAR
        </div>

        <div class="text-justify" style="line-height: 1.8; font-size: 11pt; text-indent: 35px; space-y-4">
            <p>Puji syukur ke hadirat Tuhan Yang Maha Esa atas tersusunnya Buku Portofolio Pembelajaran Mahasiswa Program Studi {{ $namaProdiClean }} Politeknik Kesehatan Bandung Kementerian Kesehatan R.I.</p>
            <p>Portofolio ini disusun sebagai instrumen penilaian autentik yang mendokumentasikan pencapaian kompetensi mahasiswa sesuai pendekatan <em>Outcome Based Education (OBE)</em>. Portofolio menjadi bukti ketercapaian Capaian Pembelajaran Lulusan (CPL), Capaian Pembelajaran Mata Kuliah (CPMK), dan Sub-CPMK melalui berbagai aktivitas pembelajaran teori dan praktik.</p>
            <p>Portofolio ini diharapkan dapat menjadi sarana refleksi mahasiswa, media umpan balik dosen dan pembimbing, serta dokumen pendukung Sistem Penjaminan Mutu Internal (SPMI).</p>
            <p>Semoga buku ini bermanfaat bagi seluruh sivitas akademika.</p>
        </div>

        <div style="margin-top: 60px; float: right; text-align: left; width: 260px; font-size: 11pt;">
            Bandung, {{ date('d F Y') }}<br>
            <strong>Ketua Program Studi</strong>
            <div style="height: 65px;"></div>
            <strong><u>{{ $obe->ketua_prodi ?? '........................................' }}</u></strong><br>
            NIP. {{ $obe->nip_ketua_prodi ?? '....................' }}
        </div>
        <div style="clear: both;"></div>
    </section>

    <!-- HALAMAN 4: CPL & PROFIL MATA KULIAH -->
    <section class="sheet">
        <div class="letterhead">
            <div class="letterhead-brand">
                <img src="{{ asset('images/logo-polkesban.png') }}" alt="Kemenkes Poltekkes Bandung" onerror="this.src='https://ui-avatars.com/api/?name=Poltekkes+Bandung&size=160&background=0d9488&color=fff'">
            </div>
            <div class="letterhead-copy">
                <div class="line-ministry">Kementerian Kesehatan Republik Indonesia</div>
                <div class="line-directorate">Direktorat Jenderal Tenaga Kesehatan</div>
                <div class="line-campus">Politeknik Kesehatan Kemenkes Bandung</div>
                <div class="line-contact"><span>Alamat</span>: Jalan Pajajaran No.56, Pasir Kaliki, Cicendo, Bandung, Jawa Barat 40171</div>
                <div class="line-contact"><span>Telepon</span>: (022) 4231627</div>
                <div class="line-contact"><span>Situs</span>: https://poltekkesbandung.ac.id</div>
            </div>
        </div>
        <div class="separator"></div>

        <div class="section-header">1. Capaian Pembelajaran Lulusan (CPL)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Kode</th>
                    <th style="width: 85%;">Deskripsi Capaian Pembelajaran Lulusan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($obe->cpl_list ?? [] as $cpl)
                    <tr>
                        <td class="font-bold text-center">{{ $cpl['kode'] ?? '' }}</td>
                        <td>{{ $cpl['deskripsi'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center italic">Belum ada CPL diisi</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-header" style="margin-top: 30px;">2. Profil Mata Kuliah</div>
        <table class="data-table">
            <tr>
                <td style="width: 25%; font-weight: bold; background: #f1f5f9;">Kode Mata Kuliah</td>
                <td style="width: 25%;">{{ $obe->kode_mk }}</td>
                <td style="width: 25%; font-weight: bold; background: #f1f5f9;">Nama Mata Kuliah</td>
                <td style="width: 25%;" class="font-bold">{{ $obe->nama_mk }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold; background: #f1f5f9;">Bobot (SKS)</td>
                <td>{{ $obe->sks }} SKS</td>
                <td style="font-weight: bold; background: #f1f5f9;">Semester</td>
                <td>Semester {{ $obe->semester }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold; background: #f1f5f9;">Capaian Pembelajaran</td>
                <td colspan="3">
                    <strong>CPL Prodi yang dibebankan pada MK:</strong><br>
                    @foreach($obe->cpl_list ?? [] as $cpl)
                        • {{ $cpl['kode'] ?? '' }} - {{ $cpl['deskripsi'] ?? '' }}<br>
                    @endforeach
                    <br>
                    <strong>Capaian Pembelajaran Mata Kuliah (CPMK):</strong><br>
                    @foreach($obe->cpmk_list ?? [] as $cpmk)
                        • {{ $cpmk['kode'] ?? '' }} ({{ $cpmk['bobot'] ?? '' }}%): {{ $cpmk['deskripsi'] ?? '' }}<br>
                    @endforeach
                    <br>
                    <strong>Sub Capaian Pembelajaran Mata Kuliah (Sub-CPMK):</strong><br>
                    @foreach($obe->sub_cpmk_list ?? [] as $sub)
                        • {{ $sub }}<br>
                    @endforeach
                </td>
            </tr>
        </table>
    </section>

    <!-- HALAMAN 5: EVIDENCE, NILAI, UMPAN BALIK -->
    <section class="sheet">
        <div class="letterhead">
            <div class="letterhead-brand">
                <img src="{{ asset('images/logo-polkesban.png') }}" alt="Kemenkes Poltekkes Bandung" onerror="this.src='https://ui-avatars.com/api/?name=Poltekkes+Bandung&size=160&background=0d9488&color=fff'">
            </div>
            <div class="letterhead-copy">
                <div class="line-ministry">Kementerian Kesehatan Republik Indonesia</div>
                <div class="line-directorate">Direktorat Jenderal Tenaga Kesehatan</div>
                <div class="line-campus">Politeknik Kesehatan Kemenkes Bandung</div>
                <div class="line-contact"><span>Alamat</span>: Jalan Pajajaran No.56, Pasir Kaliki, Cicendo, Bandung, Jawa Barat 40171</div>
                <div class="line-contact"><span>Telepon</span>: (022) 4231627</div>
                <div class="line-contact"><span>Situs</span>: https://poltekkesbandung.ac.id</div>
            </div>
        </div>
        <div class="separator"></div>

        <div class="section-header">3. Daftar Evidence (Bukti Pembelajaran)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%;">No</th>
                    <th style="width: 70%;">Dokumen Evidence</th>
                    <th style="width: 20%;">Ada / Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($obe->evidences as $index => $ev)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}.</td>
                        <td>{{ $ev->nama_dokumen }}</td>
                        <td class="text-center font-bold" style="color: #0d9488;">
                            {{ $ev->is_ada ? '✓ Ada' : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center italic">Belum ada daftar evidence</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-header" style="margin-top: 30px;">4. Nilai Mata Kuliah</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%;">No</th>
                    <th style="width: 50%;">Mata Kuliah</th>
                    <th style="width: 15%;">Nilai Angka</th>
                    <th style="width: 12.5%;">Mutu</th>
                    <th style="width: 12.5%;">Lambang</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1.</td>
                    <td><strong>{{ $obe->nama_mk }}</strong> ({{ $obe->kode_mk }})</td>
                    <td class="text-center">{{ $obe->nilai_angka ? number_format($obe->nilai_angka, 2) : '-' }}</td>
                    <td class="text-center">{{ $obe->nilai_mutu ? number_format($obe->nilai_mutu, 2) : '-' }}</td>
                    <td class="text-center font-bold" style="font-size: 11pt;">{{ $obe->nilai_lambang ?? '-' }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-header" style="margin-top: 30px;">5. Umpan Balik Pembimbing</div>
        <table class="data-table">
            <tr>
                <td style="width: 35%; font-weight: bold; background: #f1f5f9;">Kelebihan Mahasiswa</td>
                <td style="width: 65%;">{{ $obe->kelebihan_mahasiswa ?? '-' }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold; background: #f1f5f9;">Aspek yang Perlu Ditingkatkan</td>
                <td>{{ $obe->aspek_perlu_ditingkatkan ?? '-' }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold; background: #f1f5f9;">Rekomendasi Pembimbing</td>
                <td>{{ $obe->rekomendasi_pembimbing ?? '-' }}</td>
            </tr>
        </table>
    </section>

</body>
</html>
