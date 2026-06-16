<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_members', function (Blueprint $table) {
            $table->integer('capacity_hours')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('project_members', function (Blueprint $table) {
            $table->dropColumn('capacity_hours');
        });
    }
};
