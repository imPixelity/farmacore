<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('rx_no', 30)->unique();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->date('rx_date');
            $table->enum('rx_group', ['dokter_apotek', 'dokter_luar', 'bpjs']);
            $table->enum('status', ['received', 'verified', 'rejected', 'prepared', 'dispensed', 'cancelled'])->default('received');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['status', 'rx_date']);
            $table->index(['patient_id', 'rx_date']);
        });

        Schema::create('prescription_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('qty_prescribed', 15, 3);
            $table->string('dosage_instruction', 255);
            $table->boolean('is_compound')->default(false);
            $table->string('compound_group', 30)->nullable();
            $table->decimal('prepared_qty', 15, 3)->nullable();
            $table->timestamps();
            $table->index(['prescription_id', 'is_compound']);
        });

        Schema::create('prescription_screenings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->restrictOnDelete();
            $table->json('checklist');
            $table->enum('result', ['disetujui', 'ditolak']);
            $table->text('notes')->nullable();
            $table->foreignId('pharmacy_staff_id')->constrained('pharmacy_staff')->restrictOnDelete();
            $table->timestamp('performed_at');
            $table->timestamps();
            $table->index(['prescription_id', 'performed_at']);
        });

        Schema::create('prescription_compoundings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->restrictOnDelete();
            $table->string('compound_name', 100);
            $table->decimal('yield_qty', 15, 3);
            $table->string('yield_unit', 30);
            $table->foreignId('pharmacy_staff_id')->constrained('pharmacy_staff')->restrictOnDelete();
            $table->timestamp('performed_at');
            $table->timestamps();
            $table->index(['prescription_id', 'performed_at']);
        });

        Schema::create('prescription_dispenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prescription_id')->unique()->constrained('prescriptions')->restrictOnDelete();
            $table->enum('status', ['diserahkan', 'belum'])->default('belum');
            $table->string('received_by_name', 150);
            $table->foreignId('pharmacy_staff_id')->constrained('pharmacy_staff')->restrictOnDelete();
            $table->timestamp('dispensed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_dispenses');
        Schema::dropIfExists('prescription_compoundings');
        Schema::dropIfExists('prescription_screenings');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
    }
};
