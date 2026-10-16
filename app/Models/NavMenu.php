<?php

namespace App\Models;

use App\Support\PublicManagedNavigation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class NavMenu extends Model
{
    protected $table = 'nav_menus';

    protected $fillable = ['label', 'href', 'icon', 'parent_id', 'urutan', 'aktif', 'target'];

    protected $casts = ['aktif' => 'boolean', 'urutan' => 'integer'];

    // ── Relationships ────────────────────────────────────────────────────────

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('urutan');
    }

    public function childrenRecursive()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('aktif', true)
            ->orderBy('urutan')
            ->with('childrenRecursive');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isDropdown(): bool
    {
        return is_null($this->href) || $this->children()->exists();
    }

    /**
     * Load the public navbar tree directly from the database.
     *
     * Caching serialized Eloquent models in the file cache caused
     * intermittent __PHP_Incomplete_Class failures on the public site.
     */
    public static function navbarTree(): Collection
    {
        if (! Schema::hasTable('nav_menus')) {
            return new Collection();
        }

        $manualTree = self::with([
            'childrenRecursive',
        ])
            ->whereNull('parent_id')
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get();

        $manualTree = PublicManagedNavigation::enrichManualTree($manualTree);

        $generatedTree = PublicManagedNavigation::generatedTopLevelItems($manualTree);

        return new Collection(
            $manualTree
                ->concat($generatedTree)
                ->sortBy(fn (self $item) => sprintf('%05d-%s', (int) ($item->urutan ?? 999), mb_strtolower($item->label)))
                ->values()
                ->all()
        );
    }

    public static function clearCache(): void
    {
        cache()->forget('navbar_tree');
    }
}
