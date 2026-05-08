<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_replacements', function (Blueprint $table) {
            $table->foreignId('programme_id')
                ->nullable()
                ->after('course_id')
                ->constrained('programmes')
                ->nullOnDelete();
            $table->boolean('already_implemented')->default(false)->after('programme_id');
            $table->string('original_venue')->nullable()->after('original_end_time');
            $table->unsignedInteger('original_duration_minutes')->nullable()->after('original_venue');
            $table->unsignedInteger('replacement_duration_minutes')->nullable()->after('replacement_end_time');
            $table->string('replacement_venue')->nullable()->after('replacement_duration_minutes');
            $table->string('evidence_path')->nullable()->after('implementation_admin_remarks');
            $table->string('evidence_original_name')->nullable()->after('evidence_path');
            $table->timestamp('evidence_uploaded_at')->nullable()->after('evidence_original_name');
        });

        DB::statement("ALTER TABLE class_replacements MODIFY status ENUM('pending','implementation_submitted','implemented','implementation_rejected','planned','pending_verification','verified','rejected','cancelled','overdue') DEFAULT 'planned'");

        DB::table('class_replacements')->where('status', 'pending')->update(['status' => 'planned']);
        DB::table('class_replacements')->where('status', 'implementation_submitted')->update(['status' => 'pending_verification']);
        DB::table('class_replacements')->where('status', 'implemented')->update(['status' => 'verified']);
        DB::table('class_replacements')->where('status', 'implementation_rejected')->update(['status' => 'rejected']);

        DB::statement("ALTER TABLE class_replacements MODIFY status ENUM('planned','pending_verification','verified','rejected','cancelled','overdue') DEFAULT 'planned'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE class_replacements MODIFY status ENUM('planned','pending_verification','verified','rejected','cancelled','overdue','pending','implementation_submitted','implemented','implementation_rejected') DEFAULT 'pending'");

        DB::table('class_replacements')->where('status', 'planned')->update(['status' => 'pending']);
        DB::table('class_replacements')->where('status', 'pending_verification')->update(['status' => 'implementation_submitted']);
        DB::table('class_replacements')->where('status', 'verified')->update(['status' => 'implemented']);
        DB::table('class_replacements')->where('status', 'rejected')->update(['status' => 'implementation_rejected']);
        DB::table('class_replacements')->where('status', 'overdue')->update(['status' => 'pending']);

        DB::statement("ALTER TABLE class_replacements MODIFY status ENUM('pending','implementation_submitted','implemented','implementation_rejected','cancelled') DEFAULT 'pending'");

        Schema::table('class_replacements', function (Blueprint $table) {
            $table->dropColumn([
                'already_implemented',
                'original_venue',
                'original_duration_minutes',
                'replacement_duration_minutes',
                'replacement_venue',
                'evidence_path',
                'evidence_original_name',
                'evidence_uploaded_at',
            ]);
            $table->dropConstrainedForeignId('programme_id');
        });
    }
};
