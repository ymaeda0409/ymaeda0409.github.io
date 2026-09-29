<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 80)->unique();
            $table->string('default_locale', 10)->default('en');
            $table->char('currency', 3)->default('MWK');
            $table->string('timezone', 64)->default('Africa/Blantyre');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('default_locale')->references('code')->on('languages');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
