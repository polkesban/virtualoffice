<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Auth\PasswordResetController;

class MahasiswaPasswordResetController extends PasswordResetController
{
    protected function brokerName(): string
    {
        return 'mahasiswa';
    }

    protected function guardName(): string
    {
        return 'mahasiswa';
    }

    protected function forgotView(): string
    {
        return 'mahasiswa.password.forgot';
    }

    protected function resetView(): string
    {
        return 'mahasiswa.password.reset';
    }

    protected function loginRoute(): string
    {
        return 'mahasiswa.login';
    }

    protected function authenticatedRoute(): string
    {
        return 'mahasiswa.portofolio.index';
    }
}