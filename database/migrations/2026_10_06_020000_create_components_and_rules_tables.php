<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('manufacturer_part_number')->nullable();
            $table->string('internal_part_number')->nullable();
            $table->string('sales_part_number')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->decimal('sales_price', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->decimal('weight', 8, 3)->nullable();
            $table->string('dimensions')->nullable();
            $table->json('technical_specs')->nullable();
            $table->string('status')->default('active'); // active, inactive, discontinued
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('configuration_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type'); // visibility, required, dependency, compatibility, exclusion, default_value, bom, pricing, part_number, validation, warning
            $table->string('condition_logic')->default('AND'); // AND, OR
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'is_active', 'type']);
        });

        Schema::create('rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('configuration_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('operator'); // equals, not_equals, in, not_in, greater_than, greater_than_or_equal, less_than, less_than_or_equal, contains, not_contains, is_empty, is_not_empty
            $table->text('value')->nullable();
            $table->string('logic')->default('AND'); // AND, OR for grouping
            $table->unsignedInteger('group')->default(0); // for grouped conditions
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('rule_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('configuration_rule_id')->constrained()->cascadeOnDelete();
            $table->string('action_type'); // show, hide, require, optional, set_value, add_component, remove_component, set_price, set_part_number, warn, error
            $table->string('target_type'); // attribute, option, component
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('compatibility_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('option_id');
            $table->foreignId('target_attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->unsignedBigInteger('target_option_id');
            $table->string('type')->default('compatible'); // compatible, incompatible
            $table->text('reason')->nullable();
            $table->text('engineering_note')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'attribute_id', 'option_id']);
        });

        Schema::create('bom_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('condition_logic')->default('AND');
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bom_rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('operator');
            $table->text('value')->nullable();
            $table->string('logic')->default('AND');
            $table->unsignedInteger('group')->default(0);
            $table->timestamps();
        });

        Schema::create('bom_rule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 8, 2)->default(1);
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();
        });

        Schema::create('part_number_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('segments'); // [{type: 'literal'|'attribute', value: 'HEC'|attribute_code, separator: '-'}]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_number_patterns');
        Schema::dropIfExists('bom_rule_items');
        Schema::dropIfExists('bom_rule_conditions');
        Schema::dropIfExists('bom_rules');
        Schema::dropIfExists('compatibility_rules');
        Schema::dropIfExists('rule_actions');
        Schema::dropIfExists('rule_conditions');
        Schema::dropIfExists('configuration_rules');
        Schema::dropIfExists('components');
        Schema::dropIfExists('component_categories');
    }
};
