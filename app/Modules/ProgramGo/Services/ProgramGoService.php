<?php

namespace App\Modules\ProgramGo\Services;

use App\Models\Module;
use App\Models\ModuleAdmin;
use App\Models\User;
use App\Modules\ProgramGo\Models\ProgramActivity;
use App\Services\NotificationService;
use Illuminate\Support\Collection;

class ProgramGoService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function isModuleAdmin(User $user): bool
    {
        return ModuleAdmin::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('module', fn ($query) => $query->where('slug', 'program-go'))
            ->exists();
    }

    public function canViewAdminInsights(User $user): bool
    {
        return $user->is_super_admin || $this->isModuleAdmin($user);
    }

    public function calculateBudget(array $data): array
    {
        $subtotal = (float) ($data['os_21000'] ?? 0)
            + (float) ($data['os_29000'] ?? 0)
            + (float) ($data['os_42000'] ?? 0);

        $data['subtotal_os'] = round($subtotal, 2);
        $data['total_budget'] = round($subtotal + (float) ($data['hep_allocation'] ?? 0), 2);

        return $data;
    }

    public function activityCodeLabel(string $activityCode): string
    {
        return ProgramActivity::activityCodes()[$activityCode] ?? 'Unknown';
    }

    public function notifySubmission(ProgramActivity $activity, User $actor): void
    {
        $admins = $this->moduleAdmins();

        if ($admins->isEmpty()) {
            return;
        }

        $this->notifications->sendToUsers(
            $admins,
            'ProgramGo Activity Submitted',
            "{$actor->name} submitted {$activity->activity_name} for verification.",
            'program-go',
            $actor
        );
    }

    public function notifyDecision(ProgramActivity $activity, User $actor, string $decision): void
    {
        $title = match ($decision) {
            'approved' => 'ProgramGo Activity Approved',
            'returned' => 'ProgramGo Activity Returned for Correction',
            'rejected' => 'ProgramGo Activity Rejected',
            default => 'ProgramGo Activity Updated',
        };

        $message = match ($decision) {
            'approved' => 'Your programme/activity submission has been approved.',
            'returned' => 'Your programme/activity submission was returned for correction. Please review the admin remarks.',
            'rejected' => 'Your programme/activity submission was rejected. Please review the admin remarks.',
            default => 'Your programme/activity submission has been updated.',
        };

        $activity->loadMissing(['lecturer', 'collaborators.user']);

        $recipients = collect([$activity->lecturer])
            ->merge($activity->collaborators->pluck('user'))
            ->filter()
            ->unique('id')
            ->values();

        foreach ($recipients as $recipient) {
            $this->notifications->send($recipient, $title, $message, 'program-go', $actor);
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function moduleAdmins(): Collection
    {
        $module = Module::query()
            ->where('slug', 'program-go')
            ->first();

        if (! $module) {
            return collect();
        }

        return User::query()
            ->where('account_status', 'approved')
            ->whereHas('adminModules', function ($query) use ($module): void {
                $query
                    ->where('modules.id', $module->id)
                    ->where('module_admins.is_active', true);
            })
            ->get(['id', 'name', 'email']);
    }
}
