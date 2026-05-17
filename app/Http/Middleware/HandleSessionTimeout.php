<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HandleSessionTimeout
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $timeoutSeconds = (int) config('session.lifetime', 120) * 60;
        $lastActivity = $request->session()->get('last_activity_at');

        if ($lastActivity && now()->timestamp - $lastActivity > $timeoutSeconds) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Your session has expired. Please log in again.');
        }

        $request->session()->put('last_activity_at', now()->timestamp);

        return $next($request);
    }
}
