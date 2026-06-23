<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_go_activity_collaborators')) {
            return;
        }

        Schema::create('program_go_activity_collaborators', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_activity_id')->constrained('program_go_activities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('co_author');
            $table->boolean('can_edit')->default(true);
            $table->boolean('can_submit')->default(false);
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['program_activity_id', 'user_id'], 'program_go_activity_collaborator_unique');
            $table->index('program_activity_id', 'program_go_activity_collaborators_activity_index');
            $table->index('user_id', 'program_go_activity_collaborators_user_index');
            $table->index('can_edit', 'program_go_activity_collaborators_can_edit_index');
            $table->index('can_submit', 'program_go_activity_collaborators_can_submit_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_go_activity_collaborators');
    }
};
