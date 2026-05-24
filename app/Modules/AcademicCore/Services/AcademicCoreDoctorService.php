<?php

namespace App\Modules\AcademicCore\Services;

use App\Modules\AcademicCore\Models\AcademicSemester;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicCoreDoctorService
{
    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $currentSemester = Schema::hasTable('academic_semesters')
            ? AcademicSemester::query()
                ->current()
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first()
            : null;

        return [
            'current_semester' => $currentSemester
                ? "{$currentSemester->name} ({$currentSemester->academic_session}) [{$currentSemester->status}]"
                : 'None configured',
            'counts' => [
                'Academic subjects' => $this->count('academic_subjects'),
                'Academic class groups' => $this->count('academic_class_groups'),
                'Academic subject offerings' => $this->count('academic_subject_offerings'),
                'Offering/class-group links' => $this->count('academic_subject_offering_class_groups'),
            ],
            'ganti_go' => [
                'Academic Core replacements' => $this->countWhen(
                    'class_replacements',
                    fn () => DB::table('class_replacements')->whereNotNull('academic_subject_offering_id')->count(),
                    ['academic_subject_offering_id']
                ),
                'Legacy-only replacements' => $this->countWhen(
                    'class_replacements',
                    fn () => DB::table('class_replacements')
                        ->whereNull('academic_subject_offering_id')
                        ->whereNotNull('course_id')
                        ->count(),
                    ['academic_subject_offering_id', 'course_id']
                ),
                'Unlinked replacements' => $this->countWhen(
                    'class_replacements',
                    fn () => DB::table('class_replacements')
                        ->whereNull('academic_subject_offering_id')
                        ->whereNull('course_id')
                        ->count(),
                    ['academic_subject_offering_id', 'course_id']
                ),
            ],
            'subjek_go' => [
                'Academic Core-linked offered subjects' => $this->countWhen(
                    'subjek_go_offered_subjects',
                    fn () => DB::table('subjek_go_offered_subjects')->whereNotNull('academic_subject_offering_id')->count(),
                    ['academic_subject_offering_id']
                ),
                'Legacy-only offered subjects' => $this->countWhen(
                    'subjek_go_offered_subjects',
                    fn () => DB::table('subjek_go_offered_subjects')->whereNull('academic_subject_offering_id')->count(),
                    ['academic_subject_offering_id']
                ),
                'Academic Core-linked subject mirrors' => $this->countWhen(
                    'subjek_go_subject_masters',
                    fn () => DB::table('subjek_go_subject_masters')->whereNotNull('academic_subject_id')->count(),
                    ['academic_subject_id']
                ),
                'Academic Core-linked class-group mirrors' => $this->countWhen(
                    'subjek_go_class_groups',
                    fn () => DB::table('subjek_go_class_groups')->whereNotNull('academic_class_group_id')->count(),
                    ['academic_class_group_id']
                ),
            ],
            'legacy_tables' => $this->tableCounts([
                'semesters',
                'courses',
                'classes',
                'master_courses',
                'master_class_groups',
                'subjek_go_subject_masters',
                'subjek_go_class_groups',
            ]),
            'orphans' => [
                'Offerings without semester' => $this->missingRelationCount(
                    'academic_subject_offerings',
                    'academic_semester_id',
                    'academic_semesters'
                ),
                'Offerings without subject' => $this->missingRelationCount(
                    'academic_subject_offerings',
                    'academic_subject_id',
                    'academic_subjects'
                ),
                'Offering links without offering' => $this->missingRelationCount(
                    'academic_subject_offering_class_groups',
                    'academic_subject_offering_id',
                    'academic_subject_offerings'
                ),
                'Offering links without class group' => $this->missingRelationCount(
                    'academic_subject_offering_class_groups',
                    'academic_class_group_id',
                    'academic_class_groups'
                ),
                'Academic class groups without semester snapshot' => $this->countWhen(
                    'academic_class_groups',
                    fn () => DB::table('academic_class_groups')->whereNull('academic_semester_id')->count(),
                    ['academic_semester_id']
                ),
            ],
        ];
    }

    private function count(string $table): int|string
    {
        return Schema::hasTable($table) ? DB::table($table)->count() : 'missing';
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function countWhen(string $table, callable $callback, array $columns = []): int|string
    {
        if (! Schema::hasTable($table)) {
            return 'missing';
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return 'missing column';
            }
        }

        return $callback();
    }

    /**
     * @param  array<int, string>  $tables
     * @return array<string, int|string>
     */
    private function tableCounts(array $tables): array
    {
        return collect($tables)
            ->mapWithKeys(fn (string $table): array => [$table => $this->count($table)])
            ->all();
    }

    private function missingRelationCount(string $table, string $foreignKey, string $relatedTable): int|string
    {
        if (
            ! Schema::hasTable($table)
            || ! Schema::hasTable($relatedTable)
            || ! Schema::hasColumn($table, $foreignKey)
        ) {
            return 'missing';
        }

        return DB::table($table)
            ->leftJoin($relatedTable, "{$relatedTable}.id", '=', "{$table}.{$foreignKey}")
            ->whereNotNull("{$table}.{$foreignKey}")
            ->whereNull("{$relatedTable}.id")
            ->count();
    }
}
