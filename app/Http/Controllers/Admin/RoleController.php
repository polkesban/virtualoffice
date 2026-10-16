<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\UnitPage;
use App\Models\User;
use App\Support\AdminMenuCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $this->ensureSuperAdmin();

        $roles = Role::query()
            ->withCount(['users', 'unitPages'])
            ->orderByDesc('is_system')
            ->orderBy('label')
            ->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $this->ensureSuperAdmin();

        return view('admin.roles.form', [
            'role' => new Role(['menus' => []]),
            'allMenus' => AdminMenuCatalog::grouped(),
            'menuKeys' => AdminMenuCatalog::keys(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'key' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/', 'unique:roles,key'],
            'label' => 'required|string|max:180',
            'menus' => 'nullable|array',
            'menus.*' => ['string', Rule::in(AdminMenuCatalog::keys())],
        ]);

        $data['menus'] = array_values(array_unique($data['menus'] ?? []));
        $data['is_system'] = false;

        Role::create($data);
        User::refreshRoleConfig();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role berhasil ditambahkan.');
    }

    public function edit(Role $role): View
    {
        $this->ensureSuperAdmin();

        return view('admin.roles.form', [
            'role' => $role,
            'allMenus' => AdminMenuCatalog::grouped(),
            'menuKeys' => AdminMenuCatalog::keys(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $originalKey = $role->key;

        $rules = [
            'label' => 'required|string|max:180',
            'menus' => 'nullable|array',
            'menus.*' => ['string', Rule::in(AdminMenuCatalog::keys())],
        ];

        if (! $role->is_system) {
            $rules['key'] = ['required', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/', Rule::unique('roles', 'key')->ignore($role->id)];
        }

        $data = $request->validate($rules);
        $data['menus'] = array_values(array_unique($data['menus'] ?? []));

        if ($role->key === User::ROLE_SUPER_ADMIN) {
            $data['menus'] = ['*'];
        }

        $role->update($data);

        if (! $role->is_system && isset($data['key']) && $data['key'] !== $originalKey) {
            User::query()->where('role', $originalKey)->update(['role' => $data['key']]);
            UnitPage::query()->where('role', $originalKey)->update(['role' => $data['key']]);
        }

        User::refreshRoleConfig();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->ensureSuperAdmin();

        if ($role->is_system) {
            return back()->with('error', 'Role sistem tidak dapat dihapus.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Role ini masih dipakai oleh pengguna. Pindahkan pengguna ke role lain terlebih dahulu.');
        }

        if ($role->unitPages()->exists()) {
            return back()->with('error', 'Role ini masih terhubung dengan halaman unit. Hapus atau ubah halaman unit terkait terlebih dahulu.');
        }

        $role->delete();
        User::refreshRoleConfig();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }

    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}