<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\LoginCaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    private const CAPTCHA_CONTEXT = 'admin';

    public function showLogin(Request $request, LoginCaptcha $captcha): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->is_admin) {
            if (! Auth::user()->isSuperAdmin()) {
                return redirect()->route('admin.halaman-unit.index');
            }
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login', [
            'captchaQuestion' => $captcha->issue($request, self::CAPTCHA_CONTEXT),
        ]);
    }

    public function login(Request $request, LoginCaptcha $captcha): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|string',
            'password' => 'required',
            'captcha'  => 'required',
        ], [
            'captcha.required' => 'Captcha wajib diisi.',
        ]);

        if (! $captcha->passes($request, self::CAPTCHA_CONTEXT, $request->input('captcha'))) {
            return back()
                ->withErrors(['captcha' => 'Captcha tidak sesuai.'])
                ->onlyInput('email');
        }

        $loginInput = $credentials['email'];
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
        $loginField = $isEmail ? 'email' : 'identity_number';

        $authCredentials = [
            $loginField => $loginInput,
            'password' => $credentials['password'],
        ];

        if (Auth::attempt($authCredentials, $request->boolean('remember'))) {
            $user = Auth::user();
            $isAllowed = $user->is_admin
                || $user->hasRoleAccess('halaman-unit')
                || in_array($user->role, [\App\Models\User::ROLE_DOSEN, \App\Models\User::ROLE_EMPLOYEE], true);

            if (! $isAllowed) {
                Auth::logout();
                return back()->withErrors(['email' => 'Akun ini tidak memiliki akses admin.']);
            }

            $request->session()->regenerate();
            $captcha->clear($request, self::CAPTCHA_CONTEXT);

            if (! $user->isSuperAdmin()) {
                return redirect()->route('admin.halaman-unit.index');
            }

            return redirect()->intended(route('admin.halaman-unit.index'));
        }

        return back()
            ->withErrors(['email' => 'Email/NIP atau password salah.'])
            ->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
