<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subjek_go_teaching_experiences')) {
            return;
        }

        Schema::create('subjek_go_teaching_experiences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('academic_subject_id')->constrained('academic_subjects')->restrictOnDelete();
            $table->decimal('experience_years', 5, 2)->default(0);
            $table->enum('experience_level', ['beginner', 'familiar', 'experienced', 'expert'])->nullable();
            $table->string('last_taught_session', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'academic_subject_id'], 'subjek_go_experience_user_subject_unique');
            $table->index(['academic_subject_id', 'experience_years'], 'subjek_go_experience_subject_years_index');
            $table->index(['user_id', 'experience_level'], 'subjek_go_experience_user_level_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjek_go_teaching_experiences');
    }
};
