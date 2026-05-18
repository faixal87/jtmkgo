<?php

namespace App\Modules\AcademicCore\Services;

use App\Modules\AcademicCore\Models\AcademicClassGroup;
use App\Modules\AcademicCore\Models\AcademicSemester;
use App\Modules\AcademicCore\Models\AcademicSubject;
use App\Modules\AcademicCore\Models\AcademicSubjectOffering;
use App\Modules\GantiGo\Models\ClassGroup;
use App\Modules\GantiGo\Models\Course;
use App\Modules\GantiGo\Models\MasterClassGroup;
use App\Modules\GantiGo\Models\MasterCourse;
use App\Modules\GantiGo\Models\Semester;
use App\Modules\GantiGo\Models\SemesterClassGroup;
use App\Modules\GantiGo\Models\SemesterCourse;
use App\Modules\SubjekGo\Models\ClassGroup as SubjekGoClassGroup;
use App\Modules\SubjekGo\Models\SubjectMaster as SubjekGoSubjectMaster;
use Illuminate\Support\Facades\DB;

class AcademicCoreProjectionService
{
    public function mirrorSemesterForGantiGo(AcademicSemester $academicSemester): Semester
    {
        return DB::transaction(function () use ($academicSemester): Semester {
            $semester = Semester::query()
                ->where('academic_semester_id', $academicSemester->id)
                ->first()
                ?? Semester::query()->where('session_code', $academicSemester->academic_session)->first()
                ?? new Semester();

            $semester->fill([
                'name' => $academicSemester->name,
                'session_code' => $academicSemester->academic_session,
                'academic_semester_id' => $academicSemester->id,
                'start_date' => $academicSemester->start_date ?? now()->startOfYear()->toDateString(),
                'end_date' => $academicSemester->end_date ?? now()->endOfYear()->toDateString(),
                'is_active' => $academicSemester->is_current,
                'auto_activate' => $academicSemester->auto_activate,
                'remarks' => $academicSemester->remarks,
                'created_by' => $academicSemester->created_by,
            ])->save();

            Semester::query()
                ->where('id', '!=', $semester->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $academicSemester->subjectOfferings()
                ->with(['subject', 'classGroups'])
                ->active()
                ->get()
                ->each(fn (AcademicSubjectOffering $offering) => $this->mirrorOfferingForGantiGo($semester, $offering));

            return $semester->fresh();
        });
    }

    public function mirrorSubjectForSubjekGo(AcademicSubject $subject): SubjekGoSubjectMaster
    {
        $mirror = SubjekGoSubjectMaster::query()
            ->where('academic_subject_id', $subject->id)
            ->first()
            ?? SubjekGoSubjectMaster::query()->where('course_code', $subject->course_code)->first()
            ?? new SubjekGoSubjectMaster();

        $mirror->fill([
            'academic_subject_id' => $subject->id,
            'course_code' => $subject->course_code,
            'course_name' => $subject->course_name,
            'credit_hour' => $subject->credit_hour,
            'weekly_contact_hour' => $subject->weekly_contact_hour,
            'remarks' => $subject->remarks,
            'is_active' => $subject->is_active,
        ])->save();

        return $mirror;
    }

    public function mirrorClassGroupForSubjekGo(AcademicClassGroup $classGroup): SubjekGoClassGroup
    {
        $mirror = SubjekGoClassGroup::query()
            ->where('academic_class_group_id', $classGroup->id)
            ->first()
            ?? SubjekGoClassGroup::query()
                ->where('programme_id', $classGroup->programme_id)
                ->where('class_name', $classGroup->class_name)
                ->where('cohort', $classGroup->cohort)
                ->first()
            ?? new SubjekGoClassGroup();

        $mirror->fill([
            'academic_class_group_id' => $classGroup->id,
            'programme_id' => $classGroup->programme_id,
            'class_name' => $classGroup->class_name,
            'cohort' => $classGroup->cohort,
            'current_semester' => $classGroup->current_semester,
            'remarks' => $classGroup->remarks,
            'is_active' => $classGroup->is_active,
        ])->save();

        return $mirror;
    }

    /**
     * @return array{subject_master: SubjekGoSubjectMaster, class_group_ids: array<int, int>}
     */
    public function mirrorOfferingForSubjekGo(AcademicSubjectOffering $offering): array
    {
        $offering->loadMissing(['subject', 'classGroups']);
        $subjectMaster = $this->mirrorSubjectForSubjekGo($offering->subject);
        $classGroupIds = $offering->classGroups
            ->map(fn (AcademicClassGroup $classGroup): int => $this->mirrorClassGroupForSubjekGo($classGroup)->id)
            ->values()
            ->all();

        return [
            'subject_master' => $subjectMaster,
            'class_group_ids' => $classGroupIds,
        ];
    }

    private function mirrorOfferingForGantiGo(Semester $semester, AcademicSubjectOffering $offering): void
    {
        $offering->loadMissing(['subject', 'classGroups']);
        $masterCourse = MasterCourse::query()
            ->where('academic_subject_id', $offering->academic_subject_id)
            ->where('programme_id', $offering->programme_id)
            ->first()
            ?? MasterCourse::query()
                ->where('course_code', $offering->subject->course_code)
                ->where('programme_id', $offering->programme_id)
                ->first()
            ?? new MasterCourse();

        $masterCourse->fill([
            'academic_subject_id' => $offering->academic_subject_id,
            'course_code' => $offering->subject->course_code,
            'course_name' => $offering->subject->course_name,
            'programme_id' => $offering->programme_id,
            'is_active' => $offering->subject->is_active,
        ])->save();

        SemesterCourse::updateOrCreate(
            [
                'semester_id' => $semester->id,
                'master_course_id' => $masterCourse->id,
            ],
            ['is_offered' => $offering->is_active]
        );

        $course = Course::query()
            ->where('academic_subject_offering_id', $offering->id)
            ->first()
            ?? Course::query()
                ->where('semester_id', $semester->id)
                ->where('master_course_id', $masterCourse->id)
                ->first()
            ?? new Course();

        $course->fill([
            'academic_subject_offering_id' => $offering->id,
            'master_course_id' => $masterCourse->id,
            'semester_id' => $semester->id,
            'programme_id' => $offering->programme_id,
            'course_code' => $offering->subject->course_code,
            'course_name' => $offering->subject->course_name,
            'class_name' => null,
            'is_active' => $offering->is_active,
        ])->save();

        $offering->classGroups->each(function (AcademicClassGroup $academicClassGroup) use ($semester): void {
            $masterClassGroup = MasterClassGroup::query()
                ->where('academic_class_group_id', $academicClassGroup->id)
                ->first()
                ?? MasterClassGroup::query()
                    ->where('programme_id', $academicClassGroup->programme_id)
                    ->where('class_group_name', $academicClassGroup->class_name)
                    ->first()
                ?? new MasterClassGroup();

            $masterClassGroup->fill([
                'academic_class_group_id' => $academicClassGroup->id,
                'class_group_name' => $academicClassGroup->class_name,
                'programme_id' => $academicClassGroup->programme_id,
                'is_active' => $academicClassGroup->is_active,
            ])->save();

            SemesterClassGroup::updateOrCreate(
                [
                    'semester_id' => $semester->id,
                    'master_class_group_id' => $masterClassGroup->id,
                ],
                ['is_offered' => $academicClassGroup->is_active]
            );

            ClassGroup::query()->updateOrCreate(
                [
                    'semester_id' => $semester->id,
                    'master_class_group_id' => $masterClassGroup->id,
                ],
                [
                    'programme_id' => $academicClassGroup->programme_id,
                    'class_name' => $academicClassGroup->class_name,
                    'is_active' => $academicClassGroup->is_active,
                ]
            );
        });
    }
}
