<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\EnsureModuleAdmin;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureUserApproved;
use App\Http\Middleware\HandleSessionTimeout;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: SetLocale::class);

        $middleware->alias([
            'approved' => EnsureUserApproved::class,
            'module.access' => EnsureModuleAccess::class,
            'module.admin' => EnsureModuleAdmin::class,
            'session.timeout' => HandleSessionTimeout::class,
            'super.admin' => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $_, Request $request) {
            $message = 'The uploaded file is too large. Profile photos must be 10MB or smaller.';

            if ($request->is('profile', 'profile/*')) {
                return back()->withErrors(['profile_photo' => $message]);
            }

            return back()->withErrors(['upload' => $message]);
        });
    })->create();
