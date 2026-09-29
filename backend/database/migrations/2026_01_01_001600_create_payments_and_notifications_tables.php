<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('method', 20);
            $table->string('gateway', 30);
            $table->string('status', 20);
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->string('phone', 20)->nullable();
            // Our id sent to the gateway (idempotency) and the gateway's own id.
            $table->string('reference', 64)->unique();
            $table->string('gateway_reference', 128)->nullable()->index();
            $table->string('failure_code', 60)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60);
            $table->string('channel', 10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['code', 'channel']);
        });

        Schema::create('notification_template_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('notification_templates')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title', 150)->nullable();
            $table->text('body');
            $table->timestamps();

            $table->unique(['template_id', 'locale']);
            $table->foreign('locale')->references('code')->on('languages');
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 512)->unique();
            $table->string('platform', 10);
            $table->string('app', 20);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 10);
            $table->string('template_code', 60);
            $table->string('locale', 10);
            $table->string('status', 20);
            $table->string('error', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('notification_template_translations');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('payments');
    }
};
