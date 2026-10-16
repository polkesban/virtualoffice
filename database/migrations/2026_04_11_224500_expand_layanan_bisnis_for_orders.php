<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layanan_bisnis', function (Blueprint $table) {
            if (! Schema::hasColumn('layanan_bisnis', 'opsi_varian')) {
                $table->json('opsi_varian')->nullable()->after('satuan_harga');
            }

            if (! Schema::hasColumn('layanan_bisnis', 'qris_link')) {
                $table->string('qris_link', 500)->nullable()->after('link_pesan');
            }

            if (! Schema::hasColumn('layanan_bisnis', 'qris_label')) {
                $table->string('qris_label', 120)->nullable()->after('qris_link');
            }
        });

        Schema::create('layanan_bisnis_orders', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pesanan', 40)->nullable()->unique();
            $table->string('nama_pemesan', 150);
            $table->string('nomor_wa', 30);
            $table->string('email', 150)->nullable();
            $table->text('alamat')->nullable();
            $table->text('catatan_pemesan')->nullable();
            $table->string('kategori_referensi', 50)->nullable();
            $table->string('status_pesanan', 50)->default('baru');
            $table->string('status_pembayaran', 20)->default('belum_lunas');
            $table->decimal('total_bayar', 14, 2)->default(0);
            $table->string('qris_link', 500)->nullable();
            $table->string('qris_label', 120)->nullable();
            $table->string('bukti_pembayaran', 500)->nullable();
            $table->timestamp('lunas_pada')->nullable();
            $table->timestamp('status_diperbarui_pada')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_admin')->nullable();
            $table->timestamps();

            $table->index(['status_pesanan', 'status_pembayaran']);
            $table->index(['kategori_referensi', 'created_at']);
        });

        Schema::create('layanan_bisnis_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layanan_bisnis_order_id')->constrained('layanan_bisnis_orders')->cascadeOnDelete();
            $table->foreignId('layanan_bisnis_id')->constrained('layanan_bisnis')->restrictOnDelete();
            $table->string('nama_item', 255);
            $table->string('kategori', 50)->nullable();
            $table->string('jenis_tipe_ukuran', 150)->nullable();
            $table->decimal('harga_satuan', 14, 2)->default(0);
            $table->unsignedInteger('jumlah')->default(1);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['layanan_bisnis_id', 'kategori']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layanan_bisnis_order_items');
        Schema::dropIfExists('layanan_bisnis_orders');

        Schema::table('layanan_bisnis', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('layanan_bisnis', 'opsi_varian')) {
                $dropColumns[] = 'opsi_varian';
            }

            if (Schema::hasColumn('layanan_bisnis', 'qris_link')) {
                $dropColumns[] = 'qris_link';
            }

            if (Schema::hasColumn('layanan_bisnis', 'qris_label')) {
                $dropColumns[] = 'qris_label';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};