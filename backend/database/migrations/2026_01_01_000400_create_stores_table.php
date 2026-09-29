<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('franchise_id')->constrained()->restrictOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name', 150);
            $table->string('business_type', 20)->default('FOOD');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('city', 80);
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('timezone', 64)->default('Africa/Blantyre');
            $table->char('currency', 3)->default('MWK');
            $table->json('opening_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_accepting_orders')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'latitude', 'longitude']);
        });

        Schema::create('store_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->text('description')->nullable();
            $table->text('announcement')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'locale']);
            $table->foreign('locale')->references('code')->on('languages');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_translations');
        Schema::dropIfExists('stores');
    }
};
