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
            $table->string('ic_number')->unique()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->enum('account_status', ['pending', 'approved', 'rejected', 'inactive'])
                ->default('pending')
                ->after('phone');
            $table->timestamp('approved_at')->nullable()->after('account_status');
            $table->foreignId('approved_by')
                ->nullable()
                ->after('approved_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('is_super_admin')->default(false)->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'ic_number',
                'phone',
                'account_status',
                'approved_at',
                'approved_by',
                'is_super_admin',
            ]);
        });
    }
};
