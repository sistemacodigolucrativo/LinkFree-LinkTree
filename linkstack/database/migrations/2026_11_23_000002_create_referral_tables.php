<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('code', 24)->unique();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_user_id')->nullable();
            $table->unsignedBigInteger('invited_user_id')->unique();
            $table->string('referral_code', 24);
            $table->timestamps();
            $table->index(['referrer_user_id', 'created_at'], 'referrals_owner_created_index');
            $table->foreign('referrer_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('invited_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('referral_codes');
    }
};
