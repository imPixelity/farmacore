<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payable_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payable_id')->constrained('payables')->restrictOnDelete();
            $table->foreignId('cash_bank_account_id')->constrained('cash_bank_accounts')->restrictOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['payable_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payable_payments');
    }
};
