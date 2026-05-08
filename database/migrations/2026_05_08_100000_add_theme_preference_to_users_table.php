<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'theme_preference')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('theme_preference', 30)->nullable()->default('default')->after('is_super_admin');
            });
        }

        if (Schema::hasColumn('users', 'theme')) {
            DB::table('users')->update([
                'theme_preference' => DB::raw("CASE WHEN theme = 'blue' THEN 'blue' WHEN theme = 'dark' THEN 'dark' ELSE 'default' END"),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'theme_preference')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('theme_preference');
            });
        }
    }
};
