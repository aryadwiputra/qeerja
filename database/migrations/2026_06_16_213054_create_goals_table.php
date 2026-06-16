<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->date('target_date')->nullable();
            $table->timestamps();
        });

        Schema::create('key_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('status', ['not_started', 'in_progress', 'achieved'])->default('not_started');
            $table->decimal('current_value', 10, 2)->default(0);
            $table->decimal('target_value', 10, 2)->default(100);
            $table->timestamps();
        });

        Schema::create('epic_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['epic_id', 'goal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epic_goals');
        Schema::dropIfExists('key_results');
        Schema::dropIfExists('goals');
    }
};
