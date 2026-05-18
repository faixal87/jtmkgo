<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('subjek_go_offered_subjects')
            || ! Schema::hasTable('subjek_go_subject_masters')
        ) {
            return;
        }

        if (! Schema::hasColumn('subjek_go_offered_subjects', 'subject_master_id')) {
            Schema::table('subjek_go_offered_subjects', function (Blueprint $table): void {
                $table->foreignId('subject_master_id')
                    ->nullable()
                    ->after('programme_id')
                    ->constrained('subjek_go_subject_masters')
                    ->restrictOnDelete();
            });
        }

        $this->makeLegacyOfferingColumnsNullable();
        $this->backfillSubjectMasters();
        $this->backfillOfferingSubjectMasters();

        if (
            Schema::hasColumn('subjek_go_offered_subjects', 'subject_master_id')
            && ! $this->hasForeignKey('subjek_go_offered_subjects', 'subjek_go_offered_subjects_subject_master_id_foreign')
        ) {
            Schema::table('subjek_go_offered_subjects', function (Blueprint $table): void {
                $table->foreign('subject_master_id')
                    ->references('id')
                    ->on('subjek_go_subject_masters')
                    ->restrictOnDelete();
            });
        }

        if (! Schema::hasIndex('subjek_go_offered_subjects', 'subjek_go_offerings_session_master_index')) {
            Schema::table('subjek_go_offered_subjects', function (Blueprint $table): void {
                $table->index(['session_id', 'subject_master_id'], 'subjek_go_offerings_session_master_index');
            });
        }

        if (
            Schema::hasColumn('subjek_go_offered_subjects', 'subject_master_id')
            && DB::table('subjek_go_offered_subjects')->whereNull('subject_master_id')->doesntExist()
        ) {
            DB::statement('ALTER TABLE subjek_go_offered_subjects MODIFY subject_master_id BIGINT UNSIGNED NOT NULL');
            $this->dropLegacyOfferingColumns();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('subjek_go_offered_subjects')) {
            return;
        }

        Schema::table('subjek_go_offered_subjects', function (Blueprint $table): void {
            if (! Schema::hasColumn('subjek_go_offered_subjects', 'course_code')) {
                $table->string('course_code', 50)->nullable()->after('subject_master_id');
            }

            if (! Schema::hasColumn('subjek_go_offered_subjects', 'course_name')) {
                $table->string('course_name')->nullable()->after('course_code');
            }

            if (! Schema::hasColumn('subjek_go_offered_subjects', 'credit_hour')) {
                $table->decimal('credit_hour', 5, 2)->nullable()->after('offered_semester');
            }

            if (! Schema::hasColumn('subjek_go_offered_subjects', 'weekly_contact_hour')) {
                $table->decimal('weekly_contact_hour', 5, 2)->nullable()->after('credit_hour');
            }

            if (! Schema::hasColumn('subjek_go_offered_subjects', 'total_class_groups')) {
                $table->unsignedInteger('total_class_groups')->default(1)->after('weekly_contact_hour');
            }
        });

        if (
            Schema::hasColumn('subjek_go_offered_subjects', 'subject_master_id')
            && Schema::hasTable('subjek_go_subject_masters')
        ) {
            DB::table('subjek_go_offered_subjects as offerings')
                ->join('subjek_go_subject_masters as masters', 'masters.id', '=', 'offerings.subject_master_id')
                ->select([
                    'offerings.id',
                    'masters.course_code',
                    'masters.course_name',
                    'masters.credit_hour',
                    'masters.weekly_contact_hour',
                ])
                ->get()
                ->each(function (object $offering): void {
                    DB::table('subjek_go_offered_subjects')
                        ->where('id', $offering->id)
                        ->update([
                            'course_code' => $offering->course_code,
                            'course_name' => $offering->course_name,
                            'credit_hour' => $offering->credit_hour,
                            'weekly_contact_hour' => $offering->weekly_contact_hour,
                            'total_class_groups' => DB::table('subjek_go_subject_class_groups')
                                ->where('offered_subject_id', $offering->id)
                                ->count(),
                        ]);
                });
        }
    }

    private function makeLegacyOfferingColumnsNullable(): void
    {
        $nullableColumns = [
            'course_code' => 'VARCHAR(50) NULL',
            'course_name' => 'VARCHAR(255) NULL',
            'credit_hour' => 'DECIMAL(5, 2) NULL',
            'weekly_contact_hour' => 'DECIMAL(5, 2) NULL',
        ];

        foreach ($nullableColumns as $column => $definition) {
            if (Schema::hasColumn('subjek_go_offered_subjects', $column)) {
                DB::statement("ALTER TABLE subjek_go_offered_subjects MODIFY {$column} {$definition}");
            }
        }
    }

    private function backfillSubjectMasters(): void
    {
        if (! Schema::hasColumn('subjek_go_offered_subjects', 'course_code')) {
            return;
        }

        $now = now();

        DB::table('subjek_go_offered_subjects')
            ->select([
                'course_code',
                'course_name',
                'credit_hour',
                'weekly_contact_hour',
            ])
            ->whereNotNull('course_code')
            ->orderBy('id')
            ->get()
            ->each(function (object $subject) use ($now): void {
                $courseCode = strtoupper(trim((string) $subject->course_code));

                if ($courseCode === '') {
                    return;
                }

                DB::table('subjek_go_subject_masters')->updateOrInsert(
                    ['course_code' => $courseCode],
                    [
                        'course_name' => $subject->course_name,
                        'credit_hour' => $subject->credit_hour,
                        'weekly_contact_hour' => $subject->weekly_contact_hour,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            });
    }

    private function backfillOfferingSubjectMasters(): void
    {
        if (
            ! Schema::hasColumn('subjek_go_offered_subjects', 'subject_master_id')
            || ! Schema::hasColumn('subjek_go_offered_subjects', 'course_code')
        ) {
            return;
        }

        DB::table('subjek_go_offered_subjects')
            ->select(['id', 'course_code'])
            ->whereNull('subject_master_id')
            ->whereNotNull('course_code')
            ->get()
            ->each(function (object $offering): void {
                $masterId = DB::table('subjek_go_subject_masters')
                    ->where('course_code', strtoupper(trim((string) $offering->course_code)))
                    ->value('id');

                if ($masterId) {
                    DB::table('subjek_go_offered_subjects')
                        ->where('id', $offering->id)
                        ->update(['subject_master_id' => $masterId]);
                }
            });
    }

    private function dropLegacyOfferingColumns(): void
    {
        $legacyOfferingColumns = array_values(array_filter([
            'course_code',
            'course_name',
            'credit_hour',
            'weekly_contact_hour',
            'total_class_groups',
        ], fn (string $column): bool => Schema::hasColumn('subjek_go_offered_subjects', $column)));

        if ($legacyOfferingColumns === []) {
            return;
        }

        $this->ensureCoordinatorForeignKeyIndex();

        Schema::table('subjek_go_offered_subjects', function (Blueprint $table) use ($legacyOfferingColumns): void {
            if (Schema::hasIndex('subjek_go_offered_subjects', 'subjek_go_subjects_session_code_index')) {
                $table->dropIndex('subjek_go_subjects_session_code_index');
            }

            if (Schema::hasIndex('subjek_go_offered_subjects', 'subjek_go_subjects_coordinator_code_index')) {
                $table->dropIndex('subjek_go_subjects_coordinator_code_index');
            }

            $table->dropColumn($legacyOfferingColumns);
        });
    }

    /**
     * Keep the coordinator FK indexed after the legacy course-code composite index is removed.
     */
    private function ensureCoordinatorForeignKeyIndex(): void
    {
        if (
            ! Schema::hasColumn('subjek_go_offered_subjects', 'subject_coordinator_user_id')
            || Schema::hasIndex('subjek_go_offered_subjects', 'subjek_go_subjects_coordinator_index')
        ) {
            return;
        }

        Schema::table('subjek_go_offered_subjects', function (Blueprint $table): void {
            $table->index('subject_coordinator_user_id', 'subjek_go_subjects_coordinator_index');
        });
    }

    private function hasForeignKey(string $table, string $foreignKey): bool
    {
        return collect(Schema::getForeignKeys($table))
            ->contains(fn (array $definition): bool => $definition['name'] === $foreignKey);
    }
};
