<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureMahasiswa
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('mahasiswa')->check()) {
            return redirect()->route('mahasiswa.login')
                ->with('info', 'Silakan login terlebih dahulu.');
        }

        return $next($request);
    }
}
