<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('purchase_order_items', 'qty_received')) {
            Schema::table('purchase_order_items', function (Blueprint $table): void {
                $table->decimal('qty_received', 15, 3)->default(0)->after('qty_base');
                $table->index(['purchase_order_id', 'qty_received'], 'po_items_received_idx');
            });
        }

        if (! Schema::hasColumn('goods_receipts', 'purchase_order_id')) {
            Schema::table('goods_receipts', function (Blueprint $table): void {
                $table->foreignId('purchase_order_id')->nullable()->after('supplier_id')->constrained('purchase_orders')->restrictOnDelete();
                $table->index(['purchase_order_id', 'matching_status'], 'receipts_po_matching_idx');
            });
        }

        if (! Schema::hasColumn('goods_receipt_items', 'purchase_order_item_id')) {
            Schema::table('goods_receipt_items', function (Blueprint $table): void {
                $table->foreignId('purchase_order_item_id')->nullable()->after('goods_receipt_id')->constrained('purchase_order_items')->restrictOnDelete();
                $table->boolean('is_return_candidate')->default(false)->after('match_status');
                $table->index(['purchase_order_item_id', 'is_return_candidate'], 'receipt_items_matching_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table): void {
            $table->dropIndex('receipt_items_matching_idx');
            $table->dropConstrainedForeignId('purchase_order_item_id');
            $table->dropColumn('is_return_candidate');
        });

        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->dropIndex('receipts_po_matching_idx');
            $table->dropConstrainedForeignId('purchase_order_id');
        });

        Schema::table('purchase_order_items', function (Blueprint $table): void {
            $table->dropIndex('po_items_received_idx');
            $table->dropColumn('qty_received');
        });
    }
};
