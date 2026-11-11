<?php

namespace App\Http\Controllers\Rx;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rx\StorePrescriptionRequest;
use App\Http\Requests\Rx\TransitionPrescriptionRequest;
use App\Modules\Md\Models\Doctor;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\Patient;
use App\Modules\Rx\Models\Prescription;
use App\Modules\Rx\Services\PrescriptionWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PrescriptionController extends Controller
{
    public function index(Request $request, PrescriptionWorkflowService $workflow): View
    {
        Gate::authorize('rx.prescription.manage');

        return view('rx.prescriptions.index', ['prescriptions' => $workflow->queue($request->string('status')->toString() ?: null)]);
    }

    public function create(): View
    {
        Gate::authorize('rx.prescription.manage');

        return view('rx.prescriptions.create', [
            'patients' => Patient::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'doctors' => Doctor::query()->active()->orderBy('name')->get(['id', 'name']),
            'items' => Item::query()->active()->with(['baseUnit:id,name'])->orderBy('name')->get(['id', 'name', 'code', 'base_unit_id']),
        ]);
    }

    public function store(StorePrescriptionRequest $request, PrescriptionWorkflowService $workflow): RedirectResponse
    {
        $prescription = $workflow->create($request->validated());

        return redirect()->route('rx.prescriptions.show', $prescription)->with('success', 'Resep berhasil dicatat dengan status received.');
    }

    public function show(Prescription $prescription): View
    {
        Gate::authorize('rx.prescription.manage');
        $prescription->load(['patient', 'doctor', 'items.item', 'items.unit', 'screenings.pharmacyStaff', 'compoundings.pharmacyStaff', 'dispense.pharmacyStaff']);

        return view('rx.prescriptions.show', ['prescription' => $prescription]);
    }

    public function transition(TransitionPrescriptionRequest $request, Prescription $prescription, PrescriptionWorkflowService $workflow): RedirectResponse
    {
        $workflow->transition($prescription, $request->validated('target'), $request->validated());

        return back()->with('success', 'Tahap pelayanan resep berhasil diperbarui.');
    }
}
