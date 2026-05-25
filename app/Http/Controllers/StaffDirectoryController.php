<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffDirectoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $selectedUserId = $request->integer('user_id');
        $perPage = $search !== '' ? 50 : 20;

        $staffQuery = User::query()
            ->approvedStaff()
            ->select([
                'id',
                'name',
                'email',
                'ic_number',
                'phone',
                'profile_photo',
                'date_of_birth',
                'department',
                'position',
                'grade',
                'mbot_membership',
                'bem_membership',
                'staff_short_code',
            ])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('grade', 'like', "%{$search}%")
                    ->orWhere('staff_short_code', 'like', "%{$search}%");
            }))
            ->orderBy('name');

        $staff = (clone $staffQuery)
            ->paginate($perPage)
            ->withQueryString();

        $visibleStaff = $staff->getCollection();

        if ($search === '' && $selectedUserId && ! $visibleStaff->contains('id', $selectedUserId)) {
            $selectedUser = (clone $staffQuery)->whereKey($selectedUserId)->first();

            if ($selectedUser) {
                $visibleStaff->prepend($selectedUser);
            }
        }

        if (! $selectedUserId || ! $visibleStaff->contains('id', $selectedUserId)) {
            $selectedUserId = $visibleStaff->first()?->id;
        }

        $staff->setCollection($visibleStaff);
        $officialPhotoUrls = $visibleStaff->isEmpty()
            ? collect()
            : MediaPhoto::query()
                ->approved()
                ->where('is_current_official', true)
                ->whereHas('profile', fn (Builder $query) => $query->whereIn('linked_user_id', $visibleStaff->pluck('id')))
                ->with('profile:id,linked_user_id')
                ->latest('approved_at')
                ->get()
                ->unique(fn (MediaPhoto $photo) => $photo->profile?->linked_user_id)
                ->mapWithKeys(fn (MediaPhoto $photo) => [$photo->profile?->linked_user_id => $photo->thumbnailUrl()])
                ->filter();

        return view('staff-directory.index', [
            'staff' => $staff,
            'search' => $search,
            'selectedUserId' => $selectedUserId,
            'officialPhotoUrls' => $officialPhotoUrls,
        ]);
    }
}
