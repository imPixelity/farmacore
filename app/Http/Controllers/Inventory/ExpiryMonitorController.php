<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Modules\Core\Services\SettingsService;
use App\Modules\Inventory\Models\ItemBatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpiryMonitorController extends Controller
{
    public function index(Request $request, SettingsService $settings): View
    {
        $filters = $request->validate([
            'expiry_from' => ['nullable', 'date_format:Y-m-d'],
            'expiry_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:expiry_from'],
        ]);

        $batches = ItemBatch::query()
            ->with('item:id,name,code')
            ->where('qty_on_hand', '>', 0)
            ->whereHas('item', static fn (Builder $query) => $query->where('is_active', true))
            ->when(filled($filters['expiry_from'] ?? null), fn (Builder $query) => $query->whereDate('expiry_date', '>=', $filters['expiry_from']))
            ->when(filled($filters['expiry_to'] ?? null), fn (Builder $query) => $query->whereDate('expiry_date', '<=', $filters['expiry_to']))
            ->orderBy('expiry_date')
            ->orderBy('item_id')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.expiry.index', [
            'batches' => $batches,
            'filters' => $filters,
            'expiryAlertDays' => $settings->get('expiry_alert_days'),
        ]);
    }
}
