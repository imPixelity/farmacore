<?php

namespace App\Http\Controllers\Md;

use App\Http\Controllers\Controller;
use App\Http\Requests\Md\DoctorRequest;
use App\Modules\Md\Models\Doctor;
use App\Modules\Md\Services\PartnerMasterDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('md.item.view');

        $doctors = Doctor::query()
            ->when($request->filled('search'), fn ($query) => $query->whereLike('name', '%'.trim($request->string('search')->toString()).'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->input('status') === 'active'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('md.doctors.index', compact('doctors'));
    }

    public function create(): View
    {
        Gate::authorize('md.item.manage');

        return view('md.doctors.create', ['doctor' => null]);
    }

    public function store(DoctorRequest $request, PartnerMasterDataService $service): RedirectResponse
    {
        $doctor = $service->saveDoctor($request->validated());

        return redirect()->route('md.doctors.edit', $doctor)->with('success', 'Dokter berhasil dibuat.');
    }

    public function edit(Doctor $doctor): View
    {
        Gate::authorize('md.item.manage');

        return view('md.doctors.edit', compact('doctor'));
    }

    public function update(DoctorRequest $request, Doctor $doctor, PartnerMasterDataService $service): RedirectResponse
    {
        $service->saveDoctor($request->validated(), $doctor);

        return redirect()->route('md.doctors.edit', $doctor)->with('success', 'Data dokter berhasil diperbarui.');
    }

    public function updateStatus(Doctor $doctor, PartnerMasterDataService $service): RedirectResponse
    {
        Gate::authorize('md.item.manage');
        $service->setDoctorActive($doctor, ! $doctor->is_active);

        return redirect()->route('md.doctors.index')->with('success', 'Status dokter berhasil diperbarui.');
    }
}
