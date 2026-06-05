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
        if (! Schema::hasTable('link_go_portfolios')) {
            Schema::create('link_go_portfolios', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('link_go_links')) {
            Schema::create('link_go_links', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('portfolio_id')->nullable()->constrained('link_go_portfolios')->nullOnDelete();
                $table->string('title');
                $table->text('url');
                $table->text('description')->nullable();
                $table->enum('visibility', ['all_jtmk', 'kj_kpro_only', 'owner_only'])->default('all_jtmk')->index();
                $table->boolean('is_pinned')->default(false)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('click_count')->default(0);
                $table->unsignedInteger('copy_count')->default(0);
                $table->timestamp('last_clicked_at')->nullable();
                $table->timestamp('last_copied_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'created_at']);
                $table->index(['portfolio_id', 'is_active']);
                $table->index(['is_active', 'is_pinned', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('link_go_links');
        Schema::dropIfExists('link_go_portfolios');
    }
};
