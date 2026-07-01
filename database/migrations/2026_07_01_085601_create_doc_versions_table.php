<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doc_id')->constrained('docs')->cascadeOnDelete();
            $table->foreignId('edited_by')->constrained('users');
            $table->string('title');
            $table->longText('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_versions');
    }
};
