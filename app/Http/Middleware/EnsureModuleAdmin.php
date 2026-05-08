<?php

namespace App\Http\Middleware;

use App\Models\Module;
use App\Models\ModuleAdmin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $moduleSlug = null): Response
    {
        $user = $request->user();
        $module = $moduleSlug
            ? Module::where('slug', $moduleSlug)->first()
            : $request->route('module');

        if (is_string($module)) {
            $module = Module::where('slug', $module)->first();
        }

        if (! $user || ! $module) {
            abort(403, 'You are not authorized to manage this module.');
        }

        if ($user->is_super_admin) {
            return $next($request);
        }

        $isModuleAdmin = ModuleAdmin::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('is_active', true)
            ->exists();

        if (! $isModuleAdmin) {
            abort(403, 'You can only manage modules assigned to you.');
        }

        return $next($request);
    }
}
