<?php

namespace App\Modules\PhotoRepository\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PhotoRepository\Models\MediaCategory;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('view-photo-repository');

        $search = trim((string) $request->query('q'));
        $categorySlug = $request->query('category');
        $selectedCategory = $categorySlug
            ? MediaCategory::active()->where('slug', $categorySlug)->first()
            : null;

        $photos = MediaPhoto::query()
            ->with(['profile', 'category'])
            ->approved()
            ->whereHas('profile', fn (Builder $query) => $query->where('is_active', true))
            ->when($selectedCategory, fn (Builder $query) => $query->where('media_category_id', $selectedCategory->id))
            ->search($search)
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('photo-repository.gallery', [
            'photos' => $photos,
            'categories' => MediaCategory::active()->orderBy('name')->get(),
            'selectedCategory' => $selectedCategory,
            'search' => $search,
        ]);
    }
}
