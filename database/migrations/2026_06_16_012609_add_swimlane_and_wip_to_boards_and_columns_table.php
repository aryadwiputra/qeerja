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
        Schema::table('boards', function (Blueprint $table) {
            $table->string('swimlane_field', 30)->default('none')->after('is_default');
        });

        Schema::table('board_columns', function (Blueprint $table) {
            $table->unsignedSmallInteger('wip_limit')->nullable()->after('is_done_column');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn('swimlane_field');
        });

        Schema::table('board_columns', function (Blueprint $table) {
            $table->dropColumn('wip_limit');
        });
    }
};
