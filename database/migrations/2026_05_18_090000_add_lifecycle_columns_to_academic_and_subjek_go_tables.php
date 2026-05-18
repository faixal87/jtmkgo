<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $softDeleteTables = [
        'academic_semesters',
        'academic_subjects',
        'academic_class_groups',
        'academic_subject_offerings',
        'subjek_go_sessions',
        'subjek_go_subject_masters',
        'subjek_go_class_groups',
        'subjek_go_offered_subjects',
    ];

    /**
     * @var array<int, string>
     */
    private array $archivableTables = [
        'academic_subjects',
        'academic_class_groups',
        'academic_subject_offerings',
        'subjek_go_subject_masters',
        'subjek_go_class_groups',
        'subjek_go_offered_subjects',
    ];

    public function up(): void
    {
        foreach ($this->softDeleteTables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'deleted_at')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->softDeletes();
            });
        }

        foreach ($this->archivableTables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'archived_at')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->timestamp('archived_at')->nullable()->after('is_active');
                $table->index('archived_at');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->archivableTables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'archived_at')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropIndex(['archived_at']);
                $table->dropColumn('archived_at');
            });
        }

        foreach ($this->softDeleteTables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'deleted_at')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
