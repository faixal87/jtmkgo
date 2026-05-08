<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('programme_id')
                ->nullable()
                ->after('semester_id')
                ->constrained('programmes')
                ->nullOnDelete();
        });

        DB::statement('ALTER TABLE courses MODIFY class_name VARCHAR(100) NULL');
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('programme_id');
        });

        DB::table('courses')->whereNull('class_name')->update(['class_name' => '']);
        DB::statement('ALTER TABLE courses MODIFY class_name VARCHAR(100) NOT NULL');
    }
};
