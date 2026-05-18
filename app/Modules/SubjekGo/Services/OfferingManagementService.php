<?php

namespace App\Modules\SubjekGo\Services;

use App\Modules\AcademicCore\Models\AcademicSubjectOffering;
use App\Modules\AcademicCore\Services\AcademicCoreProjectionService;
use App\Modules\SubjekGo\Models\OfferedSubject;
use App\Modules\SubjekGo\Models\SubjectMaster;
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
