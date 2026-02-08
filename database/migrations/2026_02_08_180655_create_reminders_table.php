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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete()->comment('Invoice ID');
            $table->enum('reminder_type', ['soft', 'normal', 'final'])->comment('Reminder type');
            $table->string('sent_to')->comment('Email address');
            $table->string('subject')->comment('Email subject');
            $table->text('body')->comment('Email body');
            $table->timestamp('sent_at')->comment('Sent at');
            $table->foreignId('sent_by')->constrained('users')->comment('Sent by user ID');
            $table->timestamps();

            $table->index('invoice_id');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
