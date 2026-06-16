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
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedSmallInteger('story_points')->nullable()->after('position');
        });

        Schema::table('sprints', function (Blueprint $table) {
            $table->unsignedSmallInteger('committed_points')->nullable()->after('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('story_points');
        });

        Schema::table('sprints', function (Blueprint $table) {
            $table->dropColumn('committed_points');
        });
    }
};
