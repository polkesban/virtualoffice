<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $menuKey = 'eviden-akreditasi';

    private array $roleKeys = [
        'wadir_1',
        'wadir_2',
        'wadir_3',
        'pusat_mutu',
        'kaprodi',
        'spi',
    ];

    public function up(): void
    {
        if (Schema::hasTable('admin_nav_config') && ! DB::table('admin_nav_config')->where('menu_key', $this->menuKey)->exists()) {
            DB::table('admin_nav_config')->insert([
                'menu_key' => $this->menuKey,
                'custom_label' => null,
                'custom_icon' => null,
                'urutan' => 145,
                'globally_hidden' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('roles')) {
            return;
        }

        foreach ($this->roleKeys as $roleKey) {
            $this->appendMenuToRole($roleKey, $this->menuKey);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admin_nav_config')) {
            DB::table('admin_nav_config')->where('menu_key', $this->menuKey)->delete();
        }

        if (! Schema::hasTable('roles')) {
            return;
        }

        foreach ($this->roleKeys as $roleKey) {
            $this->removeMenuFromRole($roleKey, $this->menuKey);
        }
    }

    private function appendMenuToRole(string $roleKey, string $menuKey): void
    {
        $role = DB::table('roles')->where('key', $roleKey)->first();

        if (! $role) {
            return;
        }

        $menus = json_decode((string) $role->menus, true);
        $menus = is_array($menus) ? $menus : [];

        if (in_array($menuKey, $menus, true)) {
            return;
        }

        $menus[] = $menuKey;

        DB::table('roles')
            ->where('id', $role->id)
            ->update([
                'menus' => json_encode(array_values(array_unique($menus))),
                'updated_at' => now(),
            ]);
    }

    private function removeMenuFromRole(string $roleKey, string $menuKey): void
    {
        $role = DB::table('roles')->where('key', $roleKey)->first();

        if (! $role) {
            return;
        }

        $menus = json_decode((string) $role->menus, true);
        $menus = is_array($menus) ? $menus : [];
        $menus = array_values(array_filter($menus, fn ($item) => $item !== $menuKey));

        DB::table('roles')
            ->where('id', $role->id)
            ->update([
                'menus' => json_encode($menus),
                'updated_at' => now(),
            ]);
    }
};