<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osdm_portal_configs', function (Blueprint $table) {
            $table->id();
            $table->string('config_key')->unique()->comment('live, draft, security');
            $table->longText('menus')->nullable()->comment('JSON array of MenuItem');
            $table->longText('profile')->nullable()->comment('JSON MicrositeProfile object');
            $table->string('admin_pin')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osdm_portal_configs');
    }
};
