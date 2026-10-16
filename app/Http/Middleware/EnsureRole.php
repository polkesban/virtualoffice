<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Pastikan user memiliki salah satu role yang diizinkan.
     * Super admin selalu diizinkan.
     *
     * Contoh penggunaan di route:
     *   ->middleware('role:wadir_1,wadir_2')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect()->route('admin.login');
        }

        $user = Auth::user();

        // Super admin selalu lolos
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (! in_array($user->role, $roles)) {
            abort(403, 'Akses ditolak. Role Anda tidak memiliki izin untuk halaman ini.');
        }

        return $next($request);
    }
}
