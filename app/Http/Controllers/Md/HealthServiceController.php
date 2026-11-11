<?php

namespace App\Http\Controllers\Md;

use App\Http\Controllers\Controller;
use App\Http\Requests\Md\HealthServiceRequest;
use App\Modules\Md\Models\HealthService;
use App\Modules\Md\Services\PartnerMasterDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class HealthServiceController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('md.item.view');

        $healthServices = HealthService::query()
            ->when($request->filled('search'), fn ($query) => $query->whereLike('name', '%'.trim($request->string('search')->toString()).'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->input('status') === 'active'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('md.health-services.index', compact('healthServices'));
    }

    public function create(): View
    {
        Gate::authorize('md.item.manage');

        return view('md.health-services.create', ['healthService' => null]);
    }

    public function store(HealthServiceRequest $request, PartnerMasterDataService $service): RedirectResponse
    {
        $healthService = $service->saveHealthService($request->validated());

        return redirect()->route('md.health-services.edit', $healthService)->with('success', 'Layanan berhasil dibuat.');
    }

    public function edit(HealthService $healthService): View
    {
        Gate::authorize('md.item.manage');

        return view('md.health-services.edit', compact('healthService'));
    }

    public function update(
        HealthServiceRequest $request,
        HealthService $healthService,
        PartnerMasterDataService $service,
    ): RedirectResponse {
        $service->saveHealthService($request->validated(), $healthService);

        return redirect()->route('md.health-services.edit', $healthService)->with('success', 'Layanan berhasil diperbarui.');
    }

    public function updateStatus(HealthService $healthService, PartnerMasterDataService $service): RedirectResponse
    {
        Gate::authorize('md.item.manage');
        $service->setHealthServiceActive($healthService, ! $healthService->is_active);

        return redirect()->route('md.health-services.index')->with('success', 'Status layanan berhasil diperbarui.');
    }
}
