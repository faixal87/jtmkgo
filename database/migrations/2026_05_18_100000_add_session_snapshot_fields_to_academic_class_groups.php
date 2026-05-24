<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_class_groups')) {
            return;
        }

        Schema::table('academic_class_groups', function (Blueprint $table): void {
            if (! Schema::hasColumn('academic_class_groups', 'academic_semester_id')) {
                $table->foreignId('academic_semester_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('academic_semesters')
                    ->restrictOnDelete();
            }

            if (! Schema::hasColumn('academic_class_groups', 'academic_advisor_user_id')) {
                $table->foreignId('academic_advisor_user_id')
                    ->nullable()
                    ->after('current_semester')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        $this->backfillUnambiguousSemesterLinks();
        $this->addIndexIfMissing('academic_class_groups', ['academic_semester_id'], 'academic_class_groups_semester_index');
        $this->addIndexIfMissing('academic_class_groups', ['academic_advisor_user_id'], 'academic_class_groups_advisor_index');
        $this->addIndexIfMissing(
            'academic_class_groups',
            ['academic_semester_id', 'programme_id', 'class_name'],
            'academic_class_groups_semester_programme_name_index'
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('academic_class_groups')) {
            return;
        }

        $this->dropIndexIfExists('academic_class_groups', 'academic_class_groups_semester_programme_name_index');
        $this->dropIndexIfExists('academic_class_groups', 'academic_class_groups_advisor_index');
        $this->dropIndexIfExists('academic_class_groups', 'academic_class_groups_semester_index');

        Schema::table('academic_class_groups', function (Blueprint $table): void {
            if (Schema::hasColumn('academic_class_groups', 'academic_advisor_user_id')) {
                $table->dropConstrainedForeignId('academic_advisor_user_id');
            }

            if (Schema::hasColumn('academic_class_groups', 'academic_semester_id')) {
                $table->dropConstrainedForeignId('academic_semester_id');
            }
        });
    }

    private function backfillUnambiguousSemesterLinks(): void
    {
        if (
            ! Schema::hasTable('academic_subject_offering_class_groups')
            || ! Schema::hasTable('academic_subject_offerings')
            || ! Schema::hasColumn('academic_class_groups', 'academic_semester_id')
        ) {
            return;
        }

        DB::table('academic_class_groups')
            ->whereNull('academic_semester_id')
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $classGroupId): void {
                $semesterIds = DB::table('academic_subject_offering_class_groups as pivots')
                    ->join(
                        'academic_subject_offerings as offerings',
                        'offerings.id',
                        '=',
                        'pivots.academic_subject_offering_id'
                    )
                    ->where('pivots.academic_class_group_id', $classGroupId)
                    ->distinct()
                    ->pluck('offerings.academic_semester_id');

                if ($semesterIds->count() !== 1) {
                    return;
                }

                DB::table('academic_class_groups')
                    ->where('id', $classGroupId)
                    ->update([
                        'academic_semester_id' => $semesterIds->first(),
                        'updated_at' => now(),
                    ]);
            });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function addIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName): void {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
            $blueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
