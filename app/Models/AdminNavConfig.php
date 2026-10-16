<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AdminNavConfig extends Model
{
    protected $table = 'admin_nav_config';

    protected $fillable = ['menu_key', 'custom_label', 'custom_icon', 'urutan', 'globally_hidden'];

    protected $casts = [
        'globally_hidden' => 'boolean',
        'urutan'          => 'integer',
    ];

    /** Returns keyed array: menu_key => config row, cached */
    public static function getAllKeyed(): array
    {
        try {
            return cache()->remember('admin_nav_config', 600, function () {
                return self::query()->get()->keyBy('menu_key')->toArray();
            });
        } catch (\Throwable $exception) {
            Log::warning('AdminNavConfig cache fallback triggered.', [
                'message' => $exception->getMessage(),
            ]);

            return self::query()->get()->keyBy('menu_key')->toArray();
        }
    }

    public static function clearCache(): void
    {
        try {
            cache()->forget('admin_nav_config');
        } catch (\Throwable $exception) {
            Log::warning('AdminNavConfig cache clear failed.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
