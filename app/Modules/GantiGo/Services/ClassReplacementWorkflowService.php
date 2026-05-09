<?php

namespace App\Modules\GantiGo\Services;

use App\Models\User;
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
        [$attributes, $classIds] = $this->prepareReplacementData($data);
        $isDirectImplementation = (bool) Arr::get($attributes, 'already_implemented', false);

        $replacement = ClassReplacement::create([
            ...$attributes,
            'user_id' => $lecturer->id,
            'status' => $isDirectImplementation
                ? ClassReplacement::STATUS_PENDING_VERIFICATION
                : ClassReplacement::STATUS_PLANNED,
            'implementation_submitted_at' => $isDirectImplementation ? now() : null,
        ]);

        $replacement->classes()->sync($classIds);

        return $replacement->fresh(['semester', 'course', 'programme', 'classes', 'lecturer']);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(ClassReplacement $classReplacement, array $data): ClassReplacement
    {
        [$attributes, $classIds] = $this->prepareReplacementData($data);

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

        $classReplacement->update($attributes);
        $classReplacement->classes()->sync($classIds);

        return $classReplacement->fresh(['semester', 'course', 'programme', 'classes', 'lecturer']);
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

        return $classReplacement->fresh();
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

    public function markOverdueRecords(): int
    {
        return ClassReplacement::query()
            ->where('status', ClassReplacement::STATUS_PLANNED)
            ->whereDate('replacement_date', '<', now()->toDateString())
            ->update(['status' => ClassReplacement::STATUS_OVERDUE]);
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
        $classIds = array_values(array_unique((array) Arr::pull($data, 'class_ids', [])));
        $evidence = Arr::pull($data, 'evidence_file');

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

        return [$data, $classIds];
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
        if (empty($data['course_id']) || empty($data['original_class_date']) || empty($data['replacement_date'])) {
            return false;
        }

        return ClassReplacement::query()
            ->where('user_id', $lecturer->id)
            ->where('course_id', $data['course_id'])
            ->whereDate('original_class_date', $data['original_class_date'])
            ->whereDate('replacement_date', $data['replacement_date'])
            ->whereNotIn('status', [ClassReplacement::STATUS_CANCELLED])
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists();
    }
}
