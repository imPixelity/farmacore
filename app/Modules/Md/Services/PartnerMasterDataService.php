<?php

namespace App\Modules\Md\Services;

use App\Models\User;
use App\Modules\Md\Models\Doctor;
use App\Modules\Md\Models\HealthService;
use App\Modules\Md\Models\Patient;
use App\Modules\Md\Models\PharmacyStaff;
use App\Modules\Md\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartnerMasterDataService
{
    /** @param array<string, mixed> $data */
    public function saveSupplier(array $data, ?Supplier $supplier = null): Supplier
    {
        return DB::transaction(function () use ($data, $supplier): Supplier {
            if ($supplier?->exists) {
                $supplier = Supplier::query()->lockForUpdate()->findOrFail($supplier->id);
            } else {
                $supplier = new Supplier;
            }

            $supplier->fill(Arr::only($data, [
                'name', 'address', 'phone', 'email', 'contact_person', 'payment_terms',
                'payment_term_days', 'lead_time_days', 'is_active',
            ]));
            $this->stamp($supplier);
            $supplier->save();

            return $supplier;
        });
    }

    public function setSupplierActive(Supplier $supplier, bool $isActive): Supplier
    {
        return $this->setActive($supplier, $isActive);
    }

    /** @param array<string, mixed> $data */
    public function savePatient(array $data, ?Patient $patient = null): Patient
    {
        return DB::transaction(function () use ($data, $patient): Patient {
            $isExisting = $patient?->exists ?? false;

            if ($isExisting) {
                $patient = Patient::query()->lockForUpdate()->findOrFail($patient->id);
            } else {
                $patient = new Patient;
            }

            $previousSensitiveValues = Arr::only($patient->getAttributes(), ['identity_no', 'phone', 'address', 'birth_date']);
            $changedSensitiveFields = [];

            if ($isExisting) {
                foreach (['identity_no', 'phone', 'address', 'birth_date'] as $field) {
                    if (array_key_exists($field, $data) && $this->sensitiveValueChanged($field, $previousSensitiveValues[$field] ?? null, $data[$field])) {
                        $changedSensitiveFields[] = $field;
                    }
                }
            }

            $patient->fill(Arr::only($data, [
                'code', 'name', 'identity_no', 'phone', 'address', 'birth_date', 'is_active',
            ]));
            $this->stamp($patient);
            $patient->save();

            if ($changedSensitiveFields !== []) {
                $patient->recordSensitiveDataChange($changedSensitiveFields);
            }

            return $patient;
        });
    }

    public function setPatientActive(Patient $patient, bool $isActive): Patient
    {
        return $this->setActive($patient, $isActive);
    }

    /** @param array<string, mixed> $data */
    public function saveDoctor(array $data, ?Doctor $doctor = null): Doctor
    {
        return DB::transaction(function () use ($data, $doctor): Doctor {
            if ($doctor?->exists) {
                $doctor = Doctor::query()->lockForUpdate()->findOrFail($doctor->id);
            } else {
                $doctor = new Doctor;
            }

            $doctor->fill(Arr::only($data, ['name', 'identity_no', 'phone', 'origin', 'is_active']));
            $this->stamp($doctor);
            $doctor->save();

            return $doctor;
        });
    }

    public function setDoctorActive(Doctor $doctor, bool $isActive): Doctor
    {
        return $this->setActive($doctor, $isActive);
    }

    /** @param array<string, mixed> $data */
    public function savePharmacyStaff(array $data, ?PharmacyStaff $pharmacyStaff = null): PharmacyStaff
    {
        return DB::transaction(function () use ($data, $pharmacyStaff): PharmacyStaff {
            $user = User::query()->lockForUpdate()->find($data['user_id']);

            if (! $user || ! $user->is_active || ! $user->hasRole('tenaga_farmasi')) {
                throw ValidationException::withMessages([
                    'user_id' => 'Pilih akun aktif dengan peran tenaga farmasi.',
                ]);
            }

            if ($pharmacyStaff?->exists) {
                $pharmacyStaff = PharmacyStaff::query()->lockForUpdate()->findOrFail($pharmacyStaff->id);
            } else {
                $pharmacyStaff = new PharmacyStaff;
            }

            $pharmacyStaff->fill(Arr::only($data, ['user_id', 'name', 'position', 'is_active']));
            $this->stamp($pharmacyStaff);
            $pharmacyStaff->save();

            return $pharmacyStaff;
        });
    }

    public function setPharmacyStaffActive(PharmacyStaff $pharmacyStaff, bool $isActive): PharmacyStaff
    {
        return $this->setActive($pharmacyStaff, $isActive);
    }

    /** @param array<string, mixed> $data */
    public function saveHealthService(array $data, ?HealthService $healthService = null): HealthService
    {
        return DB::transaction(function () use ($data, $healthService): HealthService {
            if ($healthService?->exists) {
                $healthService = HealthService::query()->lockForUpdate()->findOrFail($healthService->id);
            } else {
                $healthService = new HealthService;
            }

            $healthService->fill(Arr::only($data, ['name', 'default_fee', 'is_active']));
            $this->stamp($healthService);
            $healthService->save();

            return $healthService;
        });
    }

    public function setHealthServiceActive(HealthService $healthService, bool $isActive): HealthService
    {
        return $this->setActive($healthService, $isActive);
    }

    private function sensitiveValueChanged(string $field, mixed $previousValue, mixed $newValue): bool
    {
        if ($field === 'birth_date' && $previousValue !== null && $newValue !== null) {
            return Carbon::parse($previousValue)->toDateString() !== Carbon::parse($newValue)->toDateString();
        }

        return $previousValue !== $newValue;
    }

    private function stamp(Model $model): void
    {
        $model->setAttribute('updated_by', Auth::id());

        if (! $model->exists) {
            $model->setAttribute('created_by', Auth::id());
        }
    }

    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @return TModel
     */
    private function setActive(Model $model, bool $isActive): Model
    {
        return DB::transaction(function () use ($model, $isActive): Model {
            $record = $model->newQuery()->lockForUpdate()->findOrFail($model->getKey());
            $record->forceFill(['is_active' => $isActive, 'updated_by' => Auth::id()])->save();

            return $record;
        });
    }
}
