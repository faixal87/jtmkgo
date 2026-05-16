<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjek_go_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('academic_session', 100);
            $table->text('description')->nullable();
            $table->enum('visibility', ['private', 'public'])->default('private');
            $table->enum('status', ['draft', 'open', 'closed', 'archived'])->default('draft');
            $table->timestamp('open_at')->nullable();
            $table->timestamp('close_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'open_at', 'close_at'], 'subjek_go_sessions_window_index');
            $table->index(['academic_session', 'status'], 'subjek_go_sessions_academic_status_index');
        });

        Schema::create('subjek_go_offered_subjects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->constrained('subjek_go_sessions')->restrictOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained('programmes')->nullOnDelete();
            $table->string('course_code', 50);
            $table->string('course_name');
            $table->string('curriculum_version')->nullable();
            $table->string('offered_semester', 100)->nullable();
            $table->decimal('credit_hour', 5, 2)->nullable();
            $table->decimal('weekly_contact_hour', 5, 2)->nullable();
            $table->unsignedInteger('total_class_groups')->default(1);
            $table->foreignId('subject_coordinator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['session_id', 'is_active'], 'subjek_go_subjects_session_active_index');
            $table->index(['session_id', 'course_code'], 'subjek_go_subjects_session_code_index');
            $table->index(['programme_id', 'is_active'], 'subjek_go_subjects_programme_active_index');
            $table->index(['subject_coordinator_user_id', 'course_code'], 'subjek_go_subjects_coordinator_code_index');
        });

        Schema::create('subjek_go_subject_class_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('offered_subject_id')->constrained('subjek_go_offered_subjects')->restrictOnDelete();
            $table->foreignId('class_group_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('academic_advisor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['offered_subject_id', 'class_group_id'], 'subjek_go_subject_class_unique');
            $table->index(['class_group_id', 'academic_advisor_user_id'], 'subjek_go_subject_class_advisor_index');
        });

        Schema::create('subjek_go_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->constrained('subjek_go_sessions')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('choice_1_subject_id')->constrained('subjek_go_offered_subjects')->restrictOnDelete();
            $table->foreignId('choice_2_subject_id')->constrained('subjek_go_offered_subjects')->restrictOnDelete();
            $table->foreignId('choice_3_subject_id')->constrained('subjek_go_offered_subjects')->restrictOnDelete();
            $table->foreignId('choice_4_subject_id')->constrained('subjek_go_offered_subjects')->restrictOnDelete();
            $table->decimal('total_selected_contact_hour', 6, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->enum('status', ['draft', 'submitted', 'locked'])->default('draft');
            $table->timestamps();

            $table->unique(['session_id', 'user_id'], 'subjek_go_preferences_session_user_unique');
            $table->index(['session_id', 'status'], 'subjek_go_preferences_session_status_index');
            $table->index(['user_id', 'status'], 'subjek_go_preferences_user_status_index');
        });

        Schema::create('subjek_go_teaching_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('offered_subject_id')->nullable()->constrained('subjek_go_offered_subjects')->nullOnDelete();
            $table->string('course_code', 50);
            $table->string('course_name');
            $table->string('academic_session', 100);
            $table->string('semester_name')->nullable();
            $table->string('class_group', 100)->nullable();
            $table->decimal('weekly_contact_hour', 5, 2)->nullable();
            $table->unsignedInteger('taught_duration_months')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'course_code'], 'subjek_go_histories_user_course_index');
            $table->index(['academic_session', 'course_code'], 'subjek_go_histories_session_course_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjek_go_teaching_histories');
        Schema::dropIfExists('subjek_go_preferences');
        Schema::dropIfExists('subjek_go_subject_class_groups');
        Schema::dropIfExists('subjek_go_offered_subjects');
        Schema::dropIfExists('subjek_go_sessions');
    }
};
