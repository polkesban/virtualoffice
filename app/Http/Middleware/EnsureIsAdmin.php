<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('admin.login')
                ->with('error', 'Silakan login terlebih dahulu.');
        }

        $user = Auth::user();
        $isAllowed = $user->is_admin
            || session()->has('login_as_original_admin_id')
            || $user->hasRoleAccess('halaman-unit')
            || in_array($user->role, [\App\Models\User::ROLE_DOSEN, \App\Models\User::ROLE_EMPLOYEE], true);

        if (! $isAllowed) {
            Auth::logout();
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
