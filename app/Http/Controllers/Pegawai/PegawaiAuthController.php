<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Support\LoginCaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PegawaiAuthController extends Controller
{
    private const CAPTCHA_CONTEXT = 'pegawai';

    public function showLogin(Request $request, LoginCaptcha $captcha): View|RedirectResponse
    {
        if (Auth::check()) {
            $intended = session()->get('url.intended');
            if ($intended && preg_match('#/admin($|/)#', parse_url($intended, PHP_URL_PATH) ?? '')) {
                session()->forget('url.intended');
            }
            return redirect()->intended(route('home'));
        }

        return view('pegawai.login', [
            'captchaQuestion' => $captcha->issue($request, self::CAPTCHA_CONTEXT),
        ]);
    }

    public function login(Request $request, LoginCaptcha $captcha): RedirectResponse
    {
        $credentials = $request->validate([
            'identifier' => 'required|string|max:255',
            'password' => 'required',
            'captcha' => 'required',
        ], [
            'captcha.required' => 'Captcha wajib diisi.',
        ]);

        if (! $captcha->passes($request, self::CAPTCHA_CONTEXT, $request->input('captcha'))) {
            return back()->withErrors(['captcha' => 'Captcha tidak sesuai.'])->withInput();
        }

        $attempt = [
            $this->loginField($credentials['identifier']) => $this->normalizeIdentifier($credentials['identifier']),
            'password' => $credentials['password'],
        ];

        if (Auth::attempt($attempt, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $captcha->clear($request, self::CAPTCHA_CONTEXT);

            $intended = session()->get('url.intended');
            if ($intended && preg_match('#/admin($|/)#', parse_url($intended, PHP_URL_PATH) ?? '')) {
                session()->forget('url.intended');
            }

            return redirect()->intended(route('admin.halaman-unit.index'));
        }

        return back()->withErrors([
            'identifier' => 'NIP, email, atau password salah.',
        ])->withInput($request->only('identifier'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('pegawai.login');
    }

    private function loginField(string $identifier): string
    {
        return filter_var(trim($identifier), FILTER_VALIDATE_EMAIL) ? 'email' : 'identity_number';
    }

    private function normalizeIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return Str::lower($identifier);
        }

        return Str::upper((string) preg_replace('/[^A-Za-z0-9]+/', '', $identifier));
    }
}