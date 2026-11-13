<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receivables', function (Blueprint $table): void {
            $table->dateTime('cancelled_at')->nullable()->after('status');
        });

        Schema::create('sale_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('health_service_id')->nullable()->constrained('health_services')->restrictOnDelete();
            $table->string('service_name', 100);
            $table->decimal('fee', 15, 2);
            $table->text('result_note')->nullable();
            $table->timestamps();
            $table->index(['sale_id', 'health_service_id']);
        });

        Schema::create('sale_returns', function (Blueprint $table): void {
            $table->id();
            $table->string('return_no', 30)->unique();
            $table->foreignId('sale_id')->constrained('sales')->restrictOnDelete();
            $table->foreignId('processed_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('return_date');
            $table->string('reason', 255);
            $table->decimal('refund_amount', 15, 2);
            $table->boolean('restock')->default(false);
            $table->timestamps();
            $table->index(['sale_id', 'return_date']);
        });

        Schema::create('sale_return_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained('sale_items')->restrictOnDelete();
            $table->foreignId('item_batch_id')->nullable()->constrained('item_batches')->restrictOnDelete();
            $table->decimal('qty_base', 15, 3);
            $table->decimal('unit_price_base', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
            $table->index(['sale_item_id', 'item_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
        Schema::dropIfExists('sale_services');
        Schema::table('receivables', function (Blueprint $table): void {
            $table->dropColumn('cancelled_at');
        });
    }
};
