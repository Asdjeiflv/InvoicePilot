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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete()->comment('Invoice ID');
            $table->date('payment_date')->comment('Payment date');
            $table->decimal('amount', 12, 2)->comment('Payment amount');
            $table->string('method')->nullable()->comment('Payment method (e.g., bank_transfer, cash, credit_card)');
            $table->string('reference_no')->nullable()->comment('Reference number');
            $table->text('note')->nullable()->comment('Note');
            $table->foreignId('created_by')->constrained('users')->comment('Created by user ID');
            $table->timestamps();

            $table->index('invoice_id');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
