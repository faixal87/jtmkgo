<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('class_replacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained('semesters')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->date('original_class_date');
            $table->time('original_start_time');
            $table->time('original_end_time');
            $table->date('replacement_date');
            $table->time('replacement_start_time');
            $table->time('replacement_end_time');
            $table->string('replacement_method', 100);
            $table->text('reason')->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', [
                'pending',
                'implementation_submitted',
                'implemented',
                'implementation_rejected',
                'cancelled',
            ])->default('pending');
            $table->timestamp('implementation_submitted_at')->nullable();
            $table->foreignId('implementation_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('implementation_approved_at')->nullable();
            $table->foreignId('implementation_rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('implementation_rejected_at')->nullable();
            $table->text('implementation_admin_remarks')->nullable();
            $table->timestamps();

            $table->index(['semester_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['replacement_date', 'replacement_start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_replacements');
    }
};
