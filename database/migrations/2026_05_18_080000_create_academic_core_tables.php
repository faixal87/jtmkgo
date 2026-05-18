<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_semesters')) {
            Schema::create('academic_semesters', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('academic_session', 100);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
                $table->boolean('is_current')->default(false);
                $table->boolean('auto_activate')->default(false);
                $table->text('remarks')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('status', 'academic_semesters_status_index');
                $table->index('is_current', 'academic_semesters_current_index');
                $table->index(['auto_activate', 'start_date', 'end_date'], 'academic_semesters_activation_index');
                $table->index(['academic_session', 'status'], 'academic_semesters_session_status_index');
            });
        }

        if (! Schema::hasTable('academic_subjects')) {
            Schema::create('academic_subjects', function (Blueprint $table): void {
                $table->id();
                $table->string('course_code', 50)->unique();
                $table->string('course_name');
                $table->decimal('credit_hour', 5, 2)->nullable();
                $table->decimal('weekly_contact_hour', 5, 2)->nullable();
                $table->text('remarks')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'course_code'], 'academic_subjects_active_code_index');
            });
        }

        if (! Schema::hasTable('academic_class_groups')) {
            Schema::create('academic_class_groups', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('programme_id')->nullable()->constrained('programmes')->nullOnDelete();
                $table->string('class_name', 100);
                $table->string('cohort', 100)->nullable();
                $table->string('current_semester', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index(['programme_id', 'is_active'], 'academic_class_groups_programme_active_index');
                $table->index(['class_name', 'is_active'], 'academic_class_groups_name_active_index');
            });
        }

        if (! Schema::hasTable('academic_subject_offerings')) {
            Schema::create('academic_subject_offerings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('academic_semester_id')->constrained('academic_semesters')->restrictOnDelete();
                $table->foreignId('academic_subject_id')->constrained('academic_subjects')->restrictOnDelete();
                $table->foreignId('programme_id')->nullable()->constrained('programmes')->nullOnDelete();
                $table->string('curriculum_version')->nullable();
                $table->string('offered_semester', 100)->nullable();
                $table->foreignId('coordinator_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('remarks')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('academic_semester_id', 'academic_offerings_semester_index');
                $table->index('academic_subject_id', 'academic_offerings_subject_index');
                $table->index('programme_id', 'academic_offerings_programme_index');
                $table->index('is_active', 'academic_offerings_active_index');
                $table->index(
                    ['academic_semester_id', 'academic_subject_id', 'programme_id'],
                    'academic_offerings_semester_subject_programme_index'
                );
            });
        }

        if (! Schema::hasTable('academic_subject_offering_class_groups')) {
            Schema::create('academic_subject_offering_class_groups', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('academic_subject_offering_id')->constrained('academic_subject_offerings')->cascadeOnDelete();
                $table->foreignId('academic_class_group_id')->constrained('academic_class_groups')->restrictOnDelete();
                $table->timestamps();

                $table->unique(
                    ['academic_subject_offering_id', 'academic_class_group_id'],
                    'academic_offering_class_unique'
                );
                $table->index('academic_subject_offering_id', 'academic_offering_class_offering_index');
                $table->index('academic_class_group_id', 'academic_offering_class_group_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_subject_offering_class_groups');
        Schema::dropIfExists('academic_subject_offerings');
        Schema::dropIfExists('academic_class_groups');
        Schema::dropIfExists('academic_subjects');
        Schema::dropIfExists('academic_semesters');
    }
};
