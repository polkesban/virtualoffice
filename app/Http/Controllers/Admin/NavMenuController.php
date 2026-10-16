<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavMenu;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class NavMenuController extends Controller implements HasMiddleware
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
        $items = NavMenu::with('children')
            ->whereNull('parent_id')
            ->orderBy('urutan')
            ->get();
        $parents = NavMenu::whereNull('parent_id')->orderBy('urutan')->get();
        return view('admin.nav-menus.index', compact('items', 'parents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label'     => 'required|string|max:100',
            'href'      => 'nullable|string|max:300',
            'icon'      => 'nullable|string|max:20',
            'parent_id' => 'nullable|exists:nav_menus,id',
            'target'    => 'required|in:_self,_blank',
            'aktif'     => 'boolean',
        ]);
        $data['aktif']  = $request->boolean('aktif', true);
        $data['urutan'] = NavMenu::where('parent_id', $data['parent_id'] ?? null)->max('urutan') + 1;

        NavMenu::create($data);
        NavMenu::clearCache();

        return back()->with('success', 'Menu berhasil ditambahkan.');
    }

    public function update(Request $request, NavMenu $navMenu)
    {
        $data = $request->validate([
            'label'     => 'required|string|max:100',
            'href'      => 'nullable|string|max:300',
            'icon'      => 'nullable|string|max:20',
            'parent_id' => 'nullable|exists:nav_menus,id',
            'target'    => 'required|in:_self,_blank',
            'aktif'     => 'boolean',
        ]);
        // Prevent item becoming its own parent
        if (isset($data['parent_id']) && (int)$data['parent_id'] === $navMenu->id) {
            return back()->withErrors(['parent_id' => 'Item tidak bisa menjadi parent-nya sendiri.']);
        }
        $data['aktif'] = $request->boolean('aktif');

        $navMenu->update($data);
        NavMenu::clearCache();

        return back()->with('success', 'Menu "' . $navMenu->label . '" berhasil diperbarui.');
    }

    public function destroy(NavMenu $navMenu)
    {
        // Promote children to top level before deleting parent
        if ($navMenu->children()->count()) {
            $navMenu->children()->update(['parent_id' => null]);
        }
        $navMenu->delete();
        NavMenu::clearCache();

        return back()->with('success', 'Menu dihapus.');
    }

    public function move(Request $request, NavMenu $navMenu)
    {
        $direction = $request->input('direction'); // 'up' | 'down'
        $sibling   = NavMenu::where('parent_id', $navMenu->parent_id)
            ->when($direction === 'up',
                fn($q) => $q->where('urutan', '<', $navMenu->urutan)->orderByDesc('urutan'),
                fn($q) => $q->where('urutan', '>', $navMenu->urutan)->orderBy('urutan')
            )->first();

        if ($sibling) {
            [$navMenu->urutan, $sibling->urutan] = [$sibling->urutan, $navMenu->urutan];
            $navMenu->save();
            $sibling->save();
            NavMenu::clearCache();
        }
        return back();
    }

    public function toggleActive(NavMenu $navMenu)
    {
        $navMenu->update(['aktif' => !$navMenu->aktif]);
        NavMenu::clearCache();
        return back();
    }
}
