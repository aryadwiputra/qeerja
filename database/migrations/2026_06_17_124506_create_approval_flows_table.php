<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('column_id')->constrained('board_columns')->cascadeOnDelete();
            $table->json('required_approvers');
            $table->integer('min_approvals')->default(1);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('task_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approval_flow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['task_id', 'approval_flow_id', 'approver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_approvals');
        Schema::dropIfExists('approval_flows');
    }
};
