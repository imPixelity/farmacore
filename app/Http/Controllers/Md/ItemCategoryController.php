<?php

namespace App\Http\Controllers\Md;

use App\Http\Controllers\Controller;
use App\Http\Requests\Md\ItemCategoryRequest;
use App\Modules\Md\Models\ItemCategory;
use App\Modules\Md\Services\MasterDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ItemCategoryController extends Controller
{
    public function index(): View
    {
        Gate::authorize('md.item.manage');

        return view('md.categories.index', [
            'categories' => ItemCategory::query()->orderBy('type')->orderBy('name')->paginate(20),
        ]);
    }

    public function store(ItemCategoryRequest $request, MasterDataService $service): RedirectResponse
    {
        $service->saveCategory($request->validated());

        return redirect()->route('md.categories.index')->with('success', 'Kategori berhasil dibuat.');
    }

    public function edit(ItemCategory $category): View
    {
        Gate::authorize('md.item.manage');

        return view('md.categories.edit', compact('category'));
    }

    public function update(ItemCategoryRequest $request, ItemCategory $category, MasterDataService $service): RedirectResponse
    {
        $service->saveCategory($request->validated(), $category);

        return redirect()->route('md.categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function updateStatus(ItemCategory $category, MasterDataService $service): RedirectResponse
    {
        Gate::authorize('md.item.manage');
        $isActive = ! $category->is_active;
        $service->setCategoryActive($category, $isActive);

        return redirect()->route('md.categories.index')->with('success', 'Status kategori berhasil diperbarui.');
    }
}
