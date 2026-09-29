<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('franchise_id')->constrained()->restrictOnDelete();
            // NULL = serves every store of the franchise.
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vehicle_type', 20);
            $table->string('vehicle_number', 30)->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->boolean('is_online')->default(false);
            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->timestamps();

            $table->index(['franchise_id', 'is_online', 'status']);
        });

        Schema::create('delivery_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->decimal('distance_km', 6, 2)->nullable();
            $table->timestamp('offered_at');
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'status']);
            $table->index(['order_id', 'status']);
        });

        Schema::create('driver_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            // Device time: points buffered offline arrive late but keep their real time.
            $table->timestamp('recorded_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'recorded_at']);
            $table->index(['driver_id', 'recorded_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('driver_id')->references('id')->on('drivers')->nullOnDelete();
            $table->unsignedSmallInteger('delivery_pin_attempts')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropColumn('delivery_pin_attempts');
        });
        Schema::dropIfExists('driver_locations');
        Schema::dropIfExists('delivery_assignments');
        Schema::dropIfExists('drivers');
    }
};
