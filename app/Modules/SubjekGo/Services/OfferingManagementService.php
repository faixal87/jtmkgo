<?php

namespace App\Modules\SubjekGo\Services;

use App\Modules\SubjekGo\Models\OfferedSubject;
use App\Modules\SubjekGo\Models\SubjectMaster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OfferingManagementService
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int>  $classGroupIds
     */
    public function create(array $attributes, array $classGroupIds): OfferedSubject
    {
        return DB::transaction(function () use ($attributes, $classGroupIds): OfferedSubject {
            $subject = new OfferedSubject();
            $subject->fill($attributes);
            $subject->forceFill($this->legacyAttributes($attributes, $classGroupIds));
            $subject->save();
            $subject->classGroups()->sync($classGroupIds);

            return $subject->fresh(['subjectMaster', 'classGroups']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int>  $classGroupIds
     */
    public function update(OfferedSubject $subject, array $attributes, array $classGroupIds): OfferedSubject
    {
        return DB::transaction(function () use ($subject, $attributes, $classGroupIds): OfferedSubject {
            $subject->fill($attributes);
            $subject->forceFill($this->legacyAttributes($attributes, $classGroupIds));
            $subject->save();
            $subject->classGroups()->sync($classGroupIds);

            return $subject->fresh(['subjectMaster', 'classGroups']);
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
}
