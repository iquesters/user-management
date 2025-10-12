<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add ULID (unique public identifier)
            if (!Schema::hasColumn('users', 'uid')) {
                $table->ulid('uid')->unique()->after('id');
            }

            // Add optional phone fields
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email_verified_at');
            }

            if (!Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('phone');
            }

            // Add user status field
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('unknown')->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['uid', 'phone', 'phone_verified_at', 'status']);
        });
    }
};