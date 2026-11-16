<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('report_key', 64);
            $table->string('format', 8);
            $table->date('from_date');
            $table->date('to_date');
            $table->string('status', 16)->default('queued');
            $table->string('file_path')->nullable();
            $table->string('failure_message')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'report_key', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
