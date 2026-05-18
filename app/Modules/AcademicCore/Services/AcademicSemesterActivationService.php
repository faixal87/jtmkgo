<?php

namespace App\Modules\AcademicCore\Services;

use App\Modules\AcademicCore\Models\AcademicSemester;
use Illuminate\Support\Facades\DB;

class AcademicSemesterActivationService
{
    public function autoActivateForToday(): ?AcademicSemester
    {
        $today = now()->toDateString();

        $semester = AcademicSemester::query()
            ->where('auto_activate', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderByDesc('start_date')
            ->first();

        if ($semester) {
            if (! $semester->is_current || $semester->status !== AcademicSemester::STATUS_ACTIVE) {
                $this->activate($semester);
            }

            return $semester->fresh();
        }

        AcademicSemester::query()
            ->where('is_current', true)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today)
            ->update([
                'is_current' => false,
                'status' => AcademicSemester::STATUS_ARCHIVED,
            ]);

        return AcademicSemester::query()->current()->latest('start_date')->first();
    }

    public function activate(AcademicSemester $semester): AcademicSemester
    {
        return DB::transaction(function () use ($semester): AcademicSemester {
            AcademicSemester::query()
                ->where('id', '!=', $semester->id)
                ->where(function ($query): void {
                    $query
                        ->where('is_current', true)
                        ->orWhere('status', AcademicSemester::STATUS_ACTIVE);
                })
                ->update([
                    'is_current' => false,
                    'status' => AcademicSemester::STATUS_ARCHIVED,
                ]);

            $semester->forceFill([
                'status' => AcademicSemester::STATUS_ACTIVE,
                'is_current' => true,
            ])->save();

            return $semester->fresh();
        });
    }
}
