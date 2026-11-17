<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Accounting\Models\Receivable;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Md\Models\Item;
use App\Modules\Pos\Models\Sale;
use App\Modules\Purchasing\Models\Payable;
use App\Modules\Rx\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $metrics = [];
        $canViewSales = Gate::allows('pos.sale.view');
        $recentSales = collect();

        if ($canViewSales) {
            $salesToday = Sale::query()
                ->whereDate('sale_date', today())
                ->where('status', 'completed')
                ->selectRaw('COUNT(*) as transaction_count, COALESCE(SUM(grand_total), 0) as sales_total')
                ->first();

            $metrics[] = [
                'label' => 'Penjualan hari ini',
                'value' => number_format((int) $salesToday->transaction_count, 0, ',', '.'),
                'description' => 'Total Rp '.number_format((float) $salesToday->sales_total, 2, ',', '.'),
                'symbol' => 'Rp',
                'url' => route('pos.sales.index'),
            ];

            $recentSales = Sale::query()
                ->select(['id', 'invoice_no', 'sale_date', 'grand_total', 'payment_status', 'status'])
                ->where('status', 'completed')
                ->orderByDesc('sale_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get();
        }

        if (Gate::allows('md.item.view')) {
            $metrics[] = [
                'label' => 'Barang aktif',
                'value' => number_format(Item::query()->where('is_active', true)->count(), 0, ',', '.'),
                'description' => 'Data barang yang dapat digunakan',
                'symbol' => 'BR',
                'url' => route('md.items.index'),
            ];
        }

        if (Gate::allows('inventory.stock.view')) {
            $metrics[] = [
                'label' => 'Batch tersedia',
                'value' => number_format(ItemBatch::query()->available()->count(), 0, ',', '.'),
                'description' => 'Batch dengan stok yang masih berlaku',
                'symbol' => 'ST',
                'url' => route('inventory.stock.index'),
            ];
        }

        if (Gate::allows('rx.prescription.manage')) {
            $metrics[] = [
                'label' => 'Antrean resep',
                'value' => number_format(Prescription::query()->whereIn('status', ['received', 'verified', 'prepared'])->count(), 0, ',', '.'),
                'description' => 'Resep yang masih dalam proses pelayanan',
                'symbol' => 'RX',
                'url' => route('rx.prescriptions.index'),
            ];
        }

        if (Gate::allows('purchasing.payable.view')) {
            $metrics[] = [
                'label' => 'Utang supplier',
                'value' => number_format(Payable::query()->where('status', '!=', 'lunas')->count(), 0, ',', '.'),
                'description' => 'Tagihan yang belum diselesaikan',
                'symbol' => 'UT',
                'url' => route('purchasing.payables.index'),
            ];
        }

        if (Gate::allows('accounting.receivable.manage')) {
            $metrics[] = [
                'label' => 'Piutang pelanggan',
                'value' => number_format(Receivable::query()->whereNull('cancelled_at')->where('status', '!=', 'lunas')->count(), 0, ',', '.'),
                'description' => 'Tagihan pelanggan yang masih berjalan',
                'symbol' => 'PI',
                'url' => route('accounting.receivables.index'),
            ];
        }

        return view('welcome', [
            'user' => $user,
            'metrics' => $metrics,
            'canViewSales' => $canViewSales,
            'recentSales' => $recentSales,
        ]);
    }
}
