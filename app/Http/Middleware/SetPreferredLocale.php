<?php

namespace App\Http\Middleware;

use App\Support\PublicLocale;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPreferredLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $queryLocale = $request->query('lang');
        $cookieLocale = $request->cookie(PublicLocale::cookieName());
        $locale = PublicLocale::normalize($queryLocale ?: $cookieLocale ?: PublicLocale::default());

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        setlocale(LC_TIME, PublicLocale::localeTag($locale) . '.UTF-8', PublicLocale::localeTag($locale));

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('Content-Language', $locale);
        $response->headers->set('Vary', trim($response->headers->get('Vary', '') . ', Cookie'));

        if (is_string($queryLocale) && $queryLocale !== '') {
            cookie()->queue(cookie(
                PublicLocale::cookieName(),
                $locale,
                60 * 24 * 365,
                path: '/',
                secure: $request->isSecure(),
                httpOnly: false,
                sameSite: 'lax',
            ));
        }

        return $response;
    }
}