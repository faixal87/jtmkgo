<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('class_replacements')) {
            return;
        }

        Schema::table('class_replacements', function (Blueprint $table): void {
            if (! Schema::hasColumn('class_replacements', 'academic_semester_id')) {
                $table->foreignId('academic_semester_id')
                    ->nullable()
                    ->after('semester_id')
                    ->constrained('academic_semesters')
                    ->restrictOnDelete();
            }

            if (! Schema::hasColumn('class_replacements', 'academic_subject_offering_id')) {
                $table->foreignId('academic_subject_offering_id')
                    ->nullable()
                    ->after('course_id')
                    ->constrained('academic_subject_offerings')
                    ->restrictOnDelete();
            }

            if (! Schema::hasColumn('class_replacements', 'academic_subject_id')) {
                $table->foreignId('academic_subject_id')
                    ->nullable()
                    ->after('academic_subject_offering_id')
                    ->constrained('academic_subjects')
                    ->restrictOnDelete();
            }
        });

        Schema::table('class_replacements', function (Blueprint $table): void {
            $table->unsignedBigInteger('semester_id')->nullable()->change();
            $table->unsignedBigInteger('course_id')->nullable()->change();
        });

        if (! Schema::hasTable('ganti_go_replacement_class_groups')) {
            Schema::create('ganti_go_replacement_class_groups', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('class_replacement_id')->constrained('class_replacements')->cascadeOnDelete();
                $table->foreignId('academic_class_group_id')->constrained('academic_class_groups')->restrictOnDelete();
                $table->timestamps();

                $table->unique(
                    ['class_replacement_id', 'academic_class_group_id'],
                    'ganti_go_replacement_academic_class_unique'
                );
                $table->index('academic_class_group_id', 'ganti_go_replacement_academic_class_index');
            });
        }

        $this->backfillAcademicReferences();
    }

    public function down(): void
    {
        Schema::dropIfExists('ganti_go_replacement_class_groups');

        if (! Schema::hasTable('class_replacements')) {
            return;
        }

        Schema::table('class_replacements', function (Blueprint $table): void {
            if (Schema::hasColumn('class_replacements', 'academic_subject_id')) {
                $table->dropConstrainedForeignId('academic_subject_id');
            }

            if (Schema::hasColumn('class_replacements', 'academic_subject_offering_id')) {
                $table->dropConstrainedForeignId('academic_subject_offering_id');
            }

            if (Schema::hasColumn('class_replacements', 'academic_semester_id')) {
                $table->dropConstrainedForeignId('academic_semester_id');
            }
        });

        Schema::table('class_replacements', function (Blueprint $table): void {
            $table->unsignedBigInteger('semester_id')->nullable(false)->change();
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
        });
    }

    private function backfillAcademicReferences(): void
    {
        if (
            ! Schema::hasTable('semesters')
            || ! Schema::hasTable('courses')
            || ! Schema::hasTable('class_replacement_classes')
            || ! Schema::hasTable('classes')
            || ! Schema::hasTable('master_class_groups')
        ) {
            return;
        }

        $now = now();

        DB::table('class_replacements as replacements')
            ->leftJoin('semesters', 'semesters.id', '=', 'replacements.semester_id')
            ->leftJoin('courses', 'courses.id', '=', 'replacements.course_id')
            ->leftJoin('academic_subject_offerings', 'academic_subject_offerings.id', '=', 'courses.academic_subject_offering_id')
            ->select([
                'replacements.id',
                'semesters.academic_semester_id',
                'courses.academic_subject_offering_id',
                'academic_subject_offerings.academic_subject_id',
            ])
            ->whereNull('replacements.academic_semester_id')
            ->orderBy('replacements.id')
            ->get()
            ->each(function (object $replacement) use ($now): void {
                DB::table('class_replacements')
                    ->where('id', $replacement->id)
                    ->update([
                        'academic_semester_id' => $replacement->academic_semester_id,
                        'academic_subject_offering_id' => $replacement->academic_subject_offering_id,
                        'academic_subject_id' => $replacement->academic_subject_id,
                        'updated_at' => $now,
                    ]);

                DB::table('class_replacement_classes as pivots')
                    ->join('classes', 'classes.id', '=', 'pivots.class_id')
                    ->join('master_class_groups', 'master_class_groups.id', '=', 'classes.master_class_group_id')
                    ->where('pivots.class_replacement_id', $replacement->id)
                    ->whereNotNull('master_class_groups.academic_class_group_id')
                    ->pluck('master_class_groups.academic_class_group_id')
                    ->each(function (int $academicClassGroupId) use ($replacement, $now): void {
                        DB::table('ganti_go_replacement_class_groups')->updateOrInsert(
                            [
                                'class_replacement_id' => $replacement->id,
                                'academic_class_group_id' => $academicClassGroupId,
                            ],
                            [
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]
                        );
                    });
            });
    }
};
