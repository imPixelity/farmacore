<?php

namespace App\Http\Controllers\Md;

use App\Http\Controllers\Controller;
use App\Http\Requests\Md\PharmacyStaffRequest;
use App\Models\User;
use App\Modules\Md\Models\PharmacyStaff;
use App\Modules\Md\Services\PartnerMasterDataService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PharmacyStaffController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('md.item.view');

        $pharmacyStaff = PharmacyStaff::query()
            ->with('user:id,username,name')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.trim($request->string('search')->toString()).'%';
                $query->where(function ($query) use ($search): void {
                    $query->whereLike('name', $search)
                        ->orWhereLike('position', $search)
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->whereLike('username', $search));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->input('status') === 'active'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('md.pharmacy-staff.index', compact('pharmacyStaff'));
    }

    public function create(): View
    {
        Gate::authorize('md.item.manage');

        return view('md.pharmacy-staff.create', [
            'pharmacyStaff' => null,
            'eligibleUsers' => $this->eligibleUsers(),
        ]);
    }

    public function store(PharmacyStaffRequest $request, PartnerMasterDataService $service): RedirectResponse
    {
        $pharmacyStaff = $service->savePharmacyStaff($request->validated());

        return redirect()->route('md.pharmacy-staff.edit', $pharmacyStaff)->with('success', 'Tenaga farmasi berhasil dibuat.');
    }

    public function edit(PharmacyStaff $pharmacyStaff): View
    {
        Gate::authorize('md.item.manage');

        return view('md.pharmacy-staff.edit', [
            'pharmacyStaff' => $pharmacyStaff->load('user'),
            'eligibleUsers' => $this->eligibleUsers(),
        ]);
    }

    public function update(
        PharmacyStaffRequest $request,
        PharmacyStaff $pharmacyStaff,
        PartnerMasterDataService $service,
    ): RedirectResponse {
        $service->savePharmacyStaff($request->validated(), $pharmacyStaff);

        return redirect()->route('md.pharmacy-staff.edit', $pharmacyStaff)->with('success', 'Data tenaga farmasi berhasil diperbarui.');
    }

    public function updateStatus(PharmacyStaff $pharmacyStaff, PartnerMasterDataService $service): RedirectResponse
    {
        Gate::authorize('md.item.manage');
        $service->setPharmacyStaffActive($pharmacyStaff, ! $pharmacyStaff->is_active);

        return redirect()->route('md.pharmacy-staff.index')->with('success', 'Status tenaga farmasi berhasil diperbarui.');
    }

    /** @return Collection<int, User> */
    private function eligibleUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'tenaga_farmasi')->where('guard_name', 'web'))
            ->orderBy('name')
            ->get(['id', 'username', 'name']);
    }
}
