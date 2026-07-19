<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heartbeat_logs', function (Blueprint $table) {
            $table->id();
            $table->string('installation_id', 64)->index();
            $table->string('app_version', 20)->nullable();
            $table->boolean('wa_online')->default(false);
            $table->string('php_version', 20)->nullable();
            $table->timestamp('received_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heartbeat_logs');
    }
};
