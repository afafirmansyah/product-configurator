<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A BOM line references either a component or a configurable child product
     * (sub-assembly), so component_id becomes nullable. Existing rows are unchanged.
     */
    public function up(): void
    {
        Schema::table('bom_rule_items', function (Blueprint $table) {
            $table->foreignId('child_product_id')->nullable()->after('component_id')
                ->constrained('products')->nullOnDelete();
        });

        Schema::table('bom_rule_items', function (Blueprint $table) {
            $table->foreignId('component_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * component_id intentionally stays nullable: reverting it could fail on rows
     * that are sub-assembly lines, so only the new column is dropped.
     */
    public function down(): void
    {
        Schema::table('bom_rule_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('child_product_id');
        });
    }
};
