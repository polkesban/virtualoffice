<?php

namespace App\Support;

use Illuminate\Http\Request;

class LoginCaptcha
{
    private const SESSION_PREFIX = 'login_captcha.';

    public function issue(Request $request, string $context): string
    {
        $left = random_int(1, 9);
        $right = random_int(1, 9);
        $operator = random_int(0, 1) === 1 ? '-' : '+';

        if ($operator === '-' && $right > $left) {
            [$left, $right] = [$right, $left];
        }

        $answer = $operator === '+' ? $left + $right : $left - $right;

        $request->session()->put($this->sessionKey($context), [
            'question' => sprintf('%d %s %d', $left, $operator, $right),
            'answer' => (string) $answer,
        ]);

        return $this->question($request, $context);
    }

    public function question(Request $request, string $context): string
    {
        return (string) $request->session()->get($this->sessionKey($context) . '.question', '');
    }

    public function passes(Request $request, string $context, mixed $answer): bool
    {
        $expected = trim((string) $request->session()->get($this->sessionKey($context) . '.answer', ''));
        $actual = trim((string) $answer);

        return $expected !== '' && $actual !== '' && hash_equals($expected, $actual);
    }

    public function clear(Request $request, string $context): void
    {
        $request->session()->forget($this->sessionKey($context));
    }

    private function sessionKey(string $context): string
    {
        return self::SESSION_PREFIX . $context;
    }
}