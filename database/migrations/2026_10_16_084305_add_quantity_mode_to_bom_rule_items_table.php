<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bom_rule_items', function (Blueprint $table) {
            // Null is treated as "fixed" so existing lines keep their stored quantity.
            $table->string('quantity_mode', 20)->nullable()->after('quantity');
            $table->foreignId('quantity_attribute_id')->nullable()->after('quantity_mode')
                ->constrained('attributes')->nullOnDelete();
            $table->string('quantity_formula', 255)->nullable()->after('quantity_attribute_id');
            $table->boolean('quantity_round_up')->default(false)->after('quantity_formula');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bom_rule_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quantity_attribute_id');
            $table->dropColumn(['quantity_mode', 'quantity_formula', 'quantity_round_up']);
        });
    }
};
