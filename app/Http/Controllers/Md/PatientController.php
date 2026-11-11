<?php

namespace App\Http\Controllers\Md;

use App\Http\Controllers\Controller;
use App\Http\Requests\Md\PatientRequest;
use App\Modules\Md\Models\Patient;
use App\Modules\Md\Services\PartnerMasterDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('md.item.view');
        $canViewSensitiveData = $request->user()->can('md.patient.view_sensitive');
        $search = trim($request->string('search')->toString());

        $patients = Patient::query()
            ->when($search !== '', function ($query) use ($search, $canViewSensitiveData): void {
                $query->where(function ($query) use ($search, $canViewSensitiveData): void {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhere('code', $search);

                    if ($canViewSensitiveData) {
                        $query->orWhere('identity_no', $search);
                    }
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->input('status') === 'active'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('md.patients.index', compact('patients', 'canViewSensitiveData'));
    }

    public function create(): View
    {
        Gate::authorize('md.item.manage');

        return view('md.patients.create', ['patient' => null]);
    }

    public function store(PatientRequest $request, PartnerMasterDataService $service): RedirectResponse
    {
        $patient = $service->savePatient($request->validated());

        return redirect()->route('md.patients.edit', $patient)->with('success', 'Pasien berhasil dibuat.');
    }

    public function edit(Patient $patient): View
    {
        Gate::authorize('md.item.manage');

        return view('md.patients.edit', compact('patient'));
    }

    public function update(PatientRequest $request, Patient $patient, PartnerMasterDataService $service): RedirectResponse
    {
        $service->savePatient($request->validated(), $patient);

        return redirect()->route('md.patients.edit', $patient)->with('success', 'Data pasien berhasil diperbarui.');
    }

    public function updateStatus(Patient $patient, PartnerMasterDataService $service): RedirectResponse
    {
        Gate::authorize('md.item.manage');
        $service->setPatientActive($patient, ! $patient->is_active);

        return redirect()->route('md.patients.index')->with('success', 'Status pasien berhasil diperbarui.');
    }
}
