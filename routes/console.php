<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\AiApiKey;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    AiApiKey::where('provider', 'openrouter')
        ->where('is_exhausted', true)
        ->where('exhausted_at', '<=', now()->subMinutes(5))
        ->update([
            'is_exhausted' => false,
            'exhausted_at' => null,
            'fail_count' => 0
        ]);
})->everyFiveMinutes()->name('reset-openrouter-keys');
