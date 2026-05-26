<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('program_go_activities') || Schema::hasColumn('program_go_activities', 'deleted_at')) {
            return;
        }

        Schema::table('program_go_activities', function (Blueprint $table): void {
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('program_go_activities') || ! Schema::hasColumn('program_go_activities', 'deleted_at')) {
            return;
        }

        Schema::table('program_go_activities', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
