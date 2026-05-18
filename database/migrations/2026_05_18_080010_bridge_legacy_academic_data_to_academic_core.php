<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_semesters')) {
            return;
        }

        $this->addBridgeColumns();
        $this->backfillAcademicSemesters();
        $this->backfillAcademicSubjects();
        $this->backfillAcademicClassGroups();
        $this->backfillGantiGoLinks();
        $this->backfillSubjekGoLinks();
    }

    public function down(): void
    {
        $this->dropBridgeColumn('subjek_go_offered_subjects', 'academic_subject_offering_id');
        $this->dropBridgeColumn('subjek_go_class_groups', 'academic_class_group_id');
        $this->dropBridgeColumn('subjek_go_subject_masters', 'academic_subject_id');
        $this->dropBridgeColumn('subjek_go_sessions', 'academic_semester_id');
        $this->dropBridgeColumn('courses', 'academic_subject_offering_id');
        $this->dropBridgeColumn('master_class_groups', 'academic_class_group_id');
        $this->dropBridgeColumn('master_courses', 'academic_subject_id');
        $this->dropBridgeColumn('semesters', 'academic_semester_id');
    }

    private function addBridgeColumns(): void
    {
        $this->addNullableForeignKey('semesters', 'academic_semester_id', 'academic_semesters');
        $this->addNullableForeignKey('master_courses', 'academic_subject_id', 'academic_subjects');
        $this->addNullableForeignKey('master_class_groups', 'academic_class_group_id', 'academic_class_groups');
        $this->addNullableForeignKey('courses', 'academic_subject_offering_id', 'academic_subject_offerings');
        $this->addNullableForeignKey('subjek_go_sessions', 'academic_semester_id', 'academic_semesters');
        $this->addNullableForeignKey('subjek_go_subject_masters', 'academic_subject_id', 'academic_subjects');
        $this->addNullableForeignKey('subjek_go_class_groups', 'academic_class_group_id', 'academic_class_groups');
        $this->addNullableForeignKey('subjek_go_offered_subjects', 'academic_subject_offering_id', 'academic_subject_offerings');
    }

    private function addNullableForeignKey(string $table, string $column, string $foreignTable): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $foreignTable): void {
            $blueprint->foreignId($column)->nullable()->constrained($foreignTable)->nullOnDelete();
        });
    }

    private function backfillAcademicSemesters(): void
    {
        $now = now();

        if (Schema::hasTable('semesters')) {
            DB::table('semesters')
                ->orderBy('id')
                ->get()
                ->each(function (object $semester) use ($now): void {
                    $academicSemesterId = DB::table('academic_semesters')
                        ->where('academic_session', $semester->session_code)
                        ->where('name', $semester->name)
                        ->value('id');

                    if (! $academicSemesterId) {
                        $academicSemesterId = DB::table('academic_semesters')->insertGetId([
                            'name' => $semester->name,
                            'academic_session' => $semester->session_code,
                            'start_date' => $semester->start_date,
                            'end_date' => $semester->end_date,
                            'status' => $semester->is_active ? 'active' : 'archived',
                            'is_current' => (bool) $semester->is_active,
                            'auto_activate' => (bool) $semester->auto_activate,
                            'remarks' => $semester->remarks,
                            'created_by' => $semester->created_by,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    DB::table('semesters')
                        ->where('id', $semester->id)
                        ->update(['academic_semester_id' => $academicSemesterId, 'updated_at' => $now]);
                });
        }

        if (Schema::hasTable('subjek_go_sessions')) {
            DB::table('subjek_go_sessions')
                ->whereNull('academic_semester_id')
                ->orderBy('id')
                ->get()
                ->each(function (object $session) use ($now): void {
                    $academicSemesterId = DB::table('academic_semesters')
                        ->where('academic_session', $session->academic_session)
                        ->orderByDesc('id')
                        ->value('id');

                    if (! $academicSemesterId) {
                        $academicSemesterId = DB::table('academic_semesters')->insertGetId([
                            'name' => $session->name,
                            'academic_session' => $session->academic_session,
                            'status' => 'draft',
                            'is_current' => false,
                            'auto_activate' => false,
                            'created_by' => $session->created_by,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    DB::table('subjek_go_sessions')
                        ->where('id', $session->id)
                        ->update(['academic_semester_id' => $academicSemesterId, 'updated_at' => $now]);
                });
        }

        $currentSemesterId = DB::table('academic_semesters')
            ->where('is_current', true)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->value('id');

        if ($currentSemesterId) {
            DB::table('academic_semesters')
                ->where('id', '!=', $currentSemesterId)
                ->update(['is_current' => false, 'updated_at' => $now]);
        }
    }

    private function backfillAcademicSubjects(): void
    {
        $now = now();

        foreach ([
            ['table' => 'master_courses', 'id_column' => 'id'],
            ['table' => 'subjek_go_subject_masters', 'id_column' => 'id'],
        ] as $source) {
            if (! Schema::hasTable($source['table'])) {
                continue;
            }

            DB::table($source['table'])
                ->orderBy($source['id_column'])
                ->get()
                ->each(function (object $subject) use ($source, $now): void {
                    $courseCode = strtoupper(trim((string) $subject->course_code));

                    if ($courseCode === '') {
                        return;
                    }

                    $academicSubjectId = DB::table('academic_subjects')
                        ->where('course_code', $courseCode)
                        ->value('id');

                    if (! $academicSubjectId) {
                        $academicSubjectId = DB::table('academic_subjects')->insertGetId([
                            'course_code' => $courseCode,
                            'course_name' => $subject->course_name,
                            'credit_hour' => $subject->credit_hour ?? null,
                            'weekly_contact_hour' => $subject->weekly_contact_hour ?? null,
                            'remarks' => $subject->remarks ?? null,
                            'is_active' => (bool) ($subject->is_active ?? true),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    if (Schema::hasColumn($source['table'], 'academic_subject_id')) {
                        DB::table($source['table'])
                            ->where('id', $subject->id)
                            ->update(['academic_subject_id' => $academicSubjectId, 'updated_at' => $now]);
                    }
                });
        }
    }

    private function backfillAcademicClassGroups(): void
    {
        $now = now();

        foreach ([
            ['table' => 'master_class_groups', 'name_column' => 'class_group_name'],
            ['table' => 'subjek_go_class_groups', 'name_column' => 'class_name'],
        ] as $source) {
            if (! Schema::hasTable($source['table'])) {
                continue;
            }

            DB::table($source['table'])
                ->orderBy('id')
                ->get()
                ->each(function (object $classGroup) use ($source, $now): void {
                    $className = strtoupper(trim((string) $classGroup->{$source['name_column']}));

                    if ($className === '') {
                        return;
                    }

                    $academicClassGroupId = DB::table('academic_class_groups')
                        ->where('programme_id', $classGroup->programme_id ?? null)
                        ->where('class_name', $className)
                        ->value('id');

                    if (! $academicClassGroupId) {
                        $academicClassGroupId = DB::table('academic_class_groups')->insertGetId([
                            'programme_id' => $classGroup->programme_id ?? null,
                            'class_name' => $className,
                            'cohort' => $classGroup->cohort ?? null,
                            'current_semester' => $classGroup->current_semester ?? null,
                            'is_active' => (bool) ($classGroup->is_active ?? true),
                            'remarks' => $classGroup->remarks ?? null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    if (Schema::hasColumn($source['table'], 'academic_class_group_id')) {
                        DB::table($source['table'])
                            ->where('id', $classGroup->id)
                            ->update(['academic_class_group_id' => $academicClassGroupId, 'updated_at' => $now]);
                    }
                });
        }
    }

    private function backfillGantiGoLinks(): void
    {
        if (
            ! Schema::hasTable('courses')
            || ! Schema::hasTable('semesters')
            || ! Schema::hasTable('master_courses')
        ) {
            return;
        }

        $now = now();

        DB::table('courses as courses')
            ->join('semesters', 'semesters.id', '=', 'courses.semester_id')
            ->leftJoin('master_courses', 'master_courses.id', '=', 'courses.master_course_id')
            ->select([
                'courses.id',
                'courses.programme_id',
                'courses.is_active',
                'semesters.academic_semester_id',
                'master_courses.academic_subject_id',
            ])
            ->whereNotNull('semesters.academic_semester_id')
            ->whereNotNull('master_courses.academic_subject_id')
            ->orderBy('courses.id')
            ->get()
            ->each(function (object $course) use ($now): void {
                $offeringId = $this->firstOrCreateOffering([
                    'academic_semester_id' => $course->academic_semester_id,
                    'academic_subject_id' => $course->academic_subject_id,
                    'programme_id' => $course->programme_id,
                    'is_active' => (bool) $course->is_active,
                ]);

                DB::table('courses')
                    ->where('id', $course->id)
                    ->update(['academic_subject_offering_id' => $offeringId, 'updated_at' => $now]);
            });
    }

    private function backfillSubjekGoLinks(): void
    {
        if (
            ! Schema::hasTable('subjek_go_offered_subjects')
            || ! Schema::hasTable('subjek_go_sessions')
        ) {
            return;
        }

        $now = now();

        DB::table('subjek_go_offered_subjects as offered_subjects')
            ->join('subjek_go_sessions as sessions', 'sessions.id', '=', 'offered_subjects.session_id')
            ->leftJoin('subjek_go_subject_masters as subject_masters', 'subject_masters.id', '=', 'offered_subjects.subject_master_id')
            ->select([
                'offered_subjects.id',
                'offered_subjects.programme_id',
                'offered_subjects.curriculum_version',
                'offered_subjects.offered_semester',
                'offered_subjects.subject_coordinator_user_id',
                'offered_subjects.remarks',
                'offered_subjects.is_active',
                'sessions.academic_semester_id',
                'subject_masters.academic_subject_id',
            ])
            ->whereNotNull('sessions.academic_semester_id')
            ->whereNotNull('subject_masters.academic_subject_id')
            ->orderBy('offered_subjects.id')
            ->get()
            ->each(function (object $offeredSubject) use ($now): void {
                $offeringId = $this->firstOrCreateOffering([
                    'academic_semester_id' => $offeredSubject->academic_semester_id,
                    'academic_subject_id' => $offeredSubject->academic_subject_id,
                    'programme_id' => $offeredSubject->programme_id,
                    'curriculum_version' => $offeredSubject->curriculum_version,
                    'offered_semester' => $offeredSubject->offered_semester,
                    'coordinator_user_id' => $offeredSubject->subject_coordinator_user_id,
                    'remarks' => $offeredSubject->remarks,
                    'is_active' => (bool) $offeredSubject->is_active,
                ]);

                DB::table('subjek_go_offered_subjects')
                    ->where('id', $offeredSubject->id)
                    ->update(['academic_subject_offering_id' => $offeringId, 'updated_at' => $now]);

                if (! Schema::hasTable('subjek_go_subject_class_groups')) {
                    return;
                }

                DB::table('subjek_go_subject_class_groups as pivots')
                    ->join('subjek_go_class_groups as class_groups', 'class_groups.id', '=', 'pivots.class_group_id')
                    ->where('pivots.offered_subject_id', $offeredSubject->id)
                    ->whereNotNull('class_groups.academic_class_group_id')
                    ->pluck('class_groups.academic_class_group_id')
                    ->each(function (int $academicClassGroupId) use ($offeringId, $now): void {
                        DB::table('academic_subject_offering_class_groups')->updateOrInsert(
                            [
                                'academic_subject_offering_id' => $offeringId,
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function firstOrCreateOffering(array $attributes): int
    {
        $query = DB::table('academic_subject_offerings')
            ->where('academic_semester_id', $attributes['academic_semester_id'])
            ->where('academic_subject_id', $attributes['academic_subject_id'])
            ->where(function ($query) use ($attributes): void {
                if ($attributes['programme_id'] ?? null) {
                    $query->where('programme_id', $attributes['programme_id']);

                    return;
                }

                $query->whereNull('programme_id');
            })
            ->where('curriculum_version', $attributes['curriculum_version'] ?? null)
            ->where('offered_semester', $attributes['offered_semester'] ?? null);

        $existingId = $query->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        return (int) DB::table('academic_subject_offerings')->insertGetId([
            'academic_semester_id' => $attributes['academic_semester_id'],
            'academic_subject_id' => $attributes['academic_subject_id'],
            'programme_id' => $attributes['programme_id'] ?? null,
            'curriculum_version' => $attributes['curriculum_version'] ?? null,
            'offered_semester' => $attributes['offered_semester'] ?? null,
            'coordinator_user_id' => $attributes['coordinator_user_id'] ?? null,
            'remarks' => $attributes['remarks'] ?? null,
            'is_active' => $attributes['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function dropBridgeColumn(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column): void {
            $blueprint->dropConstrainedForeignId($column);
        });
    }
};
