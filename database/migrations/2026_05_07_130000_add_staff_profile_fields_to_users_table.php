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
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable()->after('date_of_birth');
            $table->string('position')->nullable()->after('department');
            $table->string('grade')->nullable()->after('position');
            $table->string('mbot_membership')->nullable()->after('grade');
            $table->string('bem_membership')->nullable()->after('mbot_membership');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'department',
                'position',
                'grade',
                'mbot_membership',
                'bem_membership',
            ]);
        });
    }
};
