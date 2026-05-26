<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_go_activities')) {
            return;
        }

        Schema::create('program_go_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('reference_no')->nullable();
            $table->string('activity_name');
            $table->string('activity_code');
            $table->string('activity_code_label')->nullable();
            $table->date('activity_date')->nullable();
            $table->string('venue')->nullable();
            $table->unsignedInteger('participant_count')->nullable();
            $table->enum('speaker_type', ['internal', 'external', 'supplier']);
            $table->decimal('os_21000', 12, 2)->default(0);
            $table->decimal('os_29000', 12, 2)->default(0);
            $table->decimal('os_42000', 12, 2)->default(0);
            $table->decimal('hep_allocation', 12, 2)->default(0);
            $table->decimal('subtotal_os', 12, 2)->default(0);
            $table->decimal('total_budget', 12, 2)->default(0);
            $table->text('paperwork_link')->nullable();
            $table->text('implementation_report_link')->nullable();
            $table->enum('status', ['draft', 'pending_verification', 'approved', 'rejected', 'returned_for_correction'])->default('draft');
            $table->text('admin_remarks')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index('user_id', 'program_go_activities_user_index');
            $table->index('activity_date', 'program_go_activities_activity_date_index');
            $table->index('activity_code', 'program_go_activities_activity_code_index');
            $table->index('status', 'program_go_activities_status_index');
            $table->index('approved_at', 'program_go_activities_approved_at_index');
            $table->index('approved_by', 'program_go_activities_approved_by_index');
            $table->index('speaker_type', 'program_go_activities_speaker_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_go_activities');
    }
};
