<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('franchise_id')->constrained()->restrictOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->foreignId('kitchen_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->unsignedBigInteger('base_fee')->default(0);
            $table->decimal('base_distance_km', 6, 2)->default(0);
            $table->unsignedBigInteger('additional_fee_per_km')->default(0);
            $table->decimal('max_delivery_distance_km', 6, 2);
            // Reserved for GeoJSON polygon zones (future). Distance-based matching is used while null.
            $table->json('polygon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
