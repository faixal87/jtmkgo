<?php

namespace App\Modules\AcademicCore\Services;

use App\Modules\AcademicCore\Models\AcademicClassGroup;
use App\Modules\AcademicCore\Models\AcademicSemester;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicClassGroupPromotionService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function preview(AcademicSemester $sourceSemester, AcademicSemester $targetSemester): Collection
    {
        return AcademicClassGroup::query()
            ->with(['programme', 'academicAdvisor'])
            ->where('academic_semester_id', $sourceSemester->id)
            ->whereNull('archived_at')
            ->orderBy('class_name')
            ->get()
            ->map(fn (AcademicClassGroup $classGroup): array => [
                'source_class_group_id' => $classGroup->id,
                'source_class_name' => $classGroup->class_name,
                'programme_id' => $classGroup->programme_id,
                'programme_label' => $classGroup->programme?->code ?: 'Shared',
                'cohort' => $classGroup->cohort,
                'current_semester' => $this->suggestSemesterLevel($classGroup->current_semester),
                'class_name' => $this->suggestClassName($classGroup->class_name),
                'academic_advisor_user_id' => $classGroup->academic_advisor_user_id,
                'academic_advisor_name' => $classGroup->academicAdvisor?->name,
                'remarks' => $classGroup->remarks,
                'is_active' => true,
                'target_semester_label' => "{$targetSemester->name} ({$targetSemester->academic_session})",
            ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function promote(AcademicSemester $sourceSemester, AcademicSemester $targetSemester, array $rows): int
    {
        $sourceIds = collect($rows)->pluck('source_class_group_id')->map(fn ($id) => (int) $id)->all();
        $validSourceIds = AcademicClassGroup::query()
            ->where('academic_semester_id', $sourceSemester->id)
            ->whereIn('id', $sourceIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($validSourceIds) !== count(array_unique($sourceIds))) {
            throw ValidationException::withMessages([
                'rows' => 'One or more source class groups do not belong to the selected source semester.',
            ]);
        }

        $normalizedRows = collect($rows)->map(fn (array $row): array => [
            'programme_id' => (int) $row['programme_id'],
            'class_name' => strtoupper(trim((string) $row['class_name'])),
            'cohort' => filled($row['cohort'] ?? null) ? trim((string) $row['cohort']) : null,
            'current_semester' => filled($row['current_semester'] ?? null) ? trim((string) $row['current_semester']) : null,
            'academic_advisor_user_id' => filled($row['academic_advisor_user_id'] ?? null)
                ? (int) $row['academic_advisor_user_id']
                : null,
            'remarks' => filled($row['remarks'] ?? null) ? trim((string) $row['remarks']) : null,
            'is_active' => (bool) ($row['is_active'] ?? true),
        ]);

        $duplicateWithinPayload = $normalizedRows
            ->groupBy(fn (array $row): string => implode('|', [
                $targetSemester->id,
                $row['programme_id'],
                $row['class_name'],
            ]))
            ->first(fn (Collection $rows): bool => $rows->count() > 1);

        if ($duplicateWithinPayload) {
            throw ValidationException::withMessages([
                'rows' => 'Duplicate class group names were found in the promotion preview.',
            ]);
        }

        $existingDuplicate = $normalizedRows->first(function (array $row) use ($targetSemester): bool {
            return AcademicClassGroup::query()
                ->where('academic_semester_id', $targetSemester->id)
                ->where('programme_id', $row['programme_id'])
                ->where('class_name', $row['class_name'])
                ->exists();
        });

        if ($existingDuplicate) {
            throw ValidationException::withMessages([
                'rows' => 'A promoted class group already exists in the target semester.',
            ]);
        }

        return DB::transaction(function () use ($normalizedRows, $targetSemester): int {
            $normalizedRows->each(function (array $row) use ($targetSemester): void {
                AcademicClassGroup::query()->create($row + [
                    'academic_semester_id' => $targetSemester->id,
                ]);
            });

            return $normalizedRows->count();
        });
    }

    private function suggestClassName(?string $className): ?string
    {
        if (! filled($className)) {
            return $className;
        }

        return preg_replace_callback('/\d+/', function (array $matches): string {
            return (string) (((int) $matches[0]) + 1);
        }, strtoupper($className), 1) ?: strtoupper($className);
    }

    private function suggestSemesterLevel(?string $semesterLevel): ?string
    {
        if (! filled($semesterLevel)) {
            return $semesterLevel;
        }

        if (is_numeric($semesterLevel)) {
            return (string) (((int) $semesterLevel) + 1);
        }

        return preg_replace_callback('/\d+/', function (array $matches): string {
            return (string) (((int) $matches[0]) + 1);
        }, $semesterLevel, 1) ?: $semesterLevel;
    }
}
