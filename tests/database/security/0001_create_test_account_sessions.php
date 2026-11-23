<?php

// Disposable regression fixture only. Track C owns deployment schema.
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->unsignedBigInteger('user_id');
            $t->timestamp('revoked_at')->nullable();
        });
        Schema::create('account_security_audit', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('event');
            $t->string('session_id')->nullable();
            $t->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_security_audit');
        Schema::dropIfExists('account_sessions');
    }
};
