<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20)->unique();
            $table->date('released_at');
            $table->string('channel', 20)->default('stable');
            $table->string('category', 50);
            $table->string('title', 255);
            $table->text('notes');
            $table->text('notes_html')->nullable();
            $table->boolean('mandatory')->default(false);
            $table->string('min_version', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
