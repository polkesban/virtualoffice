<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNavConfig;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AdminNavConfigController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(function ($request, $next) {
            abort_unless(auth()->user()?->isSuperAdmin(), 403);

            return $next($request);
        })];
    }

    public function index()
    {
        $items = AdminNavConfig::orderBy('urutan')->get();
        return view('admin.nav-admin.index', compact('items'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'items'                    => 'required|array',
            'items.*.id'               => 'required|exists:admin_nav_config,id',
            'items.*.custom_label'     => 'nullable|string|max:100',
            'items.*.custom_icon'      => 'nullable|string|max:20',
            'items.*.urutan'           => 'required|integer|min:0',
            'items.*.globally_hidden'  => 'boolean',
        ]);

        foreach ($data['items'] as $row) {
            AdminNavConfig::where('id', $row['id'])->update([
                'custom_label'    => $row['custom_label'] ?: null,
                'custom_icon'     => $row['custom_icon'] ?: null,
                'urutan'          => $row['urutan'],
                'globally_hidden' => !empty($row['globally_hidden']),
            ]);
        }

        AdminNavConfig::clearCache();
        return back()->with('success', 'Konfigurasi sidebar admin berhasil disimpan.');
    }
}
