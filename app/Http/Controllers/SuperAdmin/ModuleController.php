<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(): View
    {
        return view('super-admin.modules.index', [
            'modules' => Module::query()
                ->withCount([
                    'userAccesses as active_access_count' => fn ($query) => $query->where('is_active', true),
                ])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Module $module): RedirectResponse
    {
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $module->update([
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('status', "{$module->name} description has been updated.");
    }
}
