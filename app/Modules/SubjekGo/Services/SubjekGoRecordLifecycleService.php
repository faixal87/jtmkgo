<?php

namespace App\Modules\SubjekGo\Services;

use App\Modules\SubjekGo\Models\ClassGroup;
use App\Modules\SubjekGo\Models\OfferedSubject;
use App\Modules\SubjekGo\Models\Session;
use App\Modules\SubjekGo\Models\SubjectMaster;

class SubjekGoRecordLifecycleService
{
    public function sessionIsUsed(Session $session): bool
    {
        return $session->offeredSubjects()->exists()
            || $session->preferences()->exists();
    }

    public function subjectMasterIsUsed(SubjectMaster $subjectMaster): bool
    {
        return $subjectMaster->offerings()->exists();
    }

    public function classGroupIsUsed(ClassGroup $classGroup): bool
    {
        return $classGroup->offeredSubjects()->exists();
    }

    public function offeredSubjectIsUsed(OfferedSubject $offeredSubject): bool
    {
        return $offeredSubject->classGroups()->exists()
            || $offeredSubject->teachingHistories()->exists()
            || $offeredSubject->choiceOnePreferences()->exists()
            || $offeredSubject->choiceTwoPreferences()->exists()
            || $offeredSubject->choiceThreePreferences()->exists()
            || $offeredSubject->choiceFourPreferences()->exists();
    }
}
