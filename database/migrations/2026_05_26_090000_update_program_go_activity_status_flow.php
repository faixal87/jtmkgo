<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('program_go_activities')) {
            return;
        }

        DB::statement("ALTER TABLE program_go_activities MODIFY status ENUM('draft', 'in_progress', 'completed', 'pending_verification', 'approved', 'returned_for_correction', 'rejected') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('program_go_activities')) {
            return;
        }

        DB::statement("UPDATE program_go_activities SET status = 'draft' WHERE status IN ('in_progress', 'completed')");
        DB::statement("ALTER TABLE program_go_activities MODIFY status ENUM('draft', 'pending_verification', 'approved', 'rejected', 'returned_for_correction') NOT NULL DEFAULT 'draft'");
    }
};
