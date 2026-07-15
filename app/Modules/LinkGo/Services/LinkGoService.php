<?php

namespace App\Modules\LinkGo\Services;

use App\Models\FeaturePermission;
use App\Models\ModuleAdmin;
use App\Models\User;
use App\Modules\LinkGo\Models\Link;
use App\Services\NotificationService;
use Illuminate\Support\Collection;

class LinkGoService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function isModuleAdmin(User $user): bool
    {
        return ModuleAdmin::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('module', fn ($query) => $query->where('slug', 'link-go'))
            ->exists();
    }

    public function canManage(User $user): bool
    {
        return ! $user->is_super_admin && $this->isModuleAdmin($user);
    }

    public function canSubmit(User $user): bool
    {
        return ! $user->is_super_admin;
    }

    public function canViewKjKproLinks(User $user): bool
    {
        return $user->is_super_admin
            || $this->isModuleAdmin($user)
            || $user->canViewSensitiveStaffDirectory();
    }

    public function notifyLinkPublished(Link $link, User $actor): int
    {
        return $this->notifyVisibleRecipients($link, $actor, 'published');
    }

    public function notifyLinkUpdated(Link $link, User $actor): int
    {
        return $this->notifyVisibleRecipients($link, $actor, 'updated');
    }

    private function notifyVisibleRecipients(Link $link, User $actor, string $event): int
    {
        $link->loadMissing(['portfolio:id,name', 'owner:id,name']);
        $recipients = $this->notificationRecipients($link);

        if ($recipients->isEmpty()) {
            return 0;
        }

        $eventLabel = $event === 'published' ? 'New' : 'Updated';
        $portfolio = $link->portfolio?->name ?: 'No portfolio';
        $visibility = $link->visibilityLabel();
        $owner = $link->owner?->name ?: $actor->name;
        $verb = $event === 'published' ? 'submitted' : 'updated';

        $message = implode("\n", [
            "{$actor->name} {$verb} a LinkGo link.",
            "Title: {$link->title}",
            "Portfolio: {$portfolio}",
            "Visibility: {$visibility}",
            "Owner: {$owner}",
        ]);

        return $this->notifications->sendToUsers(
            $recipients,
            "{$eventLabel} LinkGo Link: {$link->title}",
            $message,
            "link-go:link-{$event}:{$link->id}:".now()->format('YmdHis'),
            $actor,
            route('link-go.links.show', $link),
            'View Link'
        );
    }

    /**
     * @return Collection<int, User>
     */
    private function notificationRecipients(Link $link): Collection
    {
        if ($link->visibility === Link::VISIBILITY_ALL) {
            return User::query()
                ->where('account_status', 'approved')
                ->get(['id', 'name', 'email']);
        }

        if ($link->visibility === Link::VISIBILITY_KJ_KPRO) {
            return User::query()
                ->where('account_status', 'approved')
                ->whereHas('featurePermissions', function ($query): void {
                    $query
                        ->where('permission_key', FeaturePermission::STAFF_DIRECTORY_SENSITIVE_VIEW)
                        ->where('is_active', true);
                })
                ->get(['id', 'name', 'email']);
        }

        return collect();
    }
}
