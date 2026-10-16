<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osdm_click_logs', function (Blueprint $table) {
            $table->id();
            $table->string('menu_id')->index();
            $table->string('menu_title');
            $table->string('category')->nullable();
            $table->string('device')->comment('Mobile | Desktop | Tablet');
            $table->string('browser');
            $table->string('referrer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osdm_click_logs');
    }
};
