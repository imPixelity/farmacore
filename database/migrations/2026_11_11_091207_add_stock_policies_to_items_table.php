<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_policies', function (Blueprint $table): void {
            $table->id();
            $table->enum('movement_class', ['fast', 'medium', 'slow'])->unique();
            $table->unsignedSmallInteger('min_stock_days')->nullable();
            $table->decimal('safety_factor', 4, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::table('items', function (Blueprint $table): void {
            $table->enum('movement_class', ['fast', 'medium', 'slow'])->nullable()->after('hna_base');
            $table->decimal('min_stock_override', 15, 3)->nullable()->after('movement_class');
            $table->decimal('min_stock', 15, 3)->nullable()->after('min_stock_override');
            $table->decimal('average_monthly_usage', 15, 6)->nullable()->after('min_stock');
            $table->decimal('buffer_stock', 15, 3)->nullable()->after('average_monthly_usage');
            $table->decimal('reorder_point', 15, 3)->nullable()->after('buffer_stock');
            $table->timestamp('stock_policy_recalculated_at')->nullable()->after('reorder_point');
            $table->index(['movement_class', 'is_active'], 'items_stock_policy_idx');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropIndex('items_stock_policy_idx');
            $table->dropColumn([
                'movement_class',
                'min_stock_override',
                'min_stock',
                'average_monthly_usage',
                'buffer_stock',
                'reorder_point',
                'stock_policy_recalculated_at',
            ]);
        });

        Schema::dropIfExists('stock_policies');
    }
};
