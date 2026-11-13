<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table): void {
            $table->id();
            $table->string('return_no', 30)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->restrictOnDelete();
            $table->date('return_date');
            $table->decimal('total_value', 15, 2)->default(0);
            $table->decimal('payable_adjustment_amount', 15, 2)->default(0);
            $table->decimal('supplier_credit_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['status', 'return_date']);
            $table->index(['supplier_id', 'goods_receipt_id']);
        });

        Schema::create('purchase_return_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->restrictOnDelete();
            $table->foreignId('goods_receipt_item_id')->constrained('goods_receipt_items')->restrictOnDelete();
            $table->foreignId('item_batch_id')->constrained('item_batches')->restrictOnDelete();
            $table->decimal('qty_base', 15, 3);
            $table->decimal('line_value', 15, 2);
            $table->enum('reason', ['rusak', 'tidak_sesuai', 'mendekati_ed', 'salah_kirim']);
            $table->timestamps();
            $table->unique(
                ['purchase_return_id', 'goods_receipt_item_id', 'item_batch_id'],
                'purchase_return_items_unique',
            );
            $table->index(['goods_receipt_item_id', 'item_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};
