<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code', 50);
            $table->string('course_name');
            $table->foreignId('programme_id')->nullable()->constrained('programmes')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['course_code', 'programme_id'], 'master_courses_code_programme_unique');
            $table->index(['programme_id', 'is_active']);
        });

        Schema::create('semester_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained('semesters')->restrictOnDelete();
            $table->foreignId('master_course_id')->constrained('master_courses')->restrictOnDelete();
            $table->boolean('is_offered')->default(true);
            $table->timestamps();

            $table->unique(['semester_id', 'master_course_id'], 'semester_courses_semester_master_unique');
            $table->index(['semester_id', 'is_offered']);
        });

        Schema::create('master_class_groups', function (Blueprint $table) {
            $table->id();
            $table->string('class_group_name', 100);
            $table->foreignId('programme_id')->constrained('programmes')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['programme_id', 'class_group_name'], 'master_class_groups_programme_name_unique');
            $table->index(['programme_id', 'is_active']);
        });

        Schema::create('semester_class_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained('semesters')->restrictOnDelete();
            $table->foreignId('master_class_group_id')->constrained('master_class_groups')->restrictOnDelete();
            $table->boolean('is_offered')->default(true);
            $table->timestamps();

            $table->unique(['semester_id', 'master_class_group_id'], 'semester_class_groups_semester_master_unique');
            $table->index(['semester_id', 'is_offered']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('master_course_id')
                ->nullable()
                ->after('id')
                ->constrained('master_courses')
                ->nullOnDelete();

            $table->index(['semester_id', 'master_course_id']);
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('master_class_group_id')
                ->nullable()
                ->after('id')
                ->constrained('master_class_groups')
                ->nullOnDelete();

            $table->index(['semester_id', 'master_class_group_id']);
        });

        $this->backfillCourses();
        $this->backfillClassGroups();
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropIndex(['semester_id', 'master_class_group_id']);
            $table->dropConstrainedForeignId('master_class_group_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['semester_id', 'master_course_id']);
            $table->dropConstrainedForeignId('master_course_id');
        });

        Schema::dropIfExists('semester_class_groups');
        Schema::dropIfExists('master_class_groups');
        Schema::dropIfExists('semester_courses');
        Schema::dropIfExists('master_courses');
    }

    private function backfillCourses(): void
    {
        $now = now();

        DB::table('courses')
            ->orderBy('id')
            ->get()
            ->each(function (object $course) use ($now): void {
                $courseCode = strtoupper(trim((string) $course->course_code));
                $programmeId = $course->programme_id ?: null;

                $masterCourseId = DB::table('master_courses')
                    ->where('course_code', $courseCode)
                    ->where('programme_id', $programmeId)
                    ->value('id');

                if (! $masterCourseId) {
                    $masterCourseId = DB::table('master_courses')->insertGetId([
                        'course_code' => $courseCode,
                        'course_name' => $course->course_name,
                        'programme_id' => $programmeId,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('courses')
                    ->where('id', $course->id)
                    ->update([
                        'master_course_id' => $masterCourseId,
                        'course_code' => $courseCode,
                        'updated_at' => $now,
                    ]);

                DB::table('semester_courses')->updateOrInsert(
                    [
                        'semester_id' => $course->semester_id,
                        'master_course_id' => $masterCourseId,
                    ],
                    [
                        'is_offered' => (bool) $course->is_active,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            });
    }

    private function backfillClassGroups(): void
    {
        $now = now();

        DB::table('classes')
            ->orderBy('id')
            ->get()
            ->each(function (object $classGroup) use ($now): void {
                $classGroupName = strtoupper(trim((string) $classGroup->class_name));

                $masterClassGroupId = DB::table('master_class_groups')
                    ->where('programme_id', $classGroup->programme_id)
                    ->where('class_group_name', $classGroupName)
                    ->value('id');

                if (! $masterClassGroupId) {
                    $masterClassGroupId = DB::table('master_class_groups')->insertGetId([
                        'programme_id' => $classGroup->programme_id,
                        'class_group_name' => $classGroupName,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('classes')
                    ->where('id', $classGroup->id)
                    ->update([
                        'master_class_group_id' => $masterClassGroupId,
                        'class_name' => $classGroupName,
                        'updated_at' => $now,
                    ]);

                DB::table('semester_class_groups')->updateOrInsert(
                    [
                        'semester_id' => $classGroup->semester_id,
                        'master_class_group_id' => $masterClassGroupId,
                    ],
                    [
                        'is_offered' => (bool) $classGroup->is_active,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            });
    }
};
