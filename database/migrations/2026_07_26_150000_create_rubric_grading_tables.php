<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rubric_grading_rubrics')) {
            return;
        }

        Schema::create('rubric_grading_rubrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('course_code')->nullable();
            $table->string('course_name')->nullable();
            $table->string('assessment_type')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'title'], 'rubric_grading_rubrics_owner_title_index');
            $table->index('course_code', 'rubric_grading_rubrics_course_code_index');
        });

        Schema::create('rubric_grading_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rubric_id')->constrained('rubric_grading_rubrics')->cascadeOnDelete();
            $table->string('label');
            $table->decimal('value', 8, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['rubric_id', 'value'], 'rubric_grading_levels_rubric_value_unique');
            $table->index(['rubric_id', 'sort_order'], 'rubric_grading_levels_order_index');
        });

        Schema::create('rubric_grading_criteria', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rubric_id')->constrained('rubric_grading_rubrics')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('weight', 8, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['rubric_id', 'sort_order'], 'rubric_grading_criteria_order_index');
        });

        Schema::create('rubric_grading_criterion_descriptors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('criterion_id')->constrained('rubric_grading_criteria')->cascadeOnDelete();
            $table->foreignId('level_id')->constrained('rubric_grading_levels')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['criterion_id', 'level_id'], 'rubric_grading_descriptors_cell_unique');
        });

        Schema::create('rubric_grading_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rubric_id')->constrained('rubric_grading_rubrics')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('class_group')->nullable();
            $table->date('assessment_date')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'assessment_date'], 'rubric_grading_sessions_owner_date_index');
            $table->index(['rubric_id', 'assessment_date'], 'rubric_grading_sessions_rubric_date_index');
        });

        Schema::create('rubric_grading_session_students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grading_session_id')->constrained('rubric_grading_sessions')->cascadeOnDelete();
            $table->string('name');
            $table->string('registration_no')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['grading_session_id', 'sort_order'], 'rubric_grading_students_order_index');
            $table->index('registration_no', 'rubric_grading_students_registration_index');
        });

        Schema::create('rubric_grading_student_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_student_id')->constrained('rubric_grading_session_students')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('rubric_grading_criteria')->cascadeOnDelete();
            $table->decimal('level_value', 8, 2);
            $table->timestamps();

            $table->unique(['session_student_id', 'criterion_id'], 'rubric_grading_scores_student_criterion_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubric_grading_student_scores');
        Schema::dropIfExists('rubric_grading_session_students');
        Schema::dropIfExists('rubric_grading_sessions');
        Schema::dropIfExists('rubric_grading_criterion_descriptors');
        Schema::dropIfExists('rubric_grading_criteria');
        Schema::dropIfExists('rubric_grading_levels');
        Schema::dropIfExists('rubric_grading_rubrics');
    }
};
