<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Operational settings overridable per scope: STORE → FRANCHISE → ORGANIZATION → GLOBAL → config.
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type', 20);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('key', 60);
            $table->json('value');
            $table->timestamps();

            $table->unique(['scope_type', 'scope_id', 'key']);
            $table->index('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
