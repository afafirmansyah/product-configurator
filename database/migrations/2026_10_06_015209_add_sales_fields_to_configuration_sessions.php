<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuration_sessions', function (Blueprint $table) {
            $table->string('name')->nullable()->after('configuration_number');
            $table->string('customer')->nullable()->after('name');
            $table->string('project')->nullable()->after('customer');
            $table->string('bom_status')->default('pending')->after('status'); // pending, generated, validated, failed
            $table->unsignedInteger('bom_revision')->default(0)->after('bom_status');
            $table->timestamp('bom_generated_at')->nullable()->after('bom_revision');
            $table->json('bom_snapshot')->nullable()->after('bom_generated_at');
            $table->json('validation_errors')->nullable()->after('bom_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('configuration_sessions', function (Blueprint $table) {
            $table->dropColumn(['name', 'customer', 'project', 'bom_status', 'bom_revision', 'bom_generated_at', 'bom_snapshot', 'validation_errors']);
        });
    }
};
