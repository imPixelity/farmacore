<?php

namespace App\Modules\Rx\Services;

use App\Modules\Md\Models\Doctor;
use App\Modules\Md\Models\Patient;
use App\Modules\Md\Models\PharmacyStaff;
use App\Modules\Rx\Models\Prescription;
use App\Modules\Rx\Models\PrescriptionCompounding;
use App\Modules\Rx\Models\PrescriptionDispense;
use App\Modules\Rx\Models\PrescriptionItem;
use App\Modules\Rx\Models\PrescriptionScreening;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrescriptionWorkflowService
{
    public const CHECKLIST_KEYS = ['patient_match', 'dose_safe', 'interaction_checked'];

    /** @param array{patient_id: int|string, doctor_id: int|string, rx_date: string, rx_group: string, notes?: string|null, items: array<int, array{item_id: int|string, unit_id: int|string, qty_prescribed: string|int|float, dosage_instruction: string, is_compound?: bool|int, compound_group?: string|null}>} $data */
    public function create(array $data): Prescription
    {
        return DB::transaction(function () use ($data): Prescription {
            $patient = Patient::query()->lockForUpdate()->findOrFail((int) $data['patient_id']);
            $doctor = Doctor::query()->lockForUpdate()->findOrFail((int) $data['doctor_id']);
            if (! $patient->is_active) {
                throw ValidationException::withMessages(['patient_id' => 'Pasien sudah nonaktif.']);
            }
            if (! $doctor->is_active) {
                throw ValidationException::withMessages(['doctor_id' => 'Dokter sudah nonaktif.']);
            }
            if ($data['items'] === []) {
                throw ValidationException::withMessages(['items' => 'Minimal satu obat harus dicatat dalam resep.']);
            }

            $prescription = new Prescription;
            $prescription->fill([
                'rx_no' => 'RX-'.Str::ulid(),
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'rx_date' => $data['rx_date'],
                'rx_group' => $data['rx_group'],
                'status' => 'received',
                'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $prescription->save();

            foreach ($data['items'] as $line) {
                $quantity = $this->quantity((string) $line['qty_prescribed'], 'qty_prescribed');
                $item = new PrescriptionItem;
                $item->fill([
                    'prescription_id' => $prescription->id,
                    'item_id' => $line['item_id'],
                    'unit_id' => $line['unit_id'],
                    'qty_prescribed' => $quantity,
                    'dosage_instruction' => trim($line['dosage_instruction']),
                    'is_compound' => (bool) ($line['is_compound'] ?? false),
                    'compound_group' => filled($line['compound_group'] ?? null) ? trim((string) $line['compound_group']) : null,
                ]);
                $item->save();
            }

            return $prescription->fresh(['patient', 'doctor', 'items.item', 'items.unit']);
        });
    }

    /** @param array<string, mixed> $payload */
    public function transition(Prescription $prescription, string $target, array $payload = []): Prescription
    {
        return DB::transaction(function () use ($prescription, $target, $payload): Prescription {
            $rx = Prescription::query()->with('items')->lockForUpdate()->findOrFail($prescription->id);
            $this->assertTransition($rx->status, $target);

            if ($target === 'received') {
                return $this->setStatus($rx, 'received');
            }

            $staff = $this->currentStaff();
            if ($target === 'verified' || $target === 'rejected') {
                $checklist = $this->checklist($payload['checklist'] ?? null);
                $result = $target === 'verified' ? 'disetujui' : 'ditolak';
                $screening = new PrescriptionScreening;
                $screening->fill([
                    'prescription_id' => $rx->id,
                    'checklist' => $checklist,
                    'result' => $result,
                    'notes' => filled($payload['notes'] ?? null) ? trim((string) $payload['notes']) : null,
                    'pharmacy_staff_id' => $staff->id,
                    'performed_at' => now(),
                ]);
                $screening->save();

                return $this->setStatus($rx, $target);
            }

            if ($target === 'prepared') {
                $this->prepare($rx, $staff, $payload);

                return $this->setStatus($rx, 'prepared');
            }

            if ($target === 'dispensed') {
                $receivedByName = trim((string) ($payload['received_by_name'] ?? ''));
                if ($receivedByName === '' || mb_strlen($receivedByName) > 150) {
                    throw ValidationException::withMessages(['received_by_name' => 'Nama penerima obat wajib diisi.']);
                }
                $dispense = new PrescriptionDispense;
                $dispense->fill([
                    'prescription_id' => $rx->id,
                    'status' => 'diserahkan',
                    'received_by_name' => $receivedByName,
                    'pharmacy_staff_id' => $staff->id,
                    'dispensed_at' => now(),
                ]);
                $dispense->save();

                return $this->setStatus($rx, 'dispensed');
            }

            if ($target === 'cancelled') {
                if (filled($payload['notes'] ?? null)) {
                    $rx->forceFill(['notes' => trim((string) $payload['notes'])])->save();
                }

                return $this->setStatus($rx, 'cancelled');
            }

            throw ValidationException::withMessages(['status' => 'Tahap pelayanan resep tidak dikenali.']);
        });
    }

    /** @return Collection<int, Prescription> */
    public function queue(?string $status = null): Collection
    {
        return Prescription::query()
            ->with(['patient:id,code,name', 'doctor:id,name', 'creator:id,name'])
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->withCount('items')
            ->orderByDesc('rx_date')
            ->orderByDesc('id')
            ->get();
    }

    private function assertTransition(string $current, string $target): void
    {
        $allowed = [
            'received' => ['verified', 'rejected', 'cancelled'],
            'rejected' => ['received'],
            'verified' => ['prepared'],
            'prepared' => ['dispensed'],
        ];

        if (! in_array($target, $allowed[$current] ?? [], true)) {
            throw ValidationException::withMessages(['status' => "Resep berstatus {$current} tidak dapat langsung masuk tahap {$target}."]);
        }
    }

    private function setStatus(Prescription $prescription, string $status): Prescription
    {
        Prescription::duringWorkflow(function () use ($prescription, $status): void {
            $prescription->forceFill(['status' => $status, 'updated_by' => Auth::id()])->save();
        });

        return $prescription->fresh(['patient', 'doctor', 'items.item', 'items.unit', 'screenings.pharmacyStaff', 'compoundings.pharmacyStaff', 'dispense.pharmacyStaff']);
    }

    /** @param array<string, mixed> $payload */
    private function prepare(Prescription $prescription, PharmacyStaff $staff, array $payload): void
    {
        $preparedItems = $payload['prepared_items'] ?? null;
        if (! is_array($preparedItems)) {
            throw ValidationException::withMessages(['prepared_items' => 'Hasil penyiapan obat wajib diisi.']);
        }

        foreach ($prescription->items as $item) {
            $value = $preparedItems[$item->id] ?? null;
            if ($value === null) {
                throw ValidationException::withMessages(['prepared_items' => 'Semua obat resep harus memiliki jumlah disiapkan.']);
            }
            $item->forceFill(['prepared_qty' => $this->quantity((string) $value, 'prepared_items')])->save();
        }

        if ($prescription->items->contains(fn (PrescriptionItem $item): bool => $item->is_compound)) {
            $compoundings = $payload['compoundings'] ?? null;
            if (! is_array($compoundings) || $compoundings === []) {
                throw ValidationException::withMessages(['compoundings' => 'Hasil racikan wajib dicatat untuk resep racikan.']);
            }
            foreach ($compoundings as $compounding) {
                $name = trim((string) ($compounding['compound_name'] ?? ''));
                $unit = trim((string) ($compounding['yield_unit'] ?? ''));
                if ($name === '' || $unit === '') {
                    throw ValidationException::withMessages(['compoundings' => 'Nama dan satuan hasil racikan wajib diisi.']);
                }
                $record = new PrescriptionCompounding;
                $record->fill([
                    'prescription_id' => $prescription->id,
                    'compound_name' => $name,
                    'yield_qty' => $this->quantity((string) ($compounding['yield_qty'] ?? '0'), 'yield_qty'),
                    'yield_unit' => $unit,
                    'pharmacy_staff_id' => $staff->id,
                    'performed_at' => now(),
                ]);
                $record->save();
            }
        }
    }

    /** @return array<string, bool> */
    private function checklist(mixed $checklist): array
    {
        if (! is_array($checklist) || array_diff(self::CHECKLIST_KEYS, array_keys($checklist)) !== []) {
            throw ValidationException::withMessages(['checklist' => 'Checklist screening harus diisi lengkap.']);
        }

        return collect(self::CHECKLIST_KEYS)->mapWithKeys(fn (string $key): array => [$key => (bool) $checklist[$key]])->all();
    }

    private function currentStaff(): PharmacyStaff
    {
        $staff = PharmacyStaff::query()->where('user_id', Auth::id())->active()->first();
        if (! $staff) {
            throw ValidationException::withMessages(['staff' => 'Akun login belum memiliki data tenaga farmasi aktif.']);
        }

        return $staff;
    }

    private function quantity(string $quantity, string $field): string
    {
        if (! preg_match('/^\d+(?:\.\d{1,3})?$/', $quantity) || bccomp($quantity, '0', 3) <= 0 || bccomp($quantity, '999999999999.999', 3) > 0) {
            throw ValidationException::withMessages([$field => 'Jumlah harus positif dengan maksimal tiga angka desimal.']);
        }

        return bcadd($quantity, '0', 3);
    }
}
