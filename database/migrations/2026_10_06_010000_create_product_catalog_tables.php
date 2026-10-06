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
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_families', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('product_family_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->string('sku')->nullable();
            $table->string('internal_part_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->text('applications')->nullable();
            $table->text('features')->nullable();
            $table->string('image')->nullable();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->string('status')->default('draft'); // draft, in_review, approved, published, archived
            $table->string('version')->default('1.0');
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_configurable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'product_family_id']);
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type'); // text, integer, decimal, boolean, select, multi_select, radio, checkbox, range, currency, date
            $table->string('unit')->nullable();
            $table->text('description')->nullable();
            $table->json('validation_rules')->nullable();
            $table->boolean('is_global')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_adjustment', 12, 2)->default(0);
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('sku')->nullable();
            $table->string('part_number')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['attribute_id', 'code']);
        });

        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->string('default_value')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->text('help_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('step_name')->nullable(); // wizard step grouping
            $table->timestamps();

            $table->unique(['product_id', 'attribute_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('attribute_options');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_families');
        Schema::dropIfExists('product_categories');
    }
};
