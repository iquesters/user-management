<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Separate from login OTP attempts: an order OTP must NEVER be
        // usable as a login code (or vice versa).
        Schema::create('order_otp_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('organisation_id')->index();
            $table->string('channel_uid', 64);
            $table->char('scope_hash', 64);
            $table->char('destination_hash', 64)->index();
            $table->text('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->string('status', 16)->default('active');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index(['organisation_id', 'scope_hash', 'status'], 'order_otp_scope_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_otp_challenges');
    }
};
