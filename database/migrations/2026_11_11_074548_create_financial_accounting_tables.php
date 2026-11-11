<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['kas', 'bank']);
            $table->string('account_no', 50)->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['is_active', 'type']);
        });

        Schema::create('transaction_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->enum('direction', ['in', 'out']);
            $table->string('source_code', 30)->nullable()->unique();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['name', 'direction']);
            $table->index(['direction', 'is_active', 'is_system']);
        });

        Schema::create('financial_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('trx_no', 30)->unique();
            $table->date('trx_date');
            $table->foreignId('cash_bank_account_id')->constrained('cash_bank_accounts')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('transaction_categories')->restrictOnDelete();
            $table->enum('direction', ['in', 'out']);
            $table->decimal('amount', 15, 2);
            $table->string('description', 255);
            $table->string('ref_type')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->enum('status', ['posted', 'reversed'])->default('posted');
            $table->foreignId('reverses_id')->nullable()->unique()->constrained('financial_transactions')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['cash_bank_account_id', 'trx_date', 'id']);
            $table->index(['category_id', 'trx_date']);
            $table->index(['ref_type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('transaction_categories');
        Schema::dropIfExists('cash_bank_accounts');
    }
};
