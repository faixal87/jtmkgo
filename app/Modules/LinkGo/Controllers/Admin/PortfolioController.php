<?php

namespace App\Modules\LinkGo\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\LinkGo\Models\Portfolio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-link-go');

        return view('link-go.admin.portfolios', [
            'portfolios' => Portfolio::query()
                ->withCount('links')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-link-go');

        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name', '')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', 'unique:link_go_portfolios,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['is_active'] = $request->boolean('is_active', true);

        Portfolio::create($validated);

        return back()->with('status', 'Portfolio created successfully.');
    }

    public function update(Request $request, Portfolio $portfolio): RedirectResponse
    {
        Gate::authorize('manage-link-go');

        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name', '')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', Rule::unique('link_go_portfolios', 'slug')->ignore($portfolio->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $portfolio->update($validated);

        return back()->with('status', 'Portfolio updated successfully.');
    }

    public function toggle(Request $request, Portfolio $portfolio): RedirectResponse
    {
        Gate::authorize('manage-link-go');

        $portfolio->forceFill(['is_active' => ! $portfolio->is_active])->save();

        return back()->with('status', $portfolio->is_active ? 'Portfolio activated.' : 'Portfolio disabled.');
    }
}
