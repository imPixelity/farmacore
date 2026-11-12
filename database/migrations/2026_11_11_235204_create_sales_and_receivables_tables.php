<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_no', 30)->unique();
            $table->enum('sale_type', ['resep', 'obat_bebas', 'produk_lain']);
            $table->dateTime('sale_date');
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->restrictOnDelete();
            $table->foreignId('prescription_id')->nullable()->constrained('prescriptions')->restrictOnDelete();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('doctor_fee', 15, 2)->default(0);
            $table->decimal('tuslah_fee', 15, 2)->default(0);
            $table->decimal('service_fee_total', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2);
            $table->enum('payment_status', ['lunas', 'sebagian', 'belum_dibayar'])->default('belum_dibayar');
            $table->enum('status', ['completed', 'cancelled'])->default('completed');
            $table->string('cancel_reason', 255)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['sale_type', 'sale_date']);
            $table->index(['patient_id', 'sale_date']);
            $table->index(['prescription_id', 'status']);
        });

        Schema::create('sale_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('qty_input', 15, 3);
            $table->decimal('qty_base', 15, 3);
            $table->decimal('unit_price_base', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
            $table->index(['sale_id', 'item_id']);
        });

        Schema::create('sale_item_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnDelete();
            $table->foreignId('item_batch_id')->constrained('item_batches')->restrictOnDelete();
            $table->decimal('qty_base', 15, 3);
            $table->timestamps();
            $table->unique(['sale_item_id', 'item_batch_id']);
        });

        Schema::create('sale_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->enum('method', ['tunai', 'non_tunai', 'kredit']);
            $table->string('channel', 30)->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('cash_received', 15, 2)->default(0);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->foreignId('cash_bank_account_id')->nullable()->constrained('cash_bank_accounts')->restrictOnDelete();
            $table->dateTime('paid_at');
            $table->timestamps();
            $table->index(['sale_id', 'method']);
        });

        Schema::create('receivables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->unique()->constrained('sales')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->enum('status', ['belum_dibayar', 'sebagian', 'lunas'])->default('belum_dibayar');
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['patient_id', 'status']);
        });

        Schema::create('receivable_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('receivable_id')->constrained('receivables')->restrictOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->foreignId('cash_bank_account_id')->constrained('cash_bank_accounts')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['receivable_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivable_payments');
        Schema::dropIfExists('receivables');
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_item_batches');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
