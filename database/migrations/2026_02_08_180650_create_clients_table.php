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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Client code');
            $table->string('company_name')->comment('Company name');
            $table->string('contact_name')->nullable()->comment('Contact person name');
            $table->string('email')->nullable()->comment('Email address');
            $table->string('phone')->nullable()->comment('Phone number');
            $table->text('address')->nullable()->comment('Address');
            $table->integer('payment_terms_days')->default(30)->comment('Payment terms in days');
            $table->integer('closing_day')->nullable()->comment('Closing day of month');
            $table->text('notes')->nullable()->comment('Notes');
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('company_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
