<?php

namespace App\Modules\GantiGo\Services;

use App\Models\Notification;
use App\Models\User;
use App\Modules\AcademicCore\Models\AcademicSubjectOffering;
use App\Modules\GantiGo\Models\ClassReplacement;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class ClassReplacementWorkflowService
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, User $lecturer): ClassReplacement
    {
        [$attributes, $academicClassGroupIds] = $this->prepareReplacementData($data);
        $isDirectImplementation = (bool) Arr::get($attributes, 'already_implemented', false);

        $replacement = ClassReplacement::create([
            ...$attributes,
            'user_id' => $lecturer->id,
            'status' => $isDirectImplementation
                ? ClassReplacement::STATUS_PENDING_VERIFICATION
                : ClassReplacement::STATUS_PLANNED,
            'implementation_submitted_at' => $isDirectImplementation ? now() : null,
        ]);

        $replacement->academicClassGroups()->sync($academicClassGroupIds);

        $replacement = $replacement->fresh([
            'academicSemester',
            'academicSubjectOffering.subject',
            'academicSubject',
            'academicClassGroups',
            'semester',
            'course',
            'programme',
            'classes',
            'lecturer',
        ]);

        if ($isDirectImplementation) {
            $this->notifyPendingVerification($replacement, $lecturer);
        }

        return $replacement;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(ClassReplacement $classReplacement, array $data): ClassReplacement
    {
        [$attributes, $academicClassGroupIds] = $this->prepareReplacementData($data);

        if ((bool) Arr::get($attributes, 'already_implemented', false)) {
            $attributes = [
                ...$attributes,
                'status' => ClassReplacement::STATUS_PENDING_VERIFICATION,
                'implementation_submitted_at' => now(),
                'implementation_approved_by' => null,
                'implementation_approved_at' => null,
                'implementation_rejected_by' => null,
                'implementation_rejected_at' => null,
            ];
        }

        $isDirectImplementation = (bool) Arr::get($attributes, 'already_implemented', false);

        $classReplacement->update($attributes);
        $classReplacement->academicClassGroups()->sync($academicClassGroupIds);

        $classReplacement = $classReplacement->fresh([
            'academicSemester',
            'academicSubjectOffering.subject',
            'academicSubject',
            'academicClassGroups',
            'semester',
            'course',
            'programme',
            'classes',
            'lecturer',
        ]);

        if ($isDirectImplementation && $classReplacement->lecturer) {
            $this->notifyPendingVerification($classReplacement, $classReplacement->lecturer);
        }

        return $classReplacement;
    }

    public function cancel(ClassReplacement $classReplacement): ClassReplacement
    {
        $classReplacement->forceFill([
            'status' => ClassReplacement::STATUS_CANCELLED,
        ])->save();

        return $classReplacement->fresh();
    }

    public function submitImplementation(ClassReplacement $classReplacement, ?UploadedFile $evidence = null): ClassReplacement
    {
        $evidenceAttributes = $this->storeEvidence($evidence);

        $classReplacement->forceFill([
            ...$evidenceAttributes,
            'already_implemented' => true,
            'status' => ClassReplacement::STATUS_PENDING_VERIFICATION,
            'implementation_submitted_at' => now(),
            'implementation_approved_by' => null,
            'implementation_approved_at' => null,
            'implementation_rejected_by' => null,
            'implementation_rejected_at' => null,
            'implementation_admin_remarks' => null,
        ])->save();

        $classReplacement = $classReplacement->fresh([
            'academicSubjectOffering.subject',
            'academicSubject',
            'course',
            'lecturer',
        ]);

        if ($classReplacement->lecturer) {
            $this->notifyPendingVerification($classReplacement, $classReplacement->lecturer);
        }

        return $classReplacement;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function verifyImplementation(ClassReplacement $classReplacement, User $admin, array $data = []): ClassReplacement
    {
        $this->ensureReviewerCanAct($classReplacement, $admin);

        $classReplacement->forceFill([
            'status' => ClassReplacement::STATUS_VERIFIED,
            'implementation_approved_by' => $admin->id,
            'implementation_approved_at' => now(),
            'implementation_rejected_by' => null,
            'implementation_rejected_at' => null,
            'implementation_admin_remarks' => Arr::get($data, 'implementation_admin_remarks'),
        ])->save();

        $this->notifications->send(
            $classReplacement->lecturer,
            'Ganti Go Implementation Approved',
            'Your class replacement implementation has been approved.',
            'ganti-go',
            $admin
        );

        return $classReplacement->fresh();
    }

    /**
     * Backward-compatible name for existing route/controller code.
     *
     * @param array<string, mixed> $data
     */
    public function approveImplementation(ClassReplacement $classReplacement, User $admin, array $data = []): ClassReplacement
    {
        return $this->verifyImplementation($classReplacement, $admin, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function rejectImplementation(ClassReplacement $classReplacement, User $admin, array $data): ClassReplacement
    {
        $this->ensureReviewerCanAct($classReplacement, $admin);

        $classReplacement->forceFill([
            'status' => ClassReplacement::STATUS_REJECTED,
            'implementation_rejected_by' => $admin->id,
            'implementation_rejected_at' => now(),
            'implementation_approved_by' => null,
            'implementation_approved_at' => null,
            'implementation_admin_remarks' => Arr::get($data, 'implementation_admin_remarks'),
        ])->save();

        $this->notifications->send(
            $classReplacement->lecturer,
            'Ganti Go Implementation Rejected',
            'Your class replacement implementation was rejected. Please review the admin remarks and resubmit if required.',
            'ganti-go',
            $admin
        );

        return $classReplacement->fresh();
    }

    /**
     * @throws AuthorizationException
     */
    private function ensureReviewerCanAct(ClassReplacement $classReplacement, User $admin): void
    {
        if ($admin->is_super_admin) {
            throw new AuthorizationException('Only another Ganti Go module admin can verify replacement implementations.');
        }

        if ($classReplacement->blocksSelfVerificationFor($admin)) {
            throw new AuthorizationException('Self-verification is not allowed.');
        }
    }

    private function notifyPendingVerification(ClassReplacement $classReplacement, User $lecturer): void
    {
        $this->notifications->sendToModuleAdminsBySlug(
            'ganti-go',
            'Ganti Go Verification Request',
            "{$lecturer->name} submitted a replacement implementation for {$classReplacement->displayCourseLabel()}.",
            'ganti-go:pending-verification',
            $lecturer,
            route('ganti-go.admin.review-queue', ['status' => ClassReplacement::STATUS_PENDING_VERIFICATION]),
            'Review Replacement',
            $lecturer
        );
    }

    public function markOverdueRecords(): int
    {
        return ClassReplacement::query()
            ->where('status', ClassReplacement::STATUS_PLANNED)
            ->whereDate('replacement_date', '<', now()->toDateString())
            ->update(['status' => ClassReplacement::STATUS_OVERDUE]);
    }

    public function sendImplementationReminders(): int
    {
        $this->markOverdueRecords();

        $today = now()->startOfDay();
        $sent = 0;

        ClassReplacement::query()
            ->with([
                'academicSubjectOffering.subject',
                'academicSubject',
                'academicClassGroups',
                'course',
                'lecturer:id,name,email',
            ])
            ->whereIn('status', [ClassReplacement::STATUS_PLANNED, ClassReplacement::STATUS_OVERDUE])
            ->whereNull('implementation_submitted_at')
            ->whereDate('replacement_date', '<=', $today->toDateString())
            ->chunkById(100, function ($replacements) use ($today, &$sent): void {
                foreach ($replacements as $replacement) {
                    if (! $replacement->lecturer || ! $replacement->replacement_date) {
                        continue;
                    }

                    $daysElapsed = (int) Carbon::parse($replacement->replacement_date)->startOfDay()->diffInDays($today);

                    if ($daysElapsed % 2 !== 0) {
                        continue;
                    }

                    $type = "ganti-go:implementation-reminder:{$replacement->id}:{$today->toDateString()}";

                    $alreadySent = Notification::query()
                        ->where('user_id', $replacement->lecturer->id)
                        ->where('type', $type)
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    $message = implode("\n", [
                        "Please mark your planned class replacement as implemented once the session has been completed.",
                        "Course: {$replacement->displayCourseLabel()}",
                        "Class: {$replacement->formattedClassGroups()}",
                        'Replacement: '.$replacement->replacement_date->format('d M Y').', '.substr((string) $replacement->replacement_start_time, 0, 5).' - '.substr((string) $replacement->replacement_end_time, 0, 5),
                    ]);

                    $this->notifications->send(
                        $replacement->lecturer,
                        'Ganti Go Implementation Reminder',
                        $message,
                        $type,
                        actionUrl: route('ganti-go.replacements.show', $replacement),
                        actionLabel: 'Mark Implementation'
                    );

                    $sent++;
                }
            });

        return $sent;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    public function warningsFor(User $lecturer, array $data, ?ClassReplacement $ignore = null): array
    {
        $warnings = [];
        $originalDuration = $this->durationInMinutes($data['original_start_time'] ?? null, $data['original_end_time'] ?? null);
        $replacementDuration = $this->durationInMinutes($data['replacement_start_time'] ?? null, $data['replacement_end_time'] ?? null);

        if ($originalDuration && $replacementDuration && $originalDuration !== $replacementDuration) {
            $warnings[] = 'Replacement duration differs from original class duration.';
        }

        if ($this->hasOverlappingReplacement($lecturer, $data, $ignore)) {
            $warnings[] = 'This replacement overlaps with another replacement class already recorded under your account.';
        }

        if ($this->hasDuplicateReplacement($lecturer, $data, $ignore)) {
            $warnings[] = 'A similar replacement record already exists for this course and date.';
        }

        return $warnings;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{0: array<string, mixed>, 1: array<int, mixed>}
     */
    private function prepareReplacementData(array $data): array
    {
        $academicClassGroupIds = array_values(array_unique((array) Arr::pull($data, 'academic_class_group_ids', [])));
        $evidence = Arr::pull($data, 'evidence_file');
        $offering = AcademicSubjectOffering::query()
            ->with('subject')
            ->findOrFail((int) $data['academic_subject_offering_id']);

        $data['academic_semester_id'] = $offering->academic_semester_id;
        $data['academic_subject_id'] = $offering->academic_subject_id;
        $data['programme_id'] = $offering->programme_id;
        $data['semester_id'] = null;
        $data['course_id'] = null;

        $data['already_implemented'] = (bool) Arr::get($data, 'already_implemented', false);
        $data['original_duration_minutes'] = $this->durationInMinutes($data['original_start_time'] ?? null, $data['original_end_time'] ?? null);
        $data['replacement_duration_minutes'] = $this->durationInMinutes($data['replacement_start_time'] ?? null, $data['replacement_end_time'] ?? null);

        if (($data['replacement_method'] ?? null) === 'Online') {
            $data['replacement_venue'] = null;
        }

        if ($evidence instanceof UploadedFile) {
            $data = [
                ...$data,
                ...$this->storeEvidence($evidence),
            ];
        }

        return [$data, $academicClassGroupIds];
    }

    private function durationInMinutes(?string $start, ?string $end): ?int
    {
        if (! $start || ! $end) {
            return null;
        }

        $startTime = Carbon::createFromFormat('H:i', $start);
        $endTime = Carbon::createFromFormat('H:i', $end);

        return $endTime->greaterThan($startTime) ? $startTime->diffInMinutes($endTime) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function storeEvidence(?UploadedFile $file): array
    {
        if (! $file) {
            return [];
        }

        return [
            'evidence_path' => $file->store('ganti-go/evidence'),
            'evidence_original_name' => $file->getClientOriginalName(),
            'evidence_uploaded_at' => now(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hasOverlappingReplacement(User $lecturer, array $data, ?ClassReplacement $ignore): bool
    {
        if (empty($data['replacement_date']) || empty($data['replacement_start_time']) || empty($data['replacement_end_time'])) {
            return false;
        }

        return ClassReplacement::query()
            ->where('user_id', $lecturer->id)
            ->whereDate('replacement_date', $data['replacement_date'])
            ->whereNotIn('status', [ClassReplacement::STATUS_CANCELLED])
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->where('replacement_start_time', '<', $data['replacement_end_time'])
            ->where('replacement_end_time', '>', $data['replacement_start_time'])
            ->exists();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hasDuplicateReplacement(User $lecturer, array $data, ?ClassReplacement $ignore): bool
    {
        if (
            (empty($data['academic_subject_offering_id']) && empty($data['course_id']))
            || empty($data['original_class_date'])
            || empty($data['replacement_date'])
        ) {
            return false;
        }

        return ClassReplacement::query()
            ->where('user_id', $lecturer->id)
            ->when(
                ! empty($data['academic_subject_offering_id']),
                fn ($query) => $query->where('academic_subject_offering_id', $data['academic_subject_offering_id']),
                fn ($query) => $query->where('course_id', $data['course_id'])
            )
            ->whereDate('original_class_date', $data['original_class_date'])
            ->whereDate('replacement_date', $data['replacement_date'])
            ->whereNotIn('status', [ClassReplacement::STATUS_CANCELLED])
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists();
    }
}
