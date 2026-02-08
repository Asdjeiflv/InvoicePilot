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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_no')->unique()->comment('Quotation number: Q-YYYY-00001');
            $table->foreignId('client_id')->constrained()->cascadeOnDelete()->comment('Client ID');
            $table->date('issue_date')->comment('Issue date');
            $table->date('valid_until')->comment('Valid until date');
            $table->decimal('subtotal', 12, 2)->default(0)->comment('Subtotal amount');
            $table->decimal('tax_total', 12, 2)->default(0)->comment('Tax amount');
            $table->decimal('total', 12, 2)->default(0)->comment('Total amount');
            $table->enum('status', ['draft', 'sent', 'approved', 'rejected'])->default('draft')->comment('Status');
            $table->foreignId('created_by')->constrained('users')->comment('Created by user ID');
            $table->timestamps();
            $table->softDeletes();

            $table->index('quotation_no');
            $table->index('client_id');
            $table->index('status');
            $table->index('issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
