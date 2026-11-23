<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('abuse_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reported_user_id')->nullable();
            $table->unsignedBigInteger('reporter_user_id')->nullable();
            $table->string('reported_handle', 255)->nullable();
            $table->string('reported_url', 2048);
            $table->string('category', 80);
            $table->text('message')->nullable();
            $table->string('status', 24)->default('new');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['reported_user_id', 'created_at']);
            $table->foreign('reported_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reporter_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('abuse_report_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('abuse_report_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('action', 24);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->text('note')->nullable();
            $table->timestamp('created_at');
            $table->index(['abuse_report_id', 'created_at']);
            $table->foreign('abuse_report_id')->references('id')->on('abuse_reports')->onDelete('cascade');
            $table->foreign('actor_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abuse_report_events');
        Schema::dropIfExists('abuse_reports');
    }
};
