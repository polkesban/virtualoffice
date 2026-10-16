<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginCaptchaTest extends TestCase
{
    public function test_admin_login_page_shows_captcha_field(): void
    {
        $response = $this->get(route('admin.login'));

        $response->assertOk();
        $response->assertSee('Captcha');
        $response->assertSee('Masukkan hasil perhitungan');
    }

    public function test_admin_login_rejects_invalid_captcha(): void
    {
        $this->get(route('admin.login'));
        session([
            'login_captcha.admin' => [
                'question' => '2 + 3',
                'answer' => '5',
            ],
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login.post'), [
            '_token' => session()->token(),
            'email' => 'admin@example.com',
            'password' => 'password',
            'captcha' => '9',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors('captcha');
    }

    public function test_mahasiswa_login_page_shows_captcha_field(): void
    {
        $response = $this->get(route('mahasiswa.login'));

        $response->assertOk();
        $response->assertSee('Captcha');
        $response->assertSee('Masukkan hasil perhitungan');
    }

    public function test_mahasiswa_login_rejects_invalid_captcha(): void
    {
        $this->get(route('mahasiswa.login'));
        session([
            'login_captcha.mahasiswa' => [
                'question' => '6 - 1',
                'answer' => '5',
            ],
        ]);

        $response = $this->from(route('mahasiswa.login'))->post(route('mahasiswa.login.post'), [
            '_token' => session()->token(),
            'nim' => '2200001',
            'password' => 'password',
            'captcha' => '8',
        ]);

        $response->assertRedirect(route('mahasiswa.login'));
        $response->assertSessionHasErrors('captcha');
    }
}