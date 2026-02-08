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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('User ID (nullable for system actions)');
            $table->string('action')->comment('Action performed (e.g., created, updated, deleted, sent)');
            $table->string('target_type')->nullable()->comment('Target model type');
            $table->unsignedBigInteger('target_id')->nullable()->comment('Target model ID');
            $table->json('before_json')->nullable()->comment('Before state (JSON)');
            $table->json('after_json')->nullable()->comment('After state (JSON)');
            $table->string('ip_address')->nullable()->comment('IP address');
            $table->timestamp('created_at')->useCurrent()->comment('Created at');

            $table->index('user_id');
            $table->index(['target_type', 'target_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
