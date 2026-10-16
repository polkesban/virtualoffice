<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Auth\PasswordResetController;
use App\Models\User;

class AdminPasswordResetController extends PasswordResetController
{
    protected function brokerName(): string
    {
        return 'users';
    }

    protected function guardName(): string
    {
        return 'web';
    }

    protected function forgotView(): string
    {
        return 'admin.password.forgot';
    }

    protected function resetView(): string
    {
        return 'admin.password.reset';
    }

    protected function loginRoute(): string
    {
        return 'admin.login';
    }

    protected function authenticatedRoute(): string
    {
        return 'admin.dashboard';
    }

    protected function canResetEmail(string $email): bool
    {
        return User::query()
            ->where('email', $email)
            ->where('is_admin', true)
            ->exists();
    }

    protected function isAuthenticated(): bool
    {
        return auth()->check() && auth()->user()?->is_admin;
    }
}