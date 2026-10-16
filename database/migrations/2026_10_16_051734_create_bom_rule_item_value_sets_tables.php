<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A BOM rule item may carry alternative attribute-value sets: the line is
     * included when ALL options of ANY one set are selected. Explicit short
     * index names keep identifiers under the 64-character MySQL limit.
     */
    public function up(): void
    {
        Schema::create('bom_rule_item_value_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_rule_item_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('bom_rule_item_value_set_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_rule_item_value_set_id')
                ->constrained('bom_rule_item_value_sets', 'id', 'brivso_value_set_id_foreign')
                ->cascadeOnDelete();
            $table->foreignId('attribute_option_id')
                ->constrained('attribute_options', 'id', 'brivso_attribute_option_id_foreign')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['bom_rule_item_value_set_id', 'attribute_option_id'], 'brivso_set_option_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_rule_item_value_set_options');
        Schema::dropIfExists('bom_rule_item_value_sets');
    }
};
