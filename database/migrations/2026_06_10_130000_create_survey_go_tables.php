<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('survey_go_surveys')) {
            Schema::create('survey_go_surveys', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->enum('type', ['baseline', 'impact', 'module_specific'])->index();
                $table->enum('status', ['draft', 'active', 'closed', 'archived'])->default('draft')->index();
                $table->boolean('is_forced')->default(false)->index();
                $table->boolean('is_notification_sent')->default(false);
                $table->timestamp('launched_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['type', 'status', 'is_forced'], 'survey_go_surveys_type_status_forced_index');
            });
        }

        if (! Schema::hasTable('survey_go_questions')) {
            Schema::create('survey_go_questions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('survey_id')->constrained('survey_go_surveys')->cascadeOnDelete();
                $table->text('question_text');
                $table->enum('question_type', ['likert', 'yes_no', 'short_text']);
                $table->string('domain')->nullable()->index();
                $table->boolean('is_required')->default(true);
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();

                $table->index(['survey_id', 'is_active', 'sort_order'], 'survey_go_questions_survey_active_sort_index');
            });
        }

        if (! Schema::hasTable('survey_go_responses')) {
            Schema::create('survey_go_responses', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('survey_id')->constrained('survey_go_surveys')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('submitted_at')->nullable()->index();
                $table->timestamps();

                $table->unique(['survey_id', 'user_id'], 'survey_go_responses_survey_user_unique');
                $table->index(['survey_id', 'submitted_at'], 'survey_go_responses_survey_submitted_index');
                $table->index(['user_id', 'submitted_at'], 'survey_go_responses_user_submitted_index');
            });
        }

        if (! Schema::hasTable('survey_go_answers')) {
            Schema::create('survey_go_answers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('response_id')->constrained('survey_go_responses')->cascadeOnDelete();
                $table->foreignId('question_id')->constrained('survey_go_questions')->cascadeOnDelete();
                $table->string('answer_value')->nullable();
                $table->text('answer_text')->nullable();
                $table->timestamps();

                $table->unique(['response_id', 'question_id'], 'survey_go_answers_response_question_unique');
                $table->index('question_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_go_answers');
        Schema::dropIfExists('survey_go_responses');
        Schema::dropIfExists('survey_go_questions');
        Schema::dropIfExists('survey_go_surveys');
    }
};
