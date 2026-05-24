<?php

namespace App\Modules\SubjekGo\Services;

use App\Modules\AcademicCore\Models\AcademicSubjectOffering;
use App\Modules\AcademicCore\Services\AcademicCoreProjectionService;
use App\Modules\SubjekGo\Models\OfferedSubject;
use App\Modules\SubjekGo\Models\Session;
use App\Modules\SubjekGo\Models\SubjectMaster;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OfferingManagementService
{
    public function __construct(private readonly AcademicCoreProjectionService $projections)
    {
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int>  $classGroupIds
     */
    public function create(array $attributes, array $classGroupIds): OfferedSubject
    {
        return DB::transaction(function () use ($attributes): OfferedSubject {
            [$attributes, $classGroupIds] = $this->attributesFromAcademicOffering($attributes);
            $subject = new OfferedSubject();
            $subject->fill($attributes);
            $subject->forceFill($this->legacyAttributes($attributes, $classGroupIds));
            $subject->save();
            $subject->classGroups()->sync($classGroupIds);

            return $subject->fresh(['subjectMaster', 'academicSubjectOffering.subject', 'classGroups']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int>  $classGroupIds
     */
    public function update(OfferedSubject $subject, array $attributes, array $classGroupIds): OfferedSubject
    {
        return DB::transaction(function () use ($subject, $attributes): OfferedSubject {
            [$attributes, $classGroupIds] = $this->attributesFromAcademicOffering($attributes);
            $subject->fill($attributes);
            $subject->forceFill($this->legacyAttributes($attributes, $classGroupIds));
            $subject->save();
            $subject->classGroups()->sync($classGroupIds);

            return $subject->fresh(['subjectMaster', 'academicSubjectOffering.subject', 'classGroups']);
        });
    }

    /**
     * Preferences still point at SubjekGo offering IDs, so Academic Core offerings are
     * projected into compatibility rows for the selected SubjekGo session.
     *
     * @return Collection<int, OfferedSubject>
     */
    public function syncSessionFromAcademicCore(Session $session): Collection
    {
        if (! $session->academic_semester_id) {
            return $this->projectedQuery($session)
                ->with(['academicSubjectOffering.subject', 'subjectMaster', 'classGroups'])
                ->get();
        }

        $academicOfferings = AcademicSubjectOffering::query()
            ->with(['subject', 'classGroups'])
            ->where('academic_semester_id', $session->academic_semester_id)
            ->active()
            ->get();

        DB::transaction(function () use ($session, $academicOfferings): void {
            $academicOfferings->each(function (AcademicSubjectOffering $academicOffering) use ($session): void {
                [$attributes, $classGroupIds] = $this->attributesFromAcademicOffering([
                    'session_id' => $session->id,
                    'academic_subject_offering_id' => $academicOffering->id,
                ]);

                $subject = OfferedSubject::withTrashed()
                    ->where('session_id', $session->id)
                    ->where('academic_subject_offering_id', $academicOffering->id)
                    ->first()
                    ?? new OfferedSubject();

                if ($subject->trashed()) {
                    $subject->restore();
                }

                $subject->fill($attributes);
                $subject->forceFill($this->legacyAttributes($attributes, $classGroupIds));
                $subject->save();
                $subject->classGroups()->sync($classGroupIds);
            });

            OfferedSubject::query()
                ->where('session_id', $session->id)
                ->whereNotNull('academic_subject_offering_id')
                ->when(
                    $academicOfferings->isNotEmpty(),
                    fn ($query) => $query->whereNotIn('academic_subject_offering_id', $academicOfferings->pluck('id')),
                    fn ($query) => $query
                )
                ->update(['is_active' => false]);
        });

        return $this->projectedQuery($session)
            ->with(['academicSubjectOffering.subject', 'subjectMaster', 'classGroups'])
            ->get();
    }

    public function projectedQuery(Session $session): HasMany
    {
        $query = $session->activeOfferedSubjects();

        if (! $session->academic_semester_id) {
            return $query;
        }

        return $query->whereIn(
            'academic_subject_offering_id',
            AcademicSubjectOffering::query()
                ->select('id')
                ->where('academic_semester_id', $session->academic_semester_id)
                ->active()
        );
    }

    /**
     * Keep partially migrated databases writable until the legacy columns are dropped.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int>  $classGroupIds
     * @return array<string, mixed>
     */
    private function legacyAttributes(array $attributes, array $classGroupIds): array
    {
        $legacyColumns = $this->legacyColumns();

        if ($legacyColumns === []) {
            return [];
        }

        $subjectMaster = SubjectMaster::query()->findOrFail((int) $attributes['subject_master_id']);
        $legacyAttributes = [
            'course_code' => $subjectMaster->course_code,
            'course_name' => $subjectMaster->course_name,
            'credit_hour' => $subjectMaster->credit_hour,
            'weekly_contact_hour' => $subjectMaster->weekly_contact_hour,
            'total_class_groups' => count($classGroupIds),
        ];

        return array_intersect_key($legacyAttributes, array_flip($legacyColumns));
    }

    /**
     * @return array<int, string>
     */
    private function legacyColumns(): array
    {
        return once(fn (): array => array_values(array_filter([
            'course_code',
            'course_name',
            'credit_hour',
            'weekly_contact_hour',
            'total_class_groups',
        ], fn (string $column): bool => Schema::hasColumn('subjek_go_offered_subjects', $column))));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: array<string, mixed>, 1: array<int, int>}
     */
    private function attributesFromAcademicOffering(array $attributes): array
    {
        $academicOffering = AcademicSubjectOffering::query()
            ->with(['subject', 'classGroups'])
            ->findOrFail((int) $attributes['academic_subject_offering_id']);
        $mirror = $this->projections->mirrorOfferingForSubjekGo($academicOffering);

        return [[
            'session_id' => $attributes['session_id'],
            'academic_subject_offering_id' => $academicOffering->id,
            'programme_id' => $academicOffering->programme_id,
            'subject_master_id' => $mirror['subject_master']->id,
            'curriculum_version' => $academicOffering->curriculum_version,
            'offered_semester' => $academicOffering->offered_semester,
            'subject_coordinator_user_id' => $academicOffering->coordinator_user_id,
            'remarks' => $attributes['remarks'] ?? $academicOffering->remarks,
            'is_active' => $academicOffering->is_active,
        ], $mirror['class_group_ids']];
    }
}
