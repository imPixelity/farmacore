<?php

namespace App\Http\Controllers\Md;

use App\Http\Controllers\Controller;
use App\Http\Requests\Md\UnitRequest;
use App\Modules\Md\Models\Unit;
use App\Modules\Md\Services\MasterDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        Gate::authorize('md.item.manage');

        return view('md.units.index', [
            'units' => Unit::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function store(UnitRequest $request, MasterDataService $service): RedirectResponse
    {
        $service->saveUnit($request->validated());

        return redirect()->route('md.units.index')->with('success', 'Satuan berhasil dibuat.');
    }

    public function edit(Unit $unit): View
    {
        Gate::authorize('md.item.manage');

        return view('md.units.edit', compact('unit'));
    }

    public function update(UnitRequest $request, Unit $unit, MasterDataService $service): RedirectResponse
    {
        $service->saveUnit($request->validated(), $unit);

        return redirect()->route('md.units.index')->with('success', 'Satuan berhasil diperbarui.');
    }

    public function updateStatus(Unit $unit, MasterDataService $service): RedirectResponse
    {
        Gate::authorize('md.item.manage');
        $service->setUnitActive($unit, ! $unit->is_active);

        return redirect()->route('md.units.index')->with('success', 'Status satuan berhasil diperbarui.');
    }
}
