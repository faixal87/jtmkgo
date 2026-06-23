<?php

namespace App\Modules\ProgramGo\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\ProgramGo\Models\ProgramActivity;
use App\Modules\ProgramGo\Requests\StoreProgramActivityRequest;
use App\Modules\ProgramGo\Services\ProgramGoService;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramActivityController extends Controller
{
    public function index(Request $request, ProgramGoService $programGo): View
    {
        $workspace = in_array($request->query('view'), ['my', 'shared', 'other'], true)
            ? (string) $request->query('view')
            : null;
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status', 'all');
        $activityCode = (string) $request->query('activity_code', 'all');
        $speakerType = (string) $request->query('speaker_type', 'all');

        $activities = null;

        if ($workspace) {
            $activities = ProgramActivity::query()
                ->with([
                    'lecturer:id,name,email,profile_photo',
                    'collaborators.user:id,name,email,profile_photo,staff_short_code',
                ])
                ->when(
                    $workspace === 'my',
                    fn ($query) => $query->where('user_id', $request->user()->id),
                    fn ($query) => $query->when(
                        $workspace === 'shared',
                        fn ($query) => $query
                            ->where('user_id', '!=', $request->user()->id)
                            ->whereHas('collaborators', fn ($query) => $query->where('user_id', $request->user()->id)),
                        fn ($query) => $query
                            ->where('user_id', '!=', $request->user()->id)
                            ->where('status', ProgramActivity::STATUS_APPROVED)
                    )
                )
                ->search($search)
                ->when(in_array($workspace, ['my', 'shared'], true) && $status !== 'all', fn ($query) => $query->where('status', $status))
                ->when($activityCode !== 'all', fn ($query) => $query->where('activity_code', $activityCode))
                ->when($speakerType !== 'all', fn ($query) => $query->where('speaker_type', $speakerType))
                ->latest()
                ->paginate($search !== '' ? 50 : 15)
                ->withQueryString();
        }

        return view('program-go.activities.index', [
            'activities' => $activities,
            'workspace' => $workspace,
            'search' => $search,
            'status' => $status,
            'activityCode' => $activityCode,
            'speakerType' => $speakerType,
            'statuses' => ProgramActivity::statuses(),
            'activityCodes' => ProgramActivity::activityCodes(),
            'speakerTypes' => ProgramActivity::speakerTypes(),
            'canManage' => $programGo->isModuleAdmin($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        abort_if($request->user()->is_super_admin, 403, 'Super admin can only view ProgramGo analytics.');

        return view('program-go.activities.create', [
            'activity' => new ProgramActivity,
            'activityCodes' => ProgramActivity::activityCodes(),
            'speakerTypes' => ProgramActivity::speakerTypes(),
            'participantRanges' => ProgramActivity::participantRanges(),
            'collaboratorOptions' => $this->collaboratorOptions($request->user()->id),
            'canManageCollaborators' => true,
            'canSubmitActivity' => true,
        ]);
    }

    public function store(StoreProgramActivityRequest $request, ProgramGoService $programGo): RedirectResponse
    {
        $data = $this->payload($request, $programGo);
        $data['user_id'] = $request->user()->id;
        $data['status'] = $this->statusFromIntent((string) $request->input('intent'));

        $activity = ProgramActivity::query()->create($data);
        $this->syncCollaborators($activity, $request);

        if ($activity->status === ProgramActivity::STATUS_PENDING) {
            $programGo->notifySubmission($activity, $request->user());
        }

        return redirect()
            ->route('program-go.activities.index', ['view' => 'my'])
            ->with('status', $this->flashMessageForStatus($activity->status, true));
    }

    public function show(Request $request, ProgramActivity $activity, ProgramGoService $programGo): View
    {
        $this->authorizeView($request, $activity, $programGo);

        $activity->load([
            'lecturer:id,name,email',
            'approvedBy:id,name',
            'rejectedBy:id,name',
            'verifiedBy:id,name',
            'collaborators.user:id,name,email,profile_photo,staff_short_code',
        ]);
        $canManage = $programGo->isModuleAdmin($request->user());

        return view('program-go.activities.show', [
            'activity' => $activity,
            'canEdit' => $activity->canBeEditedBy($request->user()),
            'canManage' => $canManage,
            'canDelete' => $activity->canBeDeletedBy($request->user(), $canManage),
            'backView' => $this->backViewFor($activity, $request->user()),
        ]);
    }

    public function edit(Request $request, ProgramActivity $activity): View
    {
        $activity->load('collaborators.user:id,name,email,profile_photo,staff_short_code');

        abort_unless($activity->canBeEditedBy($request->user()), 403, 'Only editable own submissions can be updated.');

        return view('program-go.activities.edit', [
            'activity' => $activity,
            'activityCodes' => ProgramActivity::activityCodes(),
            'speakerTypes' => ProgramActivity::speakerTypes(),
            'participantRanges' => ProgramActivity::participantRanges(),
            'collaboratorOptions' => $this->collaboratorOptions($activity->user_id),
            'canManageCollaborators' => $activity->canManageCollaborators($request->user()),
            'canSubmitActivity' => $activity->canBeSubmittedBy($request->user()),
        ]);
    }

    public function update(StoreProgramActivityRequest $request, ProgramActivity $activity, ProgramGoService $programGo): RedirectResponse
    {
        $activity->load('collaborators');

        abort_unless($activity->canBeEditedBy($request->user()), 403, 'Only editable own submissions can be updated.');

        $data = $this->payload($request, $programGo);
        $data['status'] = $this->statusFromIntent((string) $request->input('intent'));

        abort_if(
            $data['status'] !== $activity->status && ! $activity->canBeSubmittedBy($request->user()),
            403,
            'You can edit this activity, but only the owner or a submit-enabled collaborator can change its workflow status.'
        );

        if ($data['status'] === ProgramActivity::STATUS_PENDING) {
            $data['admin_remarks'] = null;
            $data['rejected_by'] = null;
            $data['rejected_at'] = null;
        }

        $activity->update($data);

        if ($activity->canManageCollaborators($request->user())) {
            $this->syncCollaborators($activity, $request);
        }

        if ($activity->status === ProgramActivity::STATUS_PENDING) {
            $programGo->notifySubmission($activity->fresh('lecturer'), $request->user());
        }

        return redirect()
            ->route('program-go.activities.index', ['view' => $this->backViewFor($activity->loadMissing('collaborators'), $request->user())])
            ->with('status', $this->flashMessageForStatus($activity->status));
    }

    public function destroy(Request $request, ProgramActivity $activity, ProgramGoService $programGo): RedirectResponse
    {
        $isModuleAdmin = $programGo->isModuleAdmin($request->user());

        if (! $activity->canBeDeletedBy($request->user(), $isModuleAdmin)) {
            return back()->with('error', 'Only draft or in-progress activities can be deleted.');
        }

        $activityName = $activity->activity_name;
        $activityId = $activity->id;

        $activity->delete();

        ActivityLogger::record(
            'program_go_activity_deleted',
            "Deleted ProgramGo activity #{$activityId}: {$activityName}",
            $request->user(),
            $request
        );

        $redirectTo = (string) $request->input('redirect_to');

        if ($redirectTo !== '' && str_starts_with($redirectTo, url('/'))) {
            return redirect()->to($redirectTo)->with('status', 'Activity deleted successfully.');
        }

        return redirect()
            ->route('program-go.activities.index', ['view' => 'my'])
            ->with('status', 'Activity deleted successfully.');
    }

    private function statusFromIntent(string $intent): string
    {
        return match ($intent) {
            'in_progress' => ProgramActivity::STATUS_IN_PROGRESS,
            'completed' => ProgramActivity::STATUS_COMPLETED,
            'submit_verification' => ProgramActivity::STATUS_PENDING,
            'returned_save' => ProgramActivity::STATUS_RETURNED,
            default => ProgramActivity::STATUS_DRAFT,
        };
    }

    private function flashMessageForStatus(string $status, bool $created = false): string
    {
        return match ($status) {
            ProgramActivity::STATUS_IN_PROGRESS => 'Activity saved as in progress.',
            ProgramActivity::STATUS_COMPLETED => 'Activity marked as completed.',
            ProgramActivity::STATUS_PENDING => 'Activity submitted for verification.',
            default => $created ? 'Draft saved successfully.' : 'Changes saved successfully.',
        };
    }

    private function payload(StoreProgramActivityRequest $request, ProgramGoService $programGo): array
    {
        $data = collect($request->validated())
            ->except(['intent', 'collaborators'])
            ->all();

        $data['activity_code_label'] = $programGo->activityCodeLabel($data['activity_code']);

        foreach (['os_21000', 'os_29000', 'os_42000', 'hep_allocation'] as $field) {
            $data[$field] = $data[$field] ?? 0;
        }

        return $programGo->calculateBudget($data);
    }

    private function authorizeView(Request $request, ProgramActivity $activity, ProgramGoService $programGo): void
    {
        $activity->loadMissing('collaborators');

        abort_unless(
            $activity->canBeViewedBy($request->user(), $programGo->canViewAdminInsights($request->user())),
            403,
            'You are not authorized to view this ProgramGo activity.'
        );
    }

    private function syncCollaborators(ProgramActivity $activity, StoreProgramActivityRequest $request): void
    {
        $collaborators = collect($request->validated('collaborators', []))
            ->map(fn (array $collaborator): array => [
                'user_id' => (int) $collaborator['user_id'],
                'role' => 'co_author',
                'can_edit' => (bool) ($collaborator['can_edit'] ?? false) || (bool) ($collaborator['can_submit'] ?? false),
                'can_submit' => (bool) ($collaborator['can_submit'] ?? false),
                'added_by' => $request->user()->id,
            ])
            ->filter(fn (array $collaborator): bool => $collaborator['user_id'] !== $activity->user_id)
            ->unique('user_id')
            ->values();

        if ($collaborators->isEmpty()) {
            $activity->collaborators()->delete();

            return;
        }

        $activity->collaborators()
            ->whereNotIn('user_id', $collaborators->pluck('user_id')->all())
            ->delete();

        foreach ($collaborators as $collaborator) {
            $activity->collaborators()->updateOrCreate(
                ['user_id' => $collaborator['user_id']],
                $collaborator
            );
        }
    }

    private function collaboratorOptions(int $ownerId)
    {
        return User::query()
            ->approvedStaff()
            ->where('id', '!=', $ownerId)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'staff_short_code', 'profile_photo'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'short_code' => $user->staff_short_code,
                'label' => trim($user->name.' '.($user->staff_short_code ? "({$user->staff_short_code})" : '')),
            ]);
    }

    private function backViewFor(ProgramActivity $activity, User $user): string
    {
        if ($activity->user_id === $user->id) {
            return 'my';
        }

        if ($activity->collaborators->contains('user_id', $user->id)) {
            return 'shared';
        }

        return 'other';
    }
}
