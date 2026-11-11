<?php

namespace App\Http\Controllers\Md;

use App\Http\Controllers\Controller;
use App\Http\Requests\Md\SupplierRequest;
use App\Modules\Md\Models\Supplier;
use App\Modules\Md\Services\PartnerMasterDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('md.item.view');

        $suppliers = Supplier::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.trim($request->string('search')->toString()).'%';
                $query->where(function ($query) use ($search): void {
                    $query->whereLike('name', $search)
                        ->orWhereLike('contact_person', $search)
                        ->orWhereLike('phone', $search)
                        ->orWhereLike('email', $search);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->input('status') === 'active'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('md.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        Gate::authorize('md.item.manage');

        return view('md.suppliers.create', ['supplier' => null]);
    }

    public function store(SupplierRequest $request, PartnerMasterDataService $service): RedirectResponse
    {
        $supplier = $service->saveSupplier($request->validated());

        return redirect()->route('md.suppliers.edit', $supplier)->with('success', 'Supplier berhasil dibuat.');
    }

    public function edit(Supplier $supplier): View
    {
        Gate::authorize('md.item.manage');

        return view('md.suppliers.edit', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier, PartnerMasterDataService $service): RedirectResponse
    {
        $service->saveSupplier($request->validated(), $supplier);

        return redirect()->route('md.suppliers.edit', $supplier)->with('success', 'Supplier berhasil diperbarui.');
    }

    public function updateStatus(Supplier $supplier, PartnerMasterDataService $service): RedirectResponse
    {
        Gate::authorize('md.item.manage');
        $service->setSupplierActive($supplier, ! $supplier->is_active);

        return redirect()->route('md.suppliers.index')->with('success', 'Status supplier berhasil diperbarui.');
    }
}
