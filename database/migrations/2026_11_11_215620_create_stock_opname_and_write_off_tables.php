<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table): void {
            $table->id();
            $table->string('opname_no', 30)->unique();
            $table->date('opname_date');
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'opname_date']);
        });

        Schema::create('stock_opname_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->restrictOnDelete();
            $table->foreignId('item_batch_id')->constrained('item_batches')->restrictOnDelete();
            $table->decimal('system_qty', 15, 3);
            $table->decimal('physical_qty', 15, 3)->nullable();
            $table->decimal('diff_qty', 15, 3)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->unique(['stock_opname_id', 'item_batch_id']);
            $table->index('item_batch_id');
        });

        Schema::create('stock_write_offs', function (Blueprint $table): void {
            $table->id();
            $table->string('write_off_no', 30)->unique();
            $table->foreignId('item_batch_id')->constrained('item_batches')->restrictOnDelete();
            $table->date('write_off_date');
            $table->decimal('qty_base', 15, 3);
            $table->enum('reason', ['rusak', 'kedaluwarsa']);
            $table->decimal('loss_amount', 15, 2);
            $table->enum('status', ['draft', 'approved'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['status', 'write_off_date']);
            $table->index(['item_batch_id', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_write_offs');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
    }
};
