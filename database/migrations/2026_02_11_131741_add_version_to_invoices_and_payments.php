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
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('balance_due');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
