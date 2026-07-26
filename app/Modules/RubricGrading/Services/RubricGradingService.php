<?php

namespace App\Modules\RubricGrading\Services;

use App\Models\ModuleAdmin;
use App\Models\User;
use App\Modules\RubricGrading\Models\GradingSession;
use App\Modules\RubricGrading\Models\Rubric;
use App\Modules\RubricGrading\Models\RubricCriterion;
use App\Modules\RubricGrading\Models\RubricLevel;
use App\Modules\RubricGrading\Models\SessionStudent;
use Illuminate\Support\Facades\DB;

class RubricGradingService
{
    public function isModuleAdmin(User $user): bool
    {
        return ModuleAdmin::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('module', fn ($query) => $query->where('slug', 'rubric-grading'))
            ->exists();
    }

    public function maxLevelValue(Rubric $rubric): float
    {
        $levels = $rubric->relationLoaded('levels') ? $rubric->levels : $rubric->levels()->get();

        return (float) $levels->max(fn (RubricLevel $level) => (float) $level->value);
    }

    public function totalWeight(Rubric $rubric): float
    {
        $criteria = $rubric->relationLoaded('criteria') ? $rubric->criteria : $rubric->criteria()->get();

        return round((float) $criteria->sum(fn (RubricCriterion $criterion) => (float) $criterion->weight), 2);
    }

    public function criterionScore(Rubric $rubric, RubricCriterion $criterion, float|int|string|null $levelValue): ?float
    {
        if ($levelValue === null || $levelValue === '') {
            return null;
        }

        $max = $this->maxLevelValue($rubric);

        if ($max <= 0) {
            return 0.0;
        }

        return round(((float) $levelValue / $max) * (float) $criterion->weight, 2);
    }

    /**
     * @return array{total: float, graded: int, all: int}
     */
    public function studentTotal(Rubric $rubric, SessionStudent $student): array
    {
        $criteria = $rubric->relationLoaded('criteria') ? $rubric->criteria : $rubric->criteria()->get();
        $scores = $student->relationLoaded('scores') ? $student->scores : $student->scores()->get();
        $scoresByCriterion = $scores->keyBy('criterion_id');

        $total = 0.0;
        $graded = 0;

        foreach ($criteria as $criterion) {
            $score = $scoresByCriterion->get($criterion->id);

            if (! $score) {
                continue;
            }

            $total += $this->criterionScore($rubric, $criterion, $score->level_value) ?? 0;
            $graded++;
        }

        return [
            'total' => round($total, 2),
            'graded' => $graded,
            'all' => $criteria->count(),
        ];
    }

    /**
     * @return array{students: int, graded: int, average: float|null, min: float|null, max: float|null}
     */
    public function sessionStats(GradingSession $session): array
    {
        $session->loadMissing([
            'rubric.criteria',
            'rubric.levels',
            'students.scores',
        ]);

        $totals = $session->students
            ->map(fn (SessionStudent $student): array => $this->studentTotal($session->rubric, $student))
            ->filter(fn (array $summary): bool => $summary['graded'] > 0)
            ->pluck('total');

        return [
            'students' => $session->students->count(),
            'graded' => $totals->count(),
            'average' => $totals->isNotEmpty() ? round((float) $totals->average(), 2) : null,
            'min' => $totals->isNotEmpty() ? round((float) $totals->min(), 2) : null,
            'max' => $totals->isNotEmpty() ? round((float) $totals->max(), 2) : null,
        ];
    }

    public function syncRubricStructure(Rubric $rubric, array $data): Rubric
    {
        return DB::transaction(function () use ($rubric, $data): Rubric {
            $rubric->fill(collect($data)->only([
                'title',
                'course_code',
                'course_name',
                'assessment_type',
            ])->all())->save();

            $levelsByIndex = [];
            $keptLevelIds = [];

            foreach (array_values($data['levels'] ?? []) as $index => $levelData) {
                $level = isset($levelData['id'])
                    ? $rubric->levels()->whereKey($levelData['id'])->first()
                    : null;

                $level ??= new RubricLevel(['rubric_id' => $rubric->id]);
                $level->fill([
                    'label' => $levelData['label'],
                    'value' => $levelData['value'],
                    'sort_order' => $index + 1,
                ])->save();

                $levelsByIndex[$index] = $level;
                $keptLevelIds[] = $level->id;
            }

            $rubric->levels()
                ->whereNotIn('id', $keptLevelIds)
                ->delete();

            $keptCriterionIds = [];

            foreach (array_values($data['criteria'] ?? []) as $criterionIndex => $criterionData) {
                $criterion = isset($criterionData['id'])
                    ? $rubric->criteria()->whereKey($criterionData['id'])->first()
                    : null;

                $criterion ??= new RubricCriterion(['rubric_id' => $rubric->id]);
                $criterion->fill([
                    'name' => $criterionData['name'],
                    'weight' => $criterionData['weight'],
                    'sort_order' => $criterionIndex + 1,
                ])->save();

                $keptCriterionIds[] = $criterion->id;

                foreach ($levelsByIndex as $levelIndex => $level) {
                    $criterion->descriptors()->updateOrCreate(
                        ['level_id' => $level->id],
                        ['description' => $criterionData['descriptors'][$levelIndex] ?? null]
                    );
                }

                $criterion->descriptors()
                    ->whereNotIn('level_id', collect($levelsByIndex)->pluck('id')->all())
                    ->delete();
            }

            $rubric->criteria()
                ->whereNotIn('id', $keptCriterionIds)
                ->delete();

            return $rubric->fresh(['levels', 'criteria.descriptors']);
        });
    }

    public function duplicateRubric(Rubric $source, User $owner): Rubric
    {
        $source->loadMissing(['levels', 'criteria.descriptors']);

        return DB::transaction(function () use ($source, $owner): Rubric {
            $rubric = Rubric::query()->create([
                'user_id' => $owner->id,
                'title' => $source->title.' (Copy)',
                'course_code' => $source->course_code,
                'course_name' => $source->course_name,
                'assessment_type' => $source->assessment_type,
            ]);

            $levelMap = [];

            foreach ($source->levels as $level) {
                $copy = $rubric->levels()->create([
                    'label' => $level->label,
                    'value' => $level->value,
                    'sort_order' => $level->sort_order,
                ]);

                $levelMap[$level->id] = $copy->id;
            }

            foreach ($source->criteria as $criterion) {
                $criterionCopy = $rubric->criteria()->create([
                    'name' => $criterion->name,
                    'weight' => $criterion->weight,
                    'sort_order' => $criterion->sort_order,
                ]);

                foreach ($criterion->descriptors as $descriptor) {
                    $criterionCopy->descriptors()->create([
                        'level_id' => $levelMap[$descriptor->level_id] ?? null,
                        'description' => $descriptor->description,
                    ]);
                }
            }

            return $rubric->fresh(['levels', 'criteria.descriptors']);
        });
    }
}
