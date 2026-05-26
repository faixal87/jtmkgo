<?php

namespace App\Modules\ProgramGo\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\ProgramGo\Models\ProgramActivity;
use App\Modules\ProgramGo\Requests\ReviewProgramActivityRequest;
use App\Modules\ProgramGo\Services\ProgramGoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewSubmissionController extends Controller
{
    public function index(Request $request, ProgramGoService $programGo): View
    {
        $this->authorizeOperationalAdmin($request, $programGo);

        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status', 'reviewable');

        $activities = ProgramActivity::query()
            ->with('lecturer:id,name,email,profile_photo')
            ->search($search)
            ->when($status === 'reviewable', fn ($query) => $query->whereIn('status', ProgramActivity::verificationPendingStatuses()))
            ->when(! in_array($status, ['all', 'reviewable'], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($search !== '' ? 50 : 15)
            ->withQueryString();

        return view('program-go.admin.review-submissions', [
            'activities' => $activities,
            'search' => $search,
            'status' => $status,
            'statuses' => ProgramActivity::statuses(),
        ]);
    }

    public function approve(ReviewProgramActivityRequest $request, ProgramActivity $activity, ProgramGoService $programGo): RedirectResponse
    {
        $this->authorizeOperationalAdmin($request, $programGo);
        $this->ensureReviewable($activity);

        $activity->forceFill([
            'status' => ProgramActivity::STATUS_APPROVED,
            'admin_remarks' => $request->validated('admin_remarks'),
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
        ])->save();

        $programGo->notifyDecision($activity->fresh('lecturer'), $request->user(), 'approved');

        return back()->with('status', 'Activity approved successfully.');
    }

    public function returnForCorrection(ReviewProgramActivityRequest $request, ProgramActivity $activity, ProgramGoService $programGo): RedirectResponse
    {
        $this->authorizeOperationalAdmin($request, $programGo);
        $this->ensureReviewable($activity);

        $activity->forceFill([
            'status' => ProgramActivity::STATUS_RETURNED,
            'admin_remarks' => $request->validated('admin_remarks') ?: 'Please review and correct this submission.',
            'rejected_by' => $request->user()->id,
            'rejected_at' => now(),
        ])->save();

        $programGo->notifyDecision($activity->fresh('lecturer'), $request->user(), 'returned');

        return back()->with('status', 'Activity returned for correction.');
    }

    public function reject(ReviewProgramActivityRequest $request, ProgramActivity $activity, ProgramGoService $programGo): RedirectResponse
    {
        $this->authorizeOperationalAdmin($request, $programGo);
        $this->ensureReviewable($activity);

        $activity->forceFill([
            'status' => ProgramActivity::STATUS_REJECTED,
            'admin_remarks' => $request->validated('admin_remarks') ?: 'Submission rejected.',
            'rejected_by' => $request->user()->id,
            'rejected_at' => now(),
        ])->save();

        $programGo->notifyDecision($activity->fresh('lecturer'), $request->user(), 'rejected');

        return back()->with('status', 'Activity rejected.');
    }

    private function authorizeOperationalAdmin(Request $request, ProgramGoService $programGo): void
    {
        abort_unless($programGo->isModuleAdmin($request->user()), 403, 'Only ProgramGo module admins can perform operational reviews.');
    }

    private function ensureReviewable(ProgramActivity $activity): void
    {
        abort_unless(
            in_array($activity->status, ProgramActivity::verificationPendingStatuses(), true),
            422,
            'Only completed or pending verification activities can be reviewed.'
        );
    }
}
