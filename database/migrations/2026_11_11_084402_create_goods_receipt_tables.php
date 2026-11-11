<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('receipt_no', 30)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('invoice_no', 50);
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->enum('payment_method', ['tunai', 'kredit']);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_before_tax', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_after_tax', 15, 2)->default(0);
            $table->enum('matching_status', ['tidak_ada_po', 'sesuai', 'tidak_sesuai'])->default('tidak_ada_po');
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['supplier_id', 'invoice_no']);
            $table->index(['status', 'invoice_date']);
        });

        Schema::create('goods_receipt_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('qty_received', 15, 3);
            $table->decimal('qty_base', 15, 3);
            $table->string('batch_no', 60);
            $table->date('expiry_date');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->enum('match_status', ['sesuai', 'lebih', 'kurang', 'harga_beda', 'tidak_dipesan'])->default('tidak_dipesan');
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['goods_receipt_id', 'item_id']);
            $table->index(['item_id', 'batch_no', 'expiry_date']);
        });

        Schema::table('item_batches', function (Blueprint $table): void {
            $table->foreignId('goods_receipt_item_id')->nullable()
                ->after('item_id')
                ->constrained('goods_receipt_items')
                ->restrictOnDelete();
            $table->decimal('unit_cost_base', 15, 4)->nullable()->after('expiry_date');
        });
        $this->restoreSqliteBatchConstraints();

        Schema::create('payables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('goods_receipt_id')->unique()->constrained('goods_receipts')->restrictOnDelete();
            $table->string('invoice_no', 50);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('due_date');
            $table->enum('status', ['belum_dibayar', 'sebagian', 'lunas'])->default('belum_dibayar');
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['status', 'due_date']);
            $table->index(['supplier_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payables');

        Schema::table('item_batches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('goods_receipt_item_id');
            $table->dropColumn('unit_cost_base');
        });
        $this->restoreSqliteBatchConstraints();

        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
    }

    private function restoreSqliteBatchConstraints(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement("CREATE TRIGGER IF NOT EXISTS item_batches_qty_non_negative_insert BEFORE INSERT ON item_batches WHEN NEW.qty_on_hand < 0 BEGIN SELECT RAISE(ABORT, 'qty_on_hand must be non-negative'); END");
        DB::statement("CREATE TRIGGER IF NOT EXISTS item_batches_qty_non_negative_update BEFORE UPDATE OF qty_on_hand ON item_batches WHEN NEW.qty_on_hand < 0 BEGIN SELECT RAISE(ABORT, 'qty_on_hand must be non-negative'); END");
    }
};
