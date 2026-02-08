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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique()->comment('Invoice number: I-YYYY-00001');
            $table->foreignId('client_id')->constrained()->cascadeOnDelete()->comment('Client ID');
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete()->comment('Related quotation ID (optional)');
            $table->date('issue_date')->comment('Issue date');
            $table->date('due_date')->comment('Due date');
            $table->decimal('subtotal', 12, 2)->default(0)->comment('Subtotal amount');
            $table->decimal('tax_total', 12, 2)->default(0)->comment('Tax amount');
            $table->decimal('total', 12, 2)->default(0)->comment('Total amount');
            $table->decimal('paid_amount', 12, 2)->default(0)->comment('Paid amount');
            $table->decimal('balance_due', 12, 2)->default(0)->comment('Balance due');
            $table->enum('status', ['draft', 'issued', 'partial_paid', 'paid', 'overdue', 'canceled'])->default('draft')->comment('Status');
            $table->timestamp('sent_at')->nullable()->comment('Sent at');
            $table->foreignId('created_by')->constrained('users')->comment('Created by user ID');
            $table->timestamps();
            $table->softDeletes();

            $table->index('invoice_no');
            $table->index('client_id');
            $table->index('quotation_id');
            $table->index('status');
            $table->index('issue_date');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
