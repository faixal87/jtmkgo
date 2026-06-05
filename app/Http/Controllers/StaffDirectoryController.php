<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StaffDirectoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $selectedUserId = $request->integer('user_id');
        $perPage = $this->perPage($request);
        $canViewSensitiveStaffDirectory = $request->user()->canViewSensitiveStaffDirectory();
        $canViewAuditRequirementLink = $canViewSensitiveStaffDirectory && Schema::hasColumn('users', 'audit_requirement_link');
        $staffQuery = $this->staffQuery($search, $canViewAuditRequirementLink);

        $staff = (clone $staffQuery)
            ->paginate($perPage)
            ->withQueryString();

        $visibleStaff = $staff->getCollection();

        if ($selectedUserId && ! $visibleStaff->contains('id', $selectedUserId)) {
            $selectedUser = (clone $staffQuery)->whereKey($selectedUserId)->first();

            if ($selectedUser && $search === '') {
                $visibleStaff->prepend($selectedUser);
            }
        }

        if (! $selectedUserId) {
            $selectedUserId = $visibleStaff->first()?->id;
        }

        $staff->setCollection($visibleStaff);
        $selectedPerson = $selectedUserId
            ? $this->staffQuery('', $canViewAuditRequirementLink)->whereKey($selectedUserId)->first()
            : null;
        $officialPhotoUrls = $this->officialPhotoUrls($visibleStaff->merge($selectedPerson ? collect([$selectedPerson]) : collect()));

        if ($request->query('_ajax_list') === 'staff-directory-list') {
            return view('staff-directory.partials.list', [
                'staff' => $staff,
                'search' => $search,
                'selectedUserId' => $selectedUserId,
                'officialPhotoUrls' => $officialPhotoUrls,
            ]);
        }

        if ($request->query('_partial') === 'staff-directory-detail') {
            return view('staff-directory.partials.detail', [
                'person' => $selectedPerson,
                'photoUrl' => $selectedPerson ? ($officialPhotoUrls[$selectedPerson->id] ?? $selectedPerson->profilePhotoUrl()) : null,
                'canViewSensitiveStaffDirectory' => $canViewSensitiveStaffDirectory,
                'canViewAuditRequirementLink' => $canViewAuditRequirementLink,
            ]);
        }

        return view('staff-directory.index', [
            'staff' => $staff,
            'search' => $search,
            'selectedUserId' => $selectedUserId,
            'selectedPerson' => $selectedPerson,
            'officialPhotoUrls' => $officialPhotoUrls,
            'canViewSensitiveStaffDirectory' => $canViewSensitiveStaffDirectory,
            'canViewAuditRequirementLink' => $canViewAuditRequirementLink,
            'perPage' => $perPage,
        ]);
    }

    private function staffQuery(string $search, bool $includeAuditRequirementLink): Builder
    {
        $staffColumns = [
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
        ];

        if ($includeAuditRequirementLink) {
            $staffColumns[] = 'audit_requirement_link';
        }

        return User::query()
            ->approvedStaff()
            ->select($staffColumns)
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
    }

    /**
     * @param  Collection<int, User>  $staff
     * @return Collection<int, string>
     */
    private function officialPhotoUrls(Collection $staff): Collection
    {
        $staff = $staff->filter();

        if ($staff->isEmpty()) {
            return collect();
        }

        return MediaPhoto::query()
            ->approved()
            ->where('is_current_official', true)
            ->whereHas('profile', fn (Builder $query) => $query->whereIn('linked_user_id', $staff->pluck('id')))
            ->with('profile:id,linked_user_id')
            ->latest('approved_at')
            ->get()
            ->unique(fn (MediaPhoto $photo) => $photo->profile?->linked_user_id)
            ->mapWithKeys(fn (MediaPhoto $photo) => [$photo->profile?->linked_user_id => $photo->thumbnailUrl()])
            ->filter();
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 20);

        return in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;
    }
}
