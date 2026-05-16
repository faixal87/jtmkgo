<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index(['account_status', 'is_super_admin'], 'users_status_super_index');
        });

        Schema::table('module_user_access', function (Blueprint $table): void {
            $table->index(['user_id', 'is_active'], 'module_user_access_user_active_index');
            $table->index(['module_id', 'is_active'], 'module_user_access_module_active_index');
        });

        Schema::table('module_admins', function (Blueprint $table): void {
            $table->index(['user_id', 'is_active'], 'module_admins_user_active_index');
            $table->index(['module_id', 'is_active'], 'module_admins_module_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('module_admins', function (Blueprint $table): void {
            $table->dropIndex('module_admins_user_active_index');
            $table->dropIndex('module_admins_module_active_index');
        });

        Schema::table('module_user_access', function (Blueprint $table): void {
            $table->dropIndex('module_user_access_user_active_index');
            $table->dropIndex('module_user_access_module_active_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_status_super_index');
        });
    }
};
