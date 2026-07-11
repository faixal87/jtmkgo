<?php

namespace App\Services;

use App\Models\User;
use App\Modules\PhotoRepository\Models\MediaPhoto;
use App\Support\SafeArrayCache;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CurrentMonthBirthdayService
{
    /**
     * @return array{month_label: string, birthdays: array<int, array<string, string|null>>}
     */
    public function forCurrentMonth(): array
    {
        $now = Carbon::now();
        $locale = app()->getLocale();

        return SafeArrayCache::remember(
            "dashboard.birthdays.{$locale}.{$now->format('Y-m')}",
            now()->addMinutes(15),
            fn (): array => $this->buildMonthData($now, $locale),
            ['month_label', 'birthdays']
        );
    }

    /**
     * @return array{month_label: string, birthdays: array<int, array<string, string|null>>}
     */
    private function buildMonthData(Carbon $now, string $locale): array
    {
        $staff = User::query()
            ->approvedStaff()
            ->select(['id', 'name', 'profile_photo', 'date_of_birth', 'position', 'department'])
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', $now->month)
            ->get()
            ->sortBy(fn (User $user): string => sprintf('%02d %s', (int) $user->date_of_birth?->day, strtolower($user->name)))
            ->values();

        $repositoryPhotoUrls = $this->repositoryPhotoUrls($staff);

        return [
            'month_label' => $now->copy()->locale($locale)->isoFormat('MMMM YYYY'),
            'birthdays' => $staff
                ->map(fn (User $user): array => $this->birthdayRow($user, $now, $locale, $repositoryPhotoUrls))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, User>  $staff
     * @return array<int, string|null>
     */
    private function repositoryPhotoUrls(Collection $staff): array
    {
        if ($staff->isEmpty() || ! Schema::hasTable('media_profiles') || ! Schema::hasTable('media_photos')) {
            return [];
        }

        return MediaPhoto::query()
            ->select([
                'media_profiles.linked_user_id',
                'media_photos.thumbnail_path',
                'media_photos.photo_path',
            ])
            ->join('media_profiles', 'media_profiles.id', '=', 'media_photos.media_profile_id')
            ->where('media_photos.status', MediaPhoto::STATUS_APPROVED)
            ->whereIn('media_profiles.linked_user_id', $staff->pluck('id')->all())
            ->orderByDesc('media_photos.is_current_official')
            ->orderByDesc('media_photos.approved_at')
            ->orderByDesc('media_photos.id')
            ->get()
            ->groupBy('linked_user_id')
            ->map(function (Collection $photos): ?string {
                $photo = $photos->first();
                $path = $photo?->thumbnail_path ?: $photo?->photo_path;

                return $path ? Storage::url($path) : null;
            })
            ->all();
    }

    /**
     * @param  array<int, string|null>  $repositoryPhotoUrls
     * @return array<string, string|null>
     */
    private function birthdayRow(User $user, Carbon $now, string $locale, array $repositoryPhotoUrls): array
    {
        $birthDate = $user->date_of_birth instanceof Carbon
            ? $user->date_of_birth
            : Carbon::parse($user->date_of_birth);

        $birthdayThisYear = $this->birthdayInYear($birthDate, (int) $now->year);
        $photoUrl = $user->profile_photo
            ? Storage::url($user->profile_photo)
            : ($repositoryPhotoUrls[$user->id] ?? null);

        return [
            'name' => $user->name,
            'initials' => $user->initials(),
            'photo_url' => $photoUrl,
            'position' => $user->position,
            'department' => $user->department,
            'birth_day' => (string) $birthDate->day,
            'birth_date_label' => $birthDate->copy()->locale($locale)->isoFormat('D MMMM'),
            'birth_month_label' => $birthDate->copy()->locale($locale)->isoFormat('MMMM'),
            'birth_weekday' => $birthDate->copy()->locale($locale)->isoFormat('dddd'),
            'birthday_this_year_label' => $birthdayThisYear->locale($locale)->isoFormat('dddd, D MMMM YYYY'),
        ];
    }

    private function birthdayInYear(Carbon $birthDate, int $year): Carbon
    {
        if ($birthDate->month === 2 && $birthDate->day === 29 && ! Carbon::create($year, 1, 1)->isLeapYear()) {
            return Carbon::create($year, 2, 28)->startOfDay();
        }

        return Carbon::create($year, $birthDate->month, $birthDate->day)->startOfDay();
    }
}
