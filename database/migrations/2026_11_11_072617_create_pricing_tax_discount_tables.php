<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->enum('sales_type', ['resep', 'obat_bebas', 'produk_lain']);
            $table->decimal('purchase_price_incl_tax', 15, 4);
            $table->decimal('margin_percent', 6, 2);
            $table->decimal('selling_price', 15, 2);
            $table->dateTime('effective_from');
            $table->boolean('is_current')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['item_id', 'sales_type', 'is_current']);
            $table->index(['sales_type', 'effective_from']);
        });

        Schema::create('margin_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('scope_key', 50);
            $table->enum('scope_type', ['global', 'category', 'item']);
            $table->foreignId('item_id')->nullable()->constrained('items')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('item_categories')->restrictOnDelete();
            $table->enum('sales_type', ['resep', 'obat_bebas', 'produk_lain']);
            $table->decimal('margin_percent', 6, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['scope_key', 'sales_type']);
            $table->index(['item_id', 'sales_type']);
            $table->index(['category_id', 'sales_type']);
        });

        Schema::create('tax_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 30);
            $table->decimal('rate_percent', 5, 2);
            $table->date('effective_from');
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['name', 'effective_from']);
            $table->index(['name', 'effective_from']);
        });

        Schema::create('tax_rules', function (Blueprint $table): void {
            $table->id();
            $table->enum('item_type', ['obat', 'alkes', 'non_obat'])->unique();
            $table->boolean('is_taxable');
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('discount_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->enum('rule_type', ['qty', 'nominal']);
            $table->enum('sales_type', ['resep', 'obat_bebas', 'produk_lain'])->nullable();
            $table->foreignId('item_id')->nullable()->constrained('items')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('item_categories')->restrictOnDelete();
            $table->decimal('min_qty', 15, 3)->nullable();
            $table->decimal('min_amount', 15, 2)->nullable();
            $table->enum('discount_type', ['percent', 'amount']);
            $table->decimal('discount_value', 15, 2);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['is_active', 'effective_from', 'effective_until']);
            $table->index(['item_id', 'category_id', 'sales_type']);
        });

        Schema::create('tax_records', function (Blueprint $table): void {
            $table->id();
            $table->morphs('source');
            $table->enum('direction', ['input', 'output']);
            $table->decimal('rate_percent', 5, 2);
            $table->decimal('taxable_amount', 15, 2);
            $table->decimal('tax_amount', 15, 2);
            $table->date('transaction_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['source_type', 'source_id', 'direction']);
            $table->index(['direction', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_records');
        Schema::dropIfExists('discount_rules');
        Schema::dropIfExists('tax_rules');
        Schema::dropIfExists('tax_settings');
        Schema::dropIfExists('margin_settings');
        Schema::dropIfExists('item_prices');
    }
};
