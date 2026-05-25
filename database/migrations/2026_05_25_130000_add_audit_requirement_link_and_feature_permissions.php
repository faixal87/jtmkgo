<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'audit_requirement_link')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('audit_requirement_link', 2048)->nullable()->after('bem_membership');
            });
        }

        if (! Schema::hasTable('feature_permissions')) {
            Schema::create('feature_permissions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('permission_key', 120);
                $table->boolean('is_active')->default(true);
                $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('granted_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'permission_key'], 'feature_permissions_user_key_unique');
                $table->index(['permission_key', 'is_active'], 'feature_permissions_key_active_index');
                $table->index(['user_id', 'is_active'], 'feature_permissions_user_active_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_permissions');

        if (! Schema::hasColumn('users', 'audit_requirement_link')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if ($this->hasIndex('users', 'users_audit_requirement_link_index')) {
                $table->dropIndex('users_audit_requirement_link_index');
            }

            $table->dropColumn('audit_requirement_link');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$index]))->isNotEmpty();
    }
};
