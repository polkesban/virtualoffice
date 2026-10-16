<?php

namespace Tests\Feature;

use App\Models\NavMenu;
use App\Models\UnitPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class LakipAdministrasiUmumPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_lakip_2023_page_is_accessible(): void
    {
        $this->createAdministrasiUmumPage();

        $response = $this->get(route('unit.administrasi-umum.lakip.2023'));

        $response->assertOk();
        $response->assertSeeText('LAKIP 2023');
        $response->assertSeeText('Ringkasan Eksekutif');
        $response->assertSeeText('19 indikator utama');
        $response->assertSeeText('Dokumen HTML Ringan');
    }

    public function test_lakip_2025_page_is_accessible(): void
    {
        $this->createAdministrasiUmumPage();

        $response = $this->get(route('unit.administrasi-umum.lakip'));

        $response->assertOk();
        $response->assertSeeText('LAKIP 2025');
        $response->assertSeeText('Ringkasan Eksekutif');
        $response->assertSeeText('22 indikator utama');
        $response->assertSeeText('Dokumen HTML Ringan');
    }

    public function test_lakip_2024_page_is_accessible(): void
    {
        $this->createAdministrasiUmumPage();

        $response = $this->get(route('unit.administrasi-umum.lakip.2024'));

        $response->assertOk();
        $response->assertSeeText('LAKIP 2024');
        $response->assertSeeText('Ringkasan Eksekutif');
        $response->assertSeeText('17 indikator utama');
        $response->assertSeeText('Dokumen HTML Ringan');
    }

    public function test_lakip_2025_legacy_document_section_route_redirects_to_html_section(): void
    {
        $this->createAdministrasiUmumPage();

        $response = $this->get(route('unit.administrasi-umum.lakip.section', ['section' => 'bab-iii']));

        $response->assertRedirect('/unit/bidang-administrasi-umum/lakip#dokumen-bab-3');
    }

    public function test_lakip_2024_legacy_document_section_route_redirects_to_html_section(): void
    {
        $this->createAdministrasiUmumPage();

        $response = $this->get(route('unit.administrasi-umum.lakip.2024.section', ['section' => 'bab-iii']));

        $response->assertRedirect('/unit/bidang-administrasi-umum/lakip-2024#dokumen-bab-3');
    }

    public function test_lakip_2023_legacy_document_section_route_redirects_to_html_section(): void
    {
        $this->createAdministrasiUmumPage();

        $response = $this->get(route('unit.administrasi-umum.lakip.2023.section', ['section' => 'bab-iii']));

        $response->assertRedirect('/unit/bidang-administrasi-umum/lakip-2023#dokumen-bab-3');
    }

    public function test_lakip_submenus_are_generated_under_bidang_administrasi_umum(): void
    {
        $this->createAdministrasiUmumPage();

        $tree = NavMenu::navbarTree();
        $layanan = $tree->firstWhere('label', 'Layanan');

        $this->assertNotNull($layanan);

        $administrasiUmum = $layanan->childrenRecursive->firstWhere('label', 'Bagian Keuangan');

        $this->assertNotNull($administrasiUmum);

        $lakip2025 = $administrasiUmum->childrenRecursive->firstWhere('label', 'LAKIP 2025');
        $lakip2024 = $administrasiUmum->childrenRecursive->firstWhere('label', 'LAKIP 2024');
        $lakip2023 = $administrasiUmum->childrenRecursive->firstWhere('label', 'LAKIP 2023');

        $this->assertNotNull($lakip2025);
        $this->assertSame('/unit/bidang-administrasi-umum/lakip', $lakip2025->href);

        $this->assertNotNull($lakip2024);
        $this->assertSame('/unit/bidang-administrasi-umum/lakip-2024', $lakip2024->href);

        $this->assertNotNull($lakip2023);
        $this->assertSame('/unit/bidang-administrasi-umum/lakip-2023', $lakip2023->href);
    }

    private function createAdministrasiUmumPage(): UnitPage
    {
        return UnitPage::query()->firstOrCreate(
            ['role' => 'wadir_2'],
            [
                'slug' => 'bidang-administrasi-umum',
                'judul' => 'Bagian Keuangan',
                'sub_judul' => 'Halaman publik Wakil Direktur II bidang administrasi umum Poltekkes Bandung.',
                'warna_tema' => '#0f766e',
                'aktif' => true,
            ]
        );
    }
}