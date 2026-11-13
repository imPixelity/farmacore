<?php

namespace App\Modules\Rx\Services;

use App\Models\AuditLog;
use App\Modules\Md\Models\Patient;
use App\Modules\Md\Models\PharmacyStaff;
use App\Modules\Pos\Models\Sale;
use App\Modules\Rx\Models\PrescriptionCompounding;
use App\Modules\Rx\Models\PrescriptionDispense;
use App\Modules\Rx\Models\PrescriptionScreening;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class PatientHistoryService
{
    /** @return array{patient: Patient, timeline: array<int, array{date: CarbonImmutable|string, type: string, title: string, description: string}>} */
    public function forPatient(Patient $patient): array
    {
        $patient->load([
            'creator:id,name',
        ]);

        $prescriptions = $patient->prescriptions()
            ->with(['doctor:id,name', 'items.item:id,name,code', 'screenings.pharmacyStaff:id,name', 'compoundings.pharmacyStaff:id,name', 'dispense.pharmacyStaff:id,name'])
            ->orderByDesc('rx_date')
            ->orderByDesc('id')
            ->get();
        $timeline = [];

        $sales = Sale::query()
            ->whereBelongsTo($patient)
            ->with(['items.item:id,name', 'services'])
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->get();
        foreach ($sales as $sale) {
            $timeline[] = [
                'date' => $sale->sale_date,
                'type' => 'penjualan',
                'title' => 'Penjualan '.$sale->invoice_no,
                'description' => 'Total '.$sale->grand_total.' · status '.$sale->status,
            ];
            foreach ($sale->services as $service) {
                $timeline[] = [
                    'date' => $sale->sale_date,
                    'type' => 'pemeriksaan',
                    'title' => $service->service_name,
                    'description' => 'Biaya '.$service->fee.($service->result_note ? ' · '.$service->result_note : ''),
                ];
            }
        }

        foreach ($prescriptions as $prescription) {
            $timeline[] = [
                'date' => $prescription->rx_date,
                'type' => 'resep',
                'title' => 'Resep '.$prescription->rx_no,
                'description' => 'Dr. '.$prescription->doctor->name.' · status '.str_replace('_', ' ', $prescription->status),
            ];

            foreach ($prescription->items as $item) {
                $timeline[] = [
                    'date' => $prescription->rx_date,
                    'type' => 'obat',
                    'title' => $item->item->name,
                    'description' => 'Jumlah '.$item->qty_prescribed.' · '.$item->dosage_instruction,
                ];
            }

            foreach ($prescription->screenings as $screening) {
                $timeline[] = [
                    'date' => $screening->performed_at,
                    'type' => 'screening',
                    'title' => 'Screening '.$screening->result,
                    'description' => 'Oleh '.$screening->pharmacyStaff->name,
                ];
            }

            foreach ($prescription->compoundings as $compounding) {
                $timeline[] = [
                    'date' => $compounding->performed_at,
                    'type' => 'peracikan',
                    'title' => 'Racikan '.$compounding->compound_name,
                    'description' => $compounding->yield_qty.' '.$compounding->yield_unit.' · Oleh '.$compounding->pharmacyStaff->name,
                ];
            }

            if ($prescription->dispense) {
                $timeline[] = [
                    'date' => $prescription->dispense->dispensed_at,
                    'type' => 'penyerahan',
                    'title' => 'Obat diserahkan',
                    'description' => 'Penerima '.$prescription->dispense->received_by_name.' · Oleh '.$prescription->dispense->pharmacyStaff->name,
                ];
            }
        }

        usort($timeline, static fn (array $left, array $right): int => CarbonImmutable::parse($right['date'])->getTimestamp() <=> CarbonImmutable::parse($left['date'])->getTimestamp());

        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'event' => 'viewed',
            'auditable_type' => Patient::class,
            'auditable_id' => $patient->id,
            'new_values' => ['action' => 'patient_history_viewed'],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        return ['patient' => $patient, 'timeline' => $timeline];
    }

    /** @return array{staff: Collection<int, PharmacyStaff>, activities: array<int, array{date: CarbonImmutable|string, staff: PharmacyStaff, type: string, title: string, description: string}>} */
    public function staffActivity(?int $staffId = null, ?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? CarbonImmutable::parse($from)->startOfDay() : null;
        $toDate = $to ? CarbonImmutable::parse($to)->endOfDay() : null;
        $activities = [];

        $screenings = PrescriptionScreening::query()->with(['pharmacyStaff:id,name', 'prescription:id,rx_no,patient_id', 'prescription.patient:id,name'])
            ->when($staffId, fn (Builder $query) => $query->where('pharmacy_staff_id', $staffId))
            ->when($fromDate, fn (Builder $query) => $query->where('performed_at', '>=', $fromDate))
            ->when($toDate, fn (Builder $query) => $query->where('performed_at', '<=', $toDate))
            ->get();
        foreach ($screenings as $screening) {
            $activities[] = ['date' => $screening->performed_at, 'staff' => $screening->pharmacyStaff, 'type' => 'screening', 'title' => 'Screening '.$screening->prescription->rx_no, 'description' => $screening->result.' · '.$screening->prescription->patient->name];
        }

        $compoundings = PrescriptionCompounding::query()->with(['pharmacyStaff:id,name', 'prescription:id,rx_no,patient_id', 'prescription.patient:id,name'])
            ->when($staffId, fn (Builder $query) => $query->where('pharmacy_staff_id', $staffId))
            ->when($fromDate, fn (Builder $query) => $query->where('performed_at', '>=', $fromDate))
            ->when($toDate, fn (Builder $query) => $query->where('performed_at', '<=', $toDate))
            ->get();
        foreach ($compoundings as $compounding) {
            $activities[] = ['date' => $compounding->performed_at, 'staff' => $compounding->pharmacyStaff, 'type' => 'peracikan', 'title' => 'Peracikan '.$compounding->compound_name, 'description' => $compounding->prescription->rx_no.' · '.$compounding->prescription->patient->name];
        }

        $dispenses = PrescriptionDispense::query()->with(['pharmacyStaff:id,name', 'prescription:id,rx_no,patient_id', 'prescription.patient:id,name'])
            ->when($staffId, fn (Builder $query) => $query->where('pharmacy_staff_id', $staffId))
            ->when($fromDate, fn (Builder $query) => $query->where('dispensed_at', '>=', $fromDate))
            ->when($toDate, fn (Builder $query) => $query->where('dispensed_at', '<=', $toDate))
            ->get();
        foreach ($dispenses as $dispense) {
            $activities[] = ['date' => $dispense->dispensed_at, 'staff' => $dispense->pharmacyStaff, 'type' => 'penyerahan', 'title' => 'Penyerahan '.$dispense->prescription->rx_no, 'description' => $dispense->prescription->patient->name.' · Penerima '.$dispense->received_by_name];
        }

        usort($activities, static fn (array $left, array $right): int => CarbonImmutable::parse($right['date'])->getTimestamp() <=> CarbonImmutable::parse($left['date'])->getTimestamp());

        return [
            'staff' => PharmacyStaff::query()->active()->orderBy('name')->get(['id', 'name']),
            'activities' => $activities,
        ];
    }
}
