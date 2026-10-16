<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

abstract class PasswordResetController extends Controller
{
    public function showForgotForm(): View|RedirectResponse
    {
        if ($this->isAuthenticated()) {
            return redirect()->route($this->authenticatedRoute());
        }

        return view($this->forgotView());
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        if ($this->isAuthenticated()) {
            return redirect()->route($this->authenticatedRoute());
        }

        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        if (! $this->canResetEmail($validated['email'])) {
            return back()
                ->withErrors(['email' => $this->statusMessage(Password::INVALID_USER)])
                ->onlyInput('email');
        }

        $status = Password::broker($this->brokerName())->sendResetLink([
            'email' => $validated['email'],
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', $this->statusMessage($status));
        }

        return back()
            ->withErrors(['email' => $this->statusMessage($status)])
            ->onlyInput('email');
    }

    public function showResetForm(Request $request, string $token): View|RedirectResponse
    {
        if ($this->isAuthenticated()) {
            return redirect()->route($this->authenticatedRoute());
        }

        return view($this->resetView(), [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        if ($this->isAuthenticated()) {
            return redirect()->route($this->authenticatedRoute());
        }

        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (! $this->canResetEmail($validated['email'])) {
            return back()
                ->withErrors(['email' => $this->statusMessage(Password::INVALID_USER)])
                ->withInput($request->only('email'));
        }

        $status = Password::broker($this->brokerName())->reset(
            $validated,
            function ($user) use ($validated): void {
                $user->forceFill([
                    'password' => $validated['password'],
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route($this->loginRoute())
                ->with('success', $this->statusMessage($status));
        }

        return back()
            ->withErrors(['email' => $this->statusMessage($status)])
            ->withInput($request->only('email'));
    }

    protected function canResetEmail(string $email): bool
    {
        return true;
    }

    protected function isAuthenticated(): bool
    {
        return auth()->guard($this->guardName())->check();
    }

    protected function statusMessage(string $status): string
    {
        return match ($status) {
            Password::RESET_LINK_SENT => 'Tautan reset password sudah dikirim ke email Anda.',
            Password::PASSWORD_RESET => 'Password berhasil direset. Silakan login dengan password baru.',
            Password::INVALID_USER => 'Kami tidak menemukan akun dengan email tersebut.',
            Password::INVALID_TOKEN => 'Token reset password tidak valid atau sudah kedaluwarsa.',
            Password::RESET_THROTTLED => 'Permintaan reset terlalu sering. Coba lagi beberapa saat lagi.',
            default => 'Proses reset password tidak dapat diselesaikan saat ini.',
        };
    }

    abstract protected function brokerName(): string;

    abstract protected function guardName(): string;

    abstract protected function forgotView(): string;

    abstract protected function resetView(): string;

    abstract protected function loginRoute(): string;

    abstract protected function authenticatedRoute(): string;
}