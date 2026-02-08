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
        // Add composite indexes for common queries on invoices
        Schema::table('invoices', function (Blueprint $table) {
            // For filtering overdue invoices
            $table->index(['status', 'due_date'], 'invoices_status_due_date_index');

            // For client invoice filtering
            $table->index(['client_id', 'status'], 'invoices_client_status_index');

            // For date range queries
            $table->index(['issue_date', 'status'], 'invoices_issue_date_status_index');
        });

        // Add composite indexes for common queries on quotations
        Schema::table('quotations', function (Blueprint $table) {
            // For client quotation filtering
            $table->index(['client_id', 'status'], 'quotations_client_status_index');

            // For date-based filtering
            $table->index(['issue_date', 'status'], 'quotations_issue_date_status_index');
        });

        // Add index for payments by date
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['payment_date', 'invoice_id'], 'payments_date_invoice_index');
        });

        // Add index for reminders
        Schema::table('reminders', function (Blueprint $table) {
            $table->index(['sent_at', 'invoice_id'], 'reminders_sent_at_invoice_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_status_due_date_index');
            $table->dropIndex('invoices_client_status_index');
            $table->dropIndex('invoices_issue_date_status_index');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropIndex('quotations_client_status_index');
            $table->dropIndex('quotations_issue_date_status_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_date_invoice_index');
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropIndex('reminders_sent_at_invoice_index');
        });
    }
};
