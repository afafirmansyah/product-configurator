<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('currency', 3)->default('EUR');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'price_list_id']);
        });

        Schema::create('option_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->decimal('price_adjustment', 12, 2)->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->unique(['attribute_option_id', 'price_list_id']);
        });

        Schema::create('configuration_sessions', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('configuration_number')->unique(); // CFG-2026-000125
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('draft'); // draft, validated, finalized
            $table->string('version')->default('1');
            $table->decimal('total_price', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->string('configuration_code')->nullable(); // generated part number
            $table->json('snapshot')->nullable(); // frozen config result at finalization
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });

        Schema::create('configuration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('configuration_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->text('value'); // selected option id(s) or raw value
            $table->timestamps();

            $table->unique(['configuration_session_id', 'attribute_id']);
        });

        Schema::create('configuration_bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('configuration_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();
        });

        Schema::create('quotation_requests', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('reference_number')->unique(); // QR-2026-000001
            $table->foreignId('configuration_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company');
            $table->string('contact_person');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('project_name')->nullable();
            $table->string('project_location')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->date('required_delivery_date')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('pending'); // pending, reviewed, quoted, closed
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // created, updated, deleted, published, etc.
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('quotation_requests');
        Schema::dropIfExists('configuration_bom_items');
        Schema::dropIfExists('configuration_items');
        Schema::dropIfExists('configuration_sessions');
        Schema::dropIfExists('option_prices');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('price_lists');
    }
};
