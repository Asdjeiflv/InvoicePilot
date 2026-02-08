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
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete()->comment('Quotation ID');
            $table->text('description')->comment('Item description');
            $table->integer('quantity')->default(1)->comment('Quantity');
            $table->decimal('unit_price', 12, 2)->comment('Unit price');
            $table->decimal('tax_rate', 5, 2)->default(10.00)->comment('Tax rate (%)');
            $table->decimal('line_total', 12, 2)->comment('Line total (quantity * unit_price)');
            $table->timestamps();

            $table->index('quotation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
