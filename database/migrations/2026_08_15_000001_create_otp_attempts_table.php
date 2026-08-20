<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('identifier_type', 30)->default('phone')->index();
            $table->string('identifier_value', 255)->index();
            $table->string('delivery_channel', 30)->default('whatsapp')->index();
            $table->text('code_hash');
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->unsignedInteger('max_attempts')->default(5);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->bigInteger('created_by')->default(0);
            $table->bigInteger('updated_by')->default(0);
            $table->timestamps();
        });

        Schema::create('otp_attempt_metas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ref_parent')->unsigned()->nullable();
            $table->string('meta_key');
            $table->text('meta_value')->nullable();
            $table->string('status')->default('active');
            $table->bigInteger('created_by')->default(0);
            $table->bigInteger('updated_by')->default(0);
            $table->timestamps();

            $table->foreign('ref_parent')->references('id')->on('otp_attempts')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['ref_parent', 'meta_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_attempt_metas');
        Schema::dropIfExists('otp_attempts');
    }
};
