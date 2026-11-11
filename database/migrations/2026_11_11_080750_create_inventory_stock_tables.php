<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->string('batch_no', 60);
            $table->date('expiry_date');
            $table->decimal('qty_on_hand', 15, 3)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['item_id', 'batch_no', 'expiry_date']);
            $table->index(['item_id', 'expiry_date']);
            $table->index(['expiry_date', 'qty_on_hand']);
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_batch_id')->constrained('item_batches')->restrictOnDelete();
            $table->decimal('qty_change', 15, 3);
            $table->decimal('qty_after', 15, 3);
            $table->string('type', 40);
            $table->text('reason');
            $table->string('ref_type')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['item_batch_id', 'created_at']);
            $table->index(['ref_type', 'ref_id']);
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE TRIGGER item_batches_qty_non_negative_insert BEFORE INSERT ON item_batches WHEN NEW.qty_on_hand < 0 BEGIN SELECT RAISE(ABORT, 'qty_on_hand must be non-negative'); END");
            DB::statement("CREATE TRIGGER item_batches_qty_non_negative_update BEFORE UPDATE OF qty_on_hand ON item_batches WHEN NEW.qty_on_hand < 0 BEGIN SELECT RAISE(ABORT, 'qty_on_hand must be non-negative'); END");

            return;
        }

        DB::statement('ALTER TABLE item_batches ADD CONSTRAINT item_batches_qty_on_hand_non_negative CHECK (qty_on_hand >= 0)');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS item_batches_qty_non_negative_update');
            DB::statement('DROP TRIGGER IF EXISTS item_batches_qty_non_negative_insert');
        }

        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('item_batches');
    }
};
