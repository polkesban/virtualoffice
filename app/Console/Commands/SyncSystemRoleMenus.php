<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncSystemRoleMenus extends Command
{
    protected $signature = 'roles:sync-system-menus
                            {--dry-run : Tampilkan perubahan tanpa menulis ke database}
                            {--replace : Ganti menu role sistem persis seperti default kode, bukan digabung}';

    protected $description = 'Sinkronkan role sistem di tabel roles dengan label dan menu default terbaru dari kode aplikasi';

    public function handle(): int
    {
        $defaults = User::defaultRoleLabels();
        $defaultMenus = User::defaultRoleMenus();
        $dryRun = (bool) $this->option('dry-run');
        $replace = (bool) $this->option('replace');

        $rows = [];

        $runner = function () use ($defaults, $defaultMenus, $dryRun, $replace, &$rows): void {
            foreach ($defaults as $key => $label) {
                $role = Role::query()->firstOrNew(['key' => $key]);
                $beforeMenus = array_values(array_unique($role->menus ?? []));
                $targetMenus = array_values(array_unique($defaultMenus[$key] ?? []));

                $afterMenus = $replace
                    ? $targetMenus
                    : array_values(array_unique(array_merge($beforeMenus, $targetMenus)));

                $labelChanged = ! $role->exists || blank($role->label);
                $menusChanged = $beforeMenus !== $afterMenus;
                $systemChanged = ! $role->exists || ! $role->is_system;

                $action = ! $role->exists
                    ? 'created'
                    : (($labelChanged || $menusChanged || $systemChanged) ? 'updated' : 'unchanged');

                $rows[] = [
                    'key' => $key,
                    'action' => $action,
                    'before' => $beforeMenus,
                    'after' => $afterMenus,
                ];

                if ($dryRun || $action === 'unchanged') {
                    continue;
                }

                $role->label = $labelChanged ? $label : $role->label;
                $role->menus = $afterMenus;
                $role->is_system = true;
                $role->save();
            }
        };

        if ($dryRun) {
            $runner();
        } else {
            DB::transaction($runner);
        }

        User::refreshRoleConfig();

        $this->table(
            ['Role', 'Aksi', 'Menu Sebelum', 'Menu Sesudah'],
            array_map(static fn (array $row): array => [
                $row['key'],
                $row['action'],
                implode(', ', $row['before']),
                implode(', ', $row['after']),
            ], $rows)
        );

        $changedCount = collect($rows)->where('action', '!=', 'unchanged')->count();

        if ($dryRun) {
            $this->info('Dry-run selesai. ' . $changedCount . ' role sistem akan disinkronkan jika command dijalankan tanpa --dry-run.');

            return self::SUCCESS;
        }

        $this->info('Sinkronisasi role sistem selesai. ' . $changedCount . ' role diperbarui/dibuat.');

        return self::SUCCESS;
    }
}