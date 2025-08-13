<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Critical composite index for status filtering
        Schema::table('po_transactions', function (Blueprint $table) {
            $table->index(['status', 'approved_at', 'rejected_at', 'voided_at', 'cancelled_at'], 'idx_po_status_composite');
        });

        // Indexes for commonly searched columns
        Schema::table('po_transactions', function (Blueprint $table) {
            $table->index('po_number', 'idx_po_number');
            $table->index('pr_number', 'idx_pr_number');
            $table->index('po_year_number_id', 'idx_po_year_number');
            $table->index('business_unit_name', 'idx_business_unit_name');
            $table->index('supplier_name', 'idx_supplier_name');
            $table->index('date_needed', 'idx_date_needed');
        });

        // Critical index for the po_orders subquery
        Schema::table('po_orders', function (Blueprint $table) {
            $table->index(['po_id', 'deleted_at', 'quantity_serve'], 'idx_po_orders_performance');
        });

        // Index for pr_transactions relation
        Schema::table('pr_transactions', function (Blueprint $table) {
            $table->index(['pr_number', 'deleted_at'], 'idx_pr_number_deleted');
            $table->index('pr_year_number_id', 'idx_pr_year_number');
        });

        // Index for log_history ordering
        Schema::table('log_history', function (Blueprint $table) {
            $table->index(['po_id', 'created_at'], 'idx_log_history_po_created');
        });
    }

    public function down()
    {
        Schema::table('po_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_po_status_composite');
            $table->dropIndex('idx_po_number');
            $table->dropIndex('idx_pr_number');
            $table->dropIndex('idx_po_year_number');
            $table->dropIndex('idx_business_unit_name');
            $table->dropIndex('idx_supplier_name');
            $table->dropIndex('idx_date_needed');
        });

        Schema::table('po_orders', function (Blueprint $table) {
            $table->dropIndex('idx_po_orders_performance');
        });

        Schema::table('pr_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_pr_number_deleted');
            $table->dropIndex('idx_pr_year_number');
        });

        Schema::table('log_history', function (Blueprint $table) {
            $table->dropIndex('idx_log_history_po_created');
        });
    }
};