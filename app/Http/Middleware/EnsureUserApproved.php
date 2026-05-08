<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserApproved
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->account_status === 'approved') {
            return $next($request);
        }

        if ($user->account_status === 'pending') {
            return redirect()->route('pending-approval');
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = $user->account_status === 'inactive'
            ? 'Your account is inactive. Please contact the administrator.'
            : 'Your account has been rejected. Please contact the administrator.';

        return redirect()->route('login')->with('status', $message);
    }
}
