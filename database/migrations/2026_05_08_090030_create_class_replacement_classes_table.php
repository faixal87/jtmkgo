<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_replacement_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_replacement_id')->constrained('class_replacements')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['class_replacement_id', 'class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_replacement_classes');
    }
};
