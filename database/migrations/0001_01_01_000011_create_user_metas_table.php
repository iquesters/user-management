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
        // Renamed from an unprefixed file; existing installs already have this table.
        if (Schema::hasTable('user_metas')) {
            return;
        }

        Schema::create('user_metas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ref_parent')->unsigned()->nullable();
            $table->string('meta_key');
            $table->string('meta_value');
            $table->string('status')->default('unknown');
            $table->bigInteger('created_by')->default(0);
            $table->bigInteger('updated_by')->default(0);
            $table->timestamps();
            $table->foreign('ref_parent')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally empty: this file was renamed from an unprefixed migration.
        // On existing installs it ran as a no-op, so rolling it back must not drop
        // live data. Use migrate:fresh to rebuild from scratch.
    }
};