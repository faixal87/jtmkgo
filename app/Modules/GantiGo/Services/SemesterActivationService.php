<?php

namespace App\Modules\GantiGo\Services;

use App\Models\User;
use App\Modules\AcademicCore\Services\AcademicCoreProjectionService;
use App\Modules\AcademicCore\Services\AcademicSemesterActivationService;
use App\Modules\GantiGo\Models\Semester;
use Illuminate\Support\Facades\DB;

class SemesterActivationService
{
    public function __construct(
        private readonly AcademicSemesterActivationService $academicSemesters,
        private readonly AcademicCoreProjectionService $projections,
    ) {
    }

    public function autoActivateForToday(): ?Semester
    {
        $academicSemester = $this->academicSemesters->autoActivateForToday();

        if ($academicSemester) {
            return $this->projections->mirrorSemesterForGantiGo($academicSemester);
        }

        $today = now()->toDateString();

        $semester = Semester::query()
            ->where('auto_activate', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderByDesc('start_date')
            ->first();

        if ($semester) {
            if (! $semester->is_active) {
                $this->activate($semester, manual: false);
            }

            return $semester->fresh();
        }

        Semester::query()
            ->where('is_active', true)
            ->where('auto_activate', true)
            ->whereDate('end_date', '<', $today)
            ->update(['is_active' => false]);

        return Semester::query()->active()->latest('start_date')->first();
    }

    public function activate(Semester $semester, ?User $user = null, bool $manual = true): Semester
    {
        return DB::transaction(function () use ($semester) {
            Semester::query()
                ->where('id', '!=', $semester->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $semester->forceFill(['is_active' => true])->save();

            return $semester->fresh();
        });
    }
}
