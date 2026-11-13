<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_obligations', function (Blueprint $table): void {
            $table->id();
            $table->string('tax_type', 30);
            $table->char('period', 7);
            $table->decimal('amount_due', 15, 2);
            $table->date('due_date')->nullable();
            $table->enum('status', ['belum', 'dibayar'])->default('belum');
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['tax_type', 'period']);
            $table->index(['period', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_obligations');
    }
};
