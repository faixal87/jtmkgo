<?php

namespace App\Modules\SubjekGo\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

trait RespondsWithSubjekGoFeedback
{
    protected function backWithSuccess(string $message): RedirectResponse
    {
        return back()
            ->with('status', $message)
            ->with('notification', $message);
    }

    /**
     * @param  array<int|string, mixed>  $parameters
     */
    protected function routeWithSuccess(string $route, array $parameters, string $message): RedirectResponse
    {
        return Redirect::route($route, $parameters)
            ->with('status', $message)
            ->with('notification', $message);
    }

    protected function safeListWithSuccess(Request $request, string $fallback, string $message): RedirectResponse
    {
        return Redirect::to($this->returnTo($request, $fallback))
            ->with('status', $message)
            ->with('notification', $message);
    }

    protected function returnTo(Request $request, string $fallback): string
    {
        $returnTo = $request->input('return_to', $request->query('return_to'));

        if (! is_string($returnTo) || $returnTo === '') {
            return $fallback;
        }

        if (str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')) {
            return $returnTo;
        }

        $returnHost = parse_url($returnTo, PHP_URL_HOST);
        $returnScheme = parse_url($returnTo, PHP_URL_SCHEME);
        $currentHost = $request->getHost();

        return $returnHost === $currentHost && in_array($returnScheme, ['http', 'https'], true)
            ? $returnTo
            : $fallback;
    }
}
