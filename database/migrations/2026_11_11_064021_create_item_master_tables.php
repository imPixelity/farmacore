<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['obat', 'non_obat']);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['type', 'name']);
            $table->index(['is_active', 'type']);
        });

        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 30)->unique();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index('is_active');
        });

        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->enum('item_type', ['obat', 'alkes', 'non_obat']);
            $table->string('name', 150);
            $table->string('dosage_form', 50)->nullable();
            $table->string('strength_volume', 50)->nullable();
            $table->foreignId('category_id')->constrained('item_categories')->restrictOnDelete();
            $table->foreignId('base_unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('order_unit_id')->constrained('units')->restrictOnDelete();
            $table->string('barcode', 50)->nullable()->unique();
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['is_active', 'category_id']);
            $table->index(['name', 'code']);
        });

        Schema::create('item_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('factor_to_base', 15, 3);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['item_id', 'unit_id']);
            $table->index('unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_units');
        Schema::dropIfExists('items');
        Schema::dropIfExists('units');
        Schema::dropIfExists('item_categories');
    }
};
