<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Breadcrumbs
{
    /**
     * @return array<int, array{label: string, url: string|null}>
     */
    public static function for(Request $request): array
    {
        $routeName = (string) $request->route()?->getName();

        if ($routeName === '') {
            return [];
        }

        $items = [
            self::item('Dashboard', self::routeUrl('dashboard')),
        ];

        if ($routeName === 'dashboard') {
            return $items;
        }

        return match (true) {
            str_starts_with($routeName, 'staff-directory.') => self::append($items, [
                self::item('Staff Directory', null),
            ]),
            str_starts_with($routeName, 'profile.') => self::append($items, [
                self::item('Profile', null),
            ]),
            str_starts_with($routeName, 'module-access-requests.') => self::append($items, [
                self::item('Request Module Access', null),
            ]),
            str_starts_with($routeName, 'notifications.') => self::append($items, [
                self::item('Notifications', null),
            ]),
            str_starts_with($routeName, 'admin.notifications.') => self::adminTrail($items, 'Notifications'),
            str_starts_with($routeName, 'admin.module-access-requests.') => self::adminTrail($items, 'Access Requests'),
            str_starts_with($routeName, 'super-admin.access-control.') => self::adminTrail($items, 'Access Control'),
            str_starts_with($routeName, 'super-admin.settings.') => self::adminTrail($items, 'Branding Settings'),
            str_starts_with($routeName, 'super-admin.users.') => self::adminTrail($items, 'User Management', 'super-admin.users.index', $routeName),
            str_starts_with($routeName, 'academic-core.') => self::academicCoreTrail($items, $routeName),
            str_starts_with($routeName, 'ganti-go.') => self::moduleTrail($items, 'Ganti Go', 'ganti-go.dashboard', self::gantiGoSection($routeName), $routeName),
            str_starts_with($routeName, 'photo-repository.') => self::moduleTrail($items, 'Photo Repository', 'photo-repository.dashboard', self::photoRepositorySection($routeName), $routeName),
            str_starts_with($routeName, 'program-go.') => self::moduleTrail($items, 'ProgramGo', 'program-go.dashboard', self::programGoSection($routeName), $routeName),
            str_starts_with($routeName, 'link-go.') => self::moduleTrail($items, 'LinkGo', 'link-go.dashboard', self::linkGoSection($routeName), $routeName),
            str_starts_with($routeName, 'subjek-go.') => self::moduleTrail($items, 'SubjekGo', 'subjek-go.dashboard', self::subjekGoSection($routeName), $routeName),
            str_starts_with($routeName, 'survey-go.') => self::moduleTrail($items, 'Survey', 'survey-go.dashboard', self::surveySection($routeName), $routeName),
            default => $items,
        };
    }

    /**
     * @param  array<int, array{label: string, url: string|null}>  $items
     * @param  array<int, array{label: string, url: string|null}>  $more
     * @return array<int, array{label: string, url: string|null}>
     */
    private static function append(array $items, array $more): array
    {
        return array_values([...$items, ...$more]);
    }

    /**
     * @return array{label: string, url: string|null}
     */
    private static function item(string $label, ?string $url = null): array
    {
        return ['label' => $label, 'url' => $url];
    }

    /**
     * @param  array<int, array{label: string, url: string|null}>  $items
     * @return array<int, array{label: string, url: string|null}>
     */
    private static function adminTrail(array $items, string $leaf, ?string $indexRoute = null, ?string $routeName = null): array
    {
        $trail = [
            self::item('Admin', null),
        ];

        $leafUrl = $indexRoute && $routeName !== $indexRoute ? self::routeUrl($indexRoute) : null;
        $trail[] = self::item($leaf, $leafUrl);

        if ($routeName) {
            $action = self::actionLabel($routeName);

            if ($action) {
                $trail[] = self::item($action, null);
            }
        }

        return self::append($items, $trail);
    }

    /**
     * @param  array<int, array{label: string, url: string|null}>  $items
     * @return array<int, array{label: string, url: string|null}>
     */
    private static function academicCoreTrail(array $items, string $routeName): array
    {
        $section = match (true) {
            str_starts_with($routeName, 'academic-core.semesters.') => ['Academic Semesters', 'academic-core.semesters.index'],
            str_starts_with($routeName, 'academic-core.subjects.') => ['Academic Subjects', 'academic-core.subjects.index'],
            str_starts_with($routeName, 'academic-core.class-groups.') => ['Class Groups', 'academic-core.class-groups.index'],
            str_starts_with($routeName, 'academic-core.offerings.') => ['Subject Offerings', 'academic-core.offerings.index'],
            default => ['Academic Core', null],
        };

        $trail = [
            self::item('Admin', null),
            self::item('Academic Core', null),
            self::item($section[0], $section[1] && $routeName !== $section[1] ? self::routeUrl($section[1]) : null),
        ];

        $action = self::actionLabel($routeName);

        if ($action) {
            $trail[] = self::item($action, null);
        }

        return self::append($items, $trail);
    }

    /**
     * @param  array<int, array{label: string, url: string|null}>  $items
     * @param  array{0: string, 1: string|null}|null  $section
     * @return array<int, array{label: string, url: string|null}>
     */
    private static function moduleTrail(array $items, string $moduleLabel, string $moduleRoute, ?array $section, string $routeName): array
    {
        $trail = [
            self::item($moduleLabel, $routeName === $moduleRoute ? null : self::routeUrl($moduleRoute)),
        ];

        if ($section) {
            $trail[] = self::item($section[0], $section[1] && $routeName !== $section[1] ? self::routeUrl($section[1]) : null);
        }

        $action = self::actionLabel($routeName);

        if ($action) {
            $trail[] = self::item($action, null);
        }

        return self::append($items, $trail);
    }

    /**
     * @return array{0: string, 1: string|null}|null
     */
    private static function gantiGoSection(string $routeName): ?array
    {
        return match (true) {
            str_starts_with($routeName, 'ganti-go.replacements.') => ['Replacements', 'ganti-go.replacements.index'],
            str_starts_with($routeName, 'ganti-go.analytics') => ['Analytics', 'ganti-go.analytics'],
            str_starts_with($routeName, 'ganti-go.monitoring') => ['Monitoring', 'ganti-go.monitoring'],
            str_starts_with($routeName, 'ganti-go.courses.') => ['Courses', 'ganti-go.courses.index'],
            str_starts_with($routeName, 'ganti-go.classes.') => ['Classes', 'ganti-go.classes.index'],
            str_starts_with($routeName, 'ganti-go.semesters.') => ['Semesters', 'ganti-go.semesters.index'],
            default => null,
        };
    }

    /**
     * @return array{0: string, 1: string|null}|null
     */
    private static function photoRepositorySection(string $routeName): ?array
    {
        return match (true) {
            str_starts_with($routeName, 'photo-repository.gallery') => ['Gallery', 'photo-repository.gallery'],
            str_starts_with($routeName, 'photo-repository.my-photos') || str_starts_with($routeName, 'photo-repository.photos.') => ['My Photos', 'photo-repository.my-photos'],
            str_starts_with($routeName, 'photo-repository.upload') => ['Upload Photo', 'photo-repository.upload'],
            str_starts_with($routeName, 'photo-repository.admin.analytics') => ['Analytics', 'photo-repository.admin.analytics'],
            str_starts_with($routeName, 'photo-repository.admin.review-queue') => ['Review Queue', 'photo-repository.admin.review-queue'],
            str_starts_with($routeName, 'photo-repository.admin.profiles') => ['Profiles', 'photo-repository.admin.profiles'],
            str_starts_with($routeName, 'photo-repository.admin.categories') => ['Categories', 'photo-repository.admin.categories'],
            default => null,
        };
    }

    /**
     * @return array{0: string, 1: string|null}|null
     */
    private static function programGoSection(string $routeName): ?array
    {
        return match (true) {
            str_starts_with($routeName, 'program-go.activities.') => ['Activities', 'program-go.activities.index'],
            str_starts_with($routeName, 'program-go.admin.review-submissions') => ['Review Submissions', 'program-go.admin.review-submissions'],
            str_starts_with($routeName, 'program-go.admin.budget-monitoring') => ['Budget Monitoring', 'program-go.admin.budget-monitoring'],
            str_starts_with($routeName, 'program-go.admin.activity-codes') => ['Activity Codes', 'program-go.admin.activity-codes'],
            str_starts_with($routeName, 'program-go.admin.reports') => ['Reports', 'program-go.admin.reports'],
            default => null,
        };
    }

    /**
     * @return array{0: string, 1: string|null}|null
     */
    private static function linkGoSection(string $routeName): ?array
    {
        return match (true) {
            str_starts_with($routeName, 'link-go.library') => ['Link Library', 'link-go.library'],
            str_starts_with($routeName, 'link-go.links.create') => ['Submit Link', 'link-go.links.create'],
            str_starts_with($routeName, 'link-go.my-links') || str_starts_with($routeName, 'link-go.links.') => ['My Links', 'link-go.my-links'],
            str_starts_with($routeName, 'link-go.admin.manage-links') => ['Manage Links', 'link-go.admin.manage-links'],
            str_starts_with($routeName, 'link-go.admin.portfolios') => ['Portfolios', 'link-go.admin.portfolios.index'],
            str_starts_with($routeName, 'link-go.admin.analytics') => ['Analytics', 'link-go.admin.analytics'],
            default => null,
        };
    }

    /**
     * @return array{0: string, 1: string|null}|null
     */
    private static function subjekGoSection(string $routeName): ?array
    {
        return match (true) {
            str_starts_with($routeName, 'subjek-go.preferences') => ['Subject Preference', 'subjek-go.preferences'],
            str_starts_with($routeName, 'subjek-go.my-selections') => ['My Selections', 'subjek-go.my-selections'],
            str_starts_with($routeName, 'subjek-go.teaching-experience') => ['My Teaching Experience', 'subjek-go.teaching-experience.index'],
            str_starts_with($routeName, 'subjek-go.admin.preferences') => ['Preference Review', 'subjek-go.admin.preferences'],
            str_starts_with($routeName, 'subjek-go.sessions.') => ['Sessions', 'subjek-go.sessions.index'],
            str_starts_with($routeName, 'subjek-go.subject-masters.') => ['Academic Subjects', 'subjek-go.subject-masters.index'],
            str_starts_with($routeName, 'subjek-go.class-groups.') => ['Class Groups', 'subjek-go.class-groups.index'],
            str_starts_with($routeName, 'subjek-go.offered-subjects.') => ['Subject Offerings', 'subjek-go.offered-subjects.index'],
            str_starts_with($routeName, 'subjek-go.analytics') => ['Analytics', 'subjek-go.analytics'],
            default => null,
        };
    }

    /**
     * @return array{0: string, 1: string|null}|null
     */
    private static function surveySection(string $routeName): ?array
    {
        return match (true) {
            str_starts_with($routeName, 'survey-go.admin.surveys.') => ['Surveys', 'survey-go.admin.surveys.index'],
            str_starts_with($routeName, 'survey-go.admin.questions.') => ['Questions', 'survey-go.admin.questions.index'],
            str_starts_with($routeName, 'survey-go.admin.responses.') => ['Responses', 'survey-go.admin.responses.index'],
            str_starts_with($routeName, 'survey-go.admin.analytics') => ['Analytics', 'survey-go.admin.analytics'],
            str_starts_with($routeName, 'survey-go.surveys.') => ['Survey Form', null],
            default => null,
        };
    }

    private static function actionLabel(string $routeName): ?string
    {
        return match (true) {
            str_ends_with($routeName, '.create') => 'Create',
            str_ends_with($routeName, '.edit') => 'Edit',
            str_ends_with($routeName, '.show') => 'Details',
            str_ends_with($routeName, '.upload') => 'Upload',
            default => null,
        };
    }

    private static function routeUrl(?string $routeName): ?string
    {
        if (! $routeName || ! Route::has($routeName)) {
            return null;
        }

        return route($routeName);
    }
}
