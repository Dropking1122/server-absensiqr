<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installations', function (Blueprint $table) {
            $table->id();
            $table->string('installation_id', 64)->unique();
            $table->string('app_name', 255)->nullable();
            $table->string('app_url', 500)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->string('php_version', 20)->nullable();
            $table->string('db_driver', 20)->nullable();
            $table->boolean('wa_online')->default(false);
            $table->string('update_channel', 20)->default('stable');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installations');
    }
};
