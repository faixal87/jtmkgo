<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subjek_go_subject_masters')) {
            Schema::create('subjek_go_subject_masters', function (Blueprint $table): void {
                $table->id();
                $table->string('course_code', 50)->unique();
                $table->string('course_name');
                $table->decimal('credit_hour', 5, 2)->nullable();
                $table->decimal('weekly_contact_hour', 5, 2)->nullable();
                $table->text('remarks')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'course_code'], 'subjek_go_subject_masters_active_code_index');
            });
        }

        if (! Schema::hasTable('subjek_go_class_groups')) {
            Schema::create('subjek_go_class_groups', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('programme_id')->nullable()->constrained('programmes')->nullOnDelete();
                $table->string('class_name', 100);
                $table->string('cohort', 100)->nullable();
                $table->string('current_semester', 100)->nullable();
                $table->text('remarks')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['programme_id', 'is_active'], 'subjek_go_class_groups_programme_active_index');
                $table->index(['class_name', 'is_active'], 'subjek_go_class_groups_name_active_index');
            });
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

        if (! Schema::hasIndex('subjek_go_offered_subjects', 'subjek_go_offerings_session_master_index')) {
            Schema::table('subjek_go_offered_subjects', function (Blueprint $table): void {
                $table->index(['session_id', 'subject_master_id'], 'subjek_go_offerings_session_master_index');
            });
        }

        if (! $this->hasForeignKey('subjek_go_offered_subjects', 'subjek_go_offered_subjects_subject_master_id_foreign')) {
            Schema::table('subjek_go_offered_subjects', function (Blueprint $table): void {
                $table->foreign('subject_master_id')
                    ->references('id')
                    ->on('subjek_go_subject_masters')
                    ->restrictOnDelete();
            });
        }

        $this->makeLegacyOfferingColumnsNullable();

        $now = now();

        if (Schema::hasColumn('subjek_go_offered_subjects', 'course_code')) {
            DB::table('subjek_go_offered_subjects')
                ->select([
                    'course_code',
                    'course_name',
                    'credit_hour',
                    'weekly_contact_hour',
                ])
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

            DB::table('subjek_go_offered_subjects')
                ->select(['id', 'course_code'])
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

        if (
            Schema::hasColumn('subjek_go_subject_class_groups', 'class_group_id')
            && Schema::hasColumn('subjek_go_subject_class_groups', 'academic_advisor_user_id')
        ) {
            if (! Schema::hasColumn('subjek_go_subject_class_groups', 'new_class_group_id')) {
                Schema::table('subjek_go_subject_class_groups', function (Blueprint $table): void {
                    $table->unsignedBigInteger('new_class_group_id')->nullable()->after('class_group_id');
                });
            }

            DB::table('subjek_go_subject_class_groups as pivot')
                ->join('classes', 'classes.id', '=', 'pivot.class_group_id')
                ->select([
                    'pivot.id as pivot_id',
                    'classes.programme_id',
                    'classes.class_name',
                ])
                ->orderBy('pivot.id')
                ->get()
                ->each(function (object $row) use ($now): void {
                    $className = strtoupper(trim((string) $row->class_name));

                    $existingId = DB::table('subjek_go_class_groups')
                        ->where('programme_id', $row->programme_id)
                        ->where('class_name', $className)
                        ->whereNull('cohort')
                        ->value('id');

                    if (! $existingId) {
                        $existingId = DB::table('subjek_go_class_groups')->insertGetId([
                            'programme_id' => $row->programme_id,
                            'class_name' => $className,
                            'cohort' => null,
                            'current_semester' => null,
                            'remarks' => null,
                            'is_active' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    DB::table('subjek_go_subject_class_groups')
                        ->where('id', $row->pivot_id)
                        ->update(['new_class_group_id' => $existingId]);
                });

            if (! Schema::hasIndex('subjek_go_subject_class_groups', 'subjek_go_subject_class_offered_index')) {
                Schema::table('subjek_go_subject_class_groups', function (Blueprint $table): void {
                    $table->index('offered_subject_id', 'subjek_go_subject_class_offered_index');
                });
            }

            Schema::table('subjek_go_subject_class_groups', function (Blueprint $table): void {
                if (Schema::hasIndex('subjek_go_subject_class_groups', 'subjek_go_subject_class_unique')) {
                    $table->dropUnique('subjek_go_subject_class_unique');
                }

                if (Schema::hasIndex('subjek_go_subject_class_groups', 'subjek_go_subject_class_advisor_index')) {
                    $table->dropIndex('subjek_go_subject_class_advisor_index');
                }

                if ($this->hasForeignKey('subjek_go_subject_class_groups', 'subjek_go_subject_class_groups_class_group_id_foreign')) {
                    $table->dropForeign(['class_group_id']);
                }

                if ($this->hasForeignKey('subjek_go_subject_class_groups', 'subjek_go_subject_class_groups_academic_advisor_user_id_foreign')) {
                    $table->dropForeign(['academic_advisor_user_id']);
                }

                $table->dropColumn(['class_group_id', 'academic_advisor_user_id']);
            });

            Schema::table('subjek_go_subject_class_groups', function (Blueprint $table): void {
                $table->renameColumn('new_class_group_id', 'class_group_id');
            });

            DB::statement('ALTER TABLE subjek_go_subject_class_groups MODIFY class_group_id BIGINT UNSIGNED NOT NULL');

            DB::table('subjek_go_subject_class_groups')
                ->selectRaw('MIN(id) as keep_id, offered_subject_id, class_group_id')
                ->groupBy('offered_subject_id', 'class_group_id')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->each(function (object $duplicate): void {
                    DB::table('subjek_go_subject_class_groups')
                        ->where('offered_subject_id', $duplicate->offered_subject_id)
                        ->where('class_group_id', $duplicate->class_group_id)
                        ->where('id', '!=', $duplicate->keep_id)
                        ->delete();
                });

            $this->ensureConvertedClassGroupConstraints();
        }

        if (
            Schema::hasColumn('subjek_go_subject_class_groups', 'class_group_id')
            && ! Schema::hasColumn('subjek_go_subject_class_groups', 'academic_advisor_user_id')
        ) {
            $this->ensureConvertedClassGroupConstraints();
        }

        if (Schema::hasColumn('subjek_go_offered_subjects', 'subject_master_id')) {
            DB::statement('ALTER TABLE subjek_go_offered_subjects MODIFY subject_master_id BIGINT UNSIGNED NOT NULL');
        }

        $legacyOfferingColumns = array_values(array_filter([
            'course_code',
            'course_name',
            'credit_hour',
            'weekly_contact_hour',
            'total_class_groups',
        ], fn (string $column): bool => Schema::hasColumn('subjek_go_offered_subjects', $column)));

        if ($legacyOfferingColumns !== []) {
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
    }

    public function down(): void
    {
        Schema::table('subjek_go_offered_subjects', function (Blueprint $table): void {
            $table->string('course_code', 50)->nullable()->after('programme_id');
            $table->string('course_name')->nullable()->after('course_code');
            $table->decimal('credit_hour', 5, 2)->nullable()->after('offered_semester');
            $table->decimal('weekly_contact_hour', 5, 2)->nullable()->after('credit_hour');
            $table->unsignedInteger('total_class_groups')->default(1)->after('weekly_contact_hour');
        });

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

        Schema::table('subjek_go_offered_subjects', function (Blueprint $table): void {
            $table->dropForeign(['subject_master_id']);
            $table->dropIndex('subjek_go_offerings_session_master_index');
            $table->dropColumn('subject_master_id');
            $table->index(['session_id', 'course_code'], 'subjek_go_subjects_session_code_index');
            $table->index(['subject_coordinator_user_id', 'course_code'], 'subjek_go_subjects_coordinator_code_index');
        });

        // Existing Ganti Go class IDs cannot be reconstructed safely from the new reusable catalogue,
        // so rollback preserves the pivot rows without guessing historical mappings.
        Schema::table('subjek_go_subject_class_groups', function (Blueprint $table): void {
            $table->dropForeign(['class_group_id']);
        });

        Schema::dropIfExists('subjek_go_class_groups');
        Schema::dropIfExists('subjek_go_subject_masters');
    }

    private function ensureConvertedClassGroupConstraints(): void
    {
        Schema::table('subjek_go_subject_class_groups', function (Blueprint $table): void {
            if (! $this->hasForeignKey('subjek_go_subject_class_groups', 'subjek_go_subject_class_groups_class_group_id_foreign')) {
                $table->foreign('class_group_id')->references('id')->on('subjek_go_class_groups')->restrictOnDelete();
            }

            if (! Schema::hasIndex('subjek_go_subject_class_groups', 'subjek_go_subject_class_unique')) {
                $table->unique(['offered_subject_id', 'class_group_id'], 'subjek_go_subject_class_unique');
            }

            if (! Schema::hasIndex('subjek_go_subject_class_groups', 'subjek_go_subject_class_group_index')) {
                $table->index('class_group_id', 'subjek_go_subject_class_group_index');
            }
        });
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

    private function hasForeignKey(string $table, string $foreignKey): bool
    {
        return collect(Schema::getForeignKeys($table))
            ->contains(fn (array $definition): bool => $definition['name'] === $foreignKey);
    }
};
