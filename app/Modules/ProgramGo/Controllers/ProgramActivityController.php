<?php

namespace App\Modules\ProgramGo\Controllers;

use App\Http\Controllers\Controller;
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
        $workspace = in_array($request->query('view'), ['my', 'other'], true)
            ? (string) $request->query('view')
            : null;
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status', 'all');
        $activityCode = (string) $request->query('activity_code', 'all');
        $speakerType = (string) $request->query('speaker_type', 'all');

        $activities = null;

        if ($workspace) {
            $activities = ProgramActivity::query()
                ->with('lecturer:id,name,email,profile_photo')
                ->when(
                    $workspace === 'my',
                    fn ($query) => $query->where('user_id', $request->user()->id),
                    fn ($query) => $query
                        ->where('user_id', '!=', $request->user()->id)
                        ->where('status', ProgramActivity::STATUS_APPROVED)
                )
                ->search($search)
                ->when($workspace === 'my' && $status !== 'all', fn ($query) => $query->where('status', $status))
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
        ]);
    }

    public function store(StoreProgramActivityRequest $request, ProgramGoService $programGo): RedirectResponse
    {
        $data = $this->payload($request, $programGo);
        $data['user_id'] = $request->user()->id;
        $data['status'] = $this->statusFromIntent((string) $request->input('intent'));

        $activity = ProgramActivity::query()->create($data);

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

        return view('program-go.activities.show', [
            'activity' => $activity->load(['lecturer:id,name,email', 'approvedBy:id,name', 'rejectedBy:id,name', 'verifiedBy:id,name']),
            'canEdit' => $activity->canBeEditedBy($request->user()),
            'canManage' => $programGo->isModuleAdmin($request->user()),
            'canDelete' => $activity->canBeDeletedBy($request->user(), $programGo->isModuleAdmin($request->user())),
        ]);
    }

    public function edit(Request $request, ProgramActivity $activity): View
    {
        abort_unless($activity->canBeEditedBy($request->user()), 403, 'Only editable own submissions can be updated.');

        return view('program-go.activities.edit', [
            'activity' => $activity,
            'activityCodes' => ProgramActivity::activityCodes(),
            'speakerTypes' => ProgramActivity::speakerTypes(),
            'participantRanges' => ProgramActivity::participantRanges(),
        ]);
    }

    public function update(StoreProgramActivityRequest $request, ProgramActivity $activity, ProgramGoService $programGo): RedirectResponse
    {
        abort_unless($activity->canBeEditedBy($request->user()), 403, 'Only editable own submissions can be updated.');

        $data = $this->payload($request, $programGo);
        $data['status'] = $this->statusFromIntent((string) $request->input('intent'));

        if ($data['status'] === ProgramActivity::STATUS_PENDING) {
            $data['admin_remarks'] = null;
            $data['rejected_by'] = null;
            $data['rejected_at'] = null;
        }

        $activity->update($data);

        if ($activity->status === ProgramActivity::STATUS_PENDING) {
            $programGo->notifySubmission($activity->fresh('lecturer'), $request->user());
        }

        return redirect()
            ->route('program-go.activities.index', ['view' => 'my'])
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
            ->except('intent')
            ->all();

        $data['activity_code_label'] = $programGo->activityCodeLabel($data['activity_code']);

        foreach (['os_21000', 'os_29000', 'os_42000', 'hep_allocation'] as $field) {
            $data[$field] = $data[$field] ?? 0;
        }

        return $programGo->calculateBudget($data);
    }

    private function authorizeView(Request $request, ProgramActivity $activity, ProgramGoService $programGo): void
    {
        abort_unless(
            $activity->user_id === $request->user()->id
                || $programGo->canViewAdminInsights($request->user())
                || $activity->status === ProgramActivity::STATUS_APPROVED,
            403,
            'You are not authorized to view this ProgramGo activity.'
        );
    }
}
