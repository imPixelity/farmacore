<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table): void {
            $table->index('trx_date');
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->index(['status', 'sale_date']);
        });

        Schema::table('sale_returns', function (Blueprint $table): void {
            $table->index('return_date');
        });

        Schema::table('payables', function (Blueprint $table): void {
            $table->index('due_date');
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->index('created_at');
        });

        Schema::table('stock_opnames', function (Blueprint $table): void {
            $table->index('opname_date');
        });

        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->index('rx_date');
        });

        Schema::table('prescription_screenings', function (Blueprint $table): void {
            $table->index('performed_at');
        });

        Schema::table('prescription_compoundings', function (Blueprint $table): void {
            $table->index('performed_at');
        });

        Schema::table('prescription_dispenses', function (Blueprint $table): void {
            $table->index('dispensed_at');
        });
    }

    public function down(): void
    {
        Schema::table('prescription_dispenses', function (Blueprint $table): void {
            $table->dropIndex(['dispensed_at']);
        });

        Schema::table('prescription_compoundings', function (Blueprint $table): void {
            $table->dropIndex(['performed_at']);
        });

        Schema::table('prescription_screenings', function (Blueprint $table): void {
            $table->dropIndex(['performed_at']);
        });

        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->dropIndex(['rx_date']);
        });

        Schema::table('stock_opnames', function (Blueprint $table): void {
            $table->dropIndex(['opname_date']);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
        });

        Schema::table('payables', function (Blueprint $table): void {
            $table->dropIndex(['due_date']);
        });

        Schema::table('sale_returns', function (Blueprint $table): void {
            $table->dropIndex(['return_date']);
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropIndex(['status', 'sale_date']);
        });

        Schema::table('financial_transactions', function (Blueprint $table): void {
            $table->dropIndex(['trx_date']);
        });
    }
};
