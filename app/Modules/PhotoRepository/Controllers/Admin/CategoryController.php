<?php

namespace App\Modules\PhotoRepository\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\PhotoRepository\Models\MediaCategory;
use App\Modules\PhotoRepository\Requests\StoreMediaCategoryRequest;
use App\Modules\PhotoRepository\Requests\UpdateMediaCategoryRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-photo-repository');

        return view('photo-repository.admin.categories', [
            'categories' => MediaCategory::withCount('photos')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreMediaCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        MediaCategory::create($validated);

        return back()->with('status', 'Category has been created.');
    }

    public function update(UpdateMediaCategoryRequest $request, MediaCategory $mediaCategory): RedirectResponse
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        $mediaCategory->update($validated);

        return back()->with('status', 'Category has been updated.');
    }

    public function toggle(Request $request, MediaCategory $mediaCategory): RedirectResponse
    {
        Gate::authorize('manage-photo-repository');

        $mediaCategory->forceFill([
            'is_active' => ! $mediaCategory->is_active,
        ])->save();

        return back()->with('status', $mediaCategory->is_active ? 'Category activated.' : 'Category deactivated.');
    }
}
