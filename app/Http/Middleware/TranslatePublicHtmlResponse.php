<?php

namespace App\Http\Middleware;

use App\Services\Localization\PublicWebsiteTranslator;
use App\Support\PublicLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TranslatePublicHtmlResponse
{
    public function __construct(private readonly PublicWebsiteTranslator $translator)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        $targetLocale = $this->resolveTargetLocale($request, $response);

        if (! $this->shouldTranslate($request, $response, $targetLocale)) {
            return $response;
        }

        try {
            $content = $response->getContent();

            if (is_string($content) && $content !== '') {
                $response->setContent($this->translator->translateHtml($content, $targetLocale));
            }
        } catch (Throwable) {
            return $response;
        }

        return $response;
    }

    private function shouldTranslate(Request $request, Response $response, string $targetLocale): bool
    {
        if (PublicLocale::isDefault($targetLocale)) {
            return false;
        }

        if (! $request->isMethodCacheable() || $request->expectsJson() || $request->is('admin') || $request->is('admin/*')) {
            return false;
        }

        if ($response->getStatusCode() >= 300 || $response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type');

        return Str::contains($contentType, ['text/html', 'application/xhtml+xml']);
    }

    private function resolveTargetLocale(Request $request, Response $response): string
    {
        $headerLocale = $response->headers->get('Content-Language');

        return PublicLocale::normalize(
            $request->query('lang')
            ?: $request->cookie(PublicLocale::cookieName())
            ?: (is_string($headerLocale) ? $headerLocale : null)
            ?: PublicLocale::current()
        );
    }
}