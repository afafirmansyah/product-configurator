<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the missing foreign keys for compatibility_rules.option_id and
     * compatibility_rules.target_option_id referencing attribute_options(id),
     * matching the sibling attribute_id FK on the same table.
     *
     * SQLite cannot add foreign keys to an existing table via ALTER TABLE, so on
     * sqlite this migration is a documented no-op (the testing suite runs on
     * sqlite :memory:). The FKs are applied only on mysql/pgsql.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('compatibility_rules', function (Blueprint $table) {
            $table->foreign('option_id')->references('id')->on('attribute_options')->cascadeOnDelete();
            $table->foreign('target_option_id')->references('id')->on('attribute_options')->cascadeOnDelete();
        });
    }

    /**
     * Drop the foreign keys added in up().
     *
     * SQLite never created the keys (see up()), so the reversal is a no-op there.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('compatibility_rules', function (Blueprint $table) {
            $table->dropForeign(['option_id']);
            $table->dropForeign(['target_option_id']);
        });
    }
};
