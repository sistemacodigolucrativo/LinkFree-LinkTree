<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('platform_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedTinyInteger('rating');
            $table->text('testimonial')->nullable();
            $table->boolean('publication_consent')->default(false);
            $table->timestamp('consented_at')->nullable();
            $table->string('consent_version', 20)->nullable();
            $table->string('status', 16)->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'publication_consent', 'created_at'], 'platform_reviews_public_idx');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_reviews');
    }
};
