<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->language_preference
            ?? $request->session()->get('locale')
            ?? config('app.locale', 'en');

        app()->setLocale(in_array($locale, ['en', 'ms'], true) ? $locale : 'en');

        return $next($request);
    }
}
