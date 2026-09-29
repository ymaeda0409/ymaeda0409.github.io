<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('code', 60);
            $table->string('image_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('product_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 150);
            $table->timestamps();

            $table->unique(['category_id', 'locale']);
            $table->foreign('locale')->references('code')->on('languages');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained('product_categories')->restrictOnDelete();
            $table->string('sku', 60);
            $table->string('image_url')->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedSmallInteger('preparation_minutes')->default(15);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'sku']);
        });

        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'locale']);
            $table->foreign('locale')->references('code')->on('languages');
        });

        Schema::create('product_option_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('min_select')->default(0);
            $table->unsignedSmallInteger('max_select')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_option_group_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_group_id')->constrained('product_option_groups')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 150);
            $table->timestamps();

            $table->unique(['option_group_id', 'locale']);
            $table->foreign('locale')->references('code')->on('languages');
        });

        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_group_id')->constrained('product_option_groups')->cascadeOnDelete();
            $table->unsignedBigInteger('price')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_option_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')->constrained('product_options')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 150);
            $table->timestamps();

            $table->unique(['option_id', 'locale']);
            $table->foreign('locale')->references('code')->on('languages');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_translations');
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('product_option_group_translations');
        Schema::dropIfExists('product_option_groups');
        Schema::dropIfExists('product_translations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_category_translations');
        Schema::dropIfExists('product_categories');
    }
};
