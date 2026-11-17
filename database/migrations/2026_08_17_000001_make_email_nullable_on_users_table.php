<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phone-only sign-up has no email. Schema builder (not raw SQL) so it runs on MySQL, PostgreSQL and SQLite.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'email')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'email')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
