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
        $perPageInput = (string) $request->query('per_page', '20');
        $requestedPerPage = match ($perPageInput) {
            '40' => 40,
            '60' => 60,
            'all' => null,
            default => 20,
        };
        $selectedCategory = $categorySlug
            ? MediaCategory::active()->where('slug', $categorySlug)->first()
            : null;

        $photoQuery = MediaPhoto::query()
            ->with(['profile', 'category'])
            ->approved()
            ->where('is_featured', true)
            ->whereHas('profile', fn (Builder $query) => $query->where('is_active', true))
            ->when($selectedCategory, fn (Builder $query) => $query->where('media_category_id', $selectedCategory->id))
            ->search($search)
            ->latest();

        $perPage = $perPageInput === 'all'
            ? max((clone $photoQuery)->count(), 1)
            : $requestedPerPage;

        $photos = $photoQuery
            ->paginate($perPage)
            ->withQueryString();

        return view('photo-repository.gallery', [
            'photos' => $photos,
            'categories' => MediaCategory::active()->orderBy('name')->get(),
            'selectedCategory' => $selectedCategory,
            'search' => $search,
            'perPage' => $perPageInput,
            'canManagePhotos' => $request->user()?->is_super_admin || Gate::allows('manage-photo-repository'),
        ]);
    }
}
