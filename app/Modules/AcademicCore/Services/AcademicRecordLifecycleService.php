<?php

namespace App\Modules\AcademicCore\Services;

use App\Modules\AcademicCore\Models\AcademicClassGroup;
use App\Modules\AcademicCore\Models\AcademicSemester;
use App\Modules\AcademicCore\Models\AcademicSubject;
use App\Modules\AcademicCore\Models\AcademicSubjectOffering;
use App\Modules\GantiGo\Models\Course as GantiGoCourse;
use App\Modules\GantiGo\Models\MasterClassGroup as GantiGoMasterClassGroup;
use App\Modules\GantiGo\Models\MasterCourse as GantiGoMasterCourse;
use App\Modules\GantiGo\Models\Semester as GantiGoSemester;
use App\Modules\SubjekGo\Models\ClassGroup as SubjekGoClassGroup;
use App\Modules\SubjekGo\Models\OfferedSubject as SubjekGoOfferedSubject;
use App\Modules\SubjekGo\Models\Session as SubjekGoSession;
use App\Modules\SubjekGo\Models\SubjectMaster as SubjekGoSubjectMaster;

class AcademicRecordLifecycleService
{
    public function semesterIsUsed(AcademicSemester $semester): bool
    {
        return $semester->subjectOfferings()->exists()
            || GantiGoSemester::query()->where('academic_semester_id', $semester->id)->exists()
            || SubjekGoSession::query()->where('academic_semester_id', $semester->id)->exists();
    }

    public function subjectIsUsed(AcademicSubject $subject): bool
    {
        return $subject->offerings()->exists()
            || GantiGoMasterCourse::query()->where('academic_subject_id', $subject->id)->exists()
            || SubjekGoSubjectMaster::query()->where('academic_subject_id', $subject->id)->exists();
    }

    public function classGroupIsUsed(AcademicClassGroup $classGroup): bool
    {
        return $classGroup->offerings()->exists()
            || GantiGoMasterClassGroup::query()->where('academic_class_group_id', $classGroup->id)->exists()
            || SubjekGoClassGroup::query()->where('academic_class_group_id', $classGroup->id)->exists();
    }

    public function offeringIsUsed(AcademicSubjectOffering $offering): bool
    {
        return $offering->classGroups()->exists()
            || GantiGoCourse::query()->where('academic_subject_offering_id', $offering->id)->exists()
            || SubjekGoOfferedSubject::query()->where('academic_subject_offering_id', $offering->id)->exists();
    }
}
