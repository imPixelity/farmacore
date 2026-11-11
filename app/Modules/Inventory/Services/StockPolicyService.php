<?php

namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Core\Services\NotificationService;
use App\Modules\Core\Services\SettingsService;
use App\Modules\Inventory\Models\GoodsReceiptItem;
use App\Modules\Inventory\Models\ItemBatch;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockPolicy;
use App\Modules\Md\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockPolicyService
{
    public const MOVEMENT_CLASSES = ['fast', 'medium', 'slow'];

    public function __construct(
        private SettingsService $settings,
        private StockService $stock,
        private NotificationService $notifications,
    ) {}

    /** @return array<string, StockPolicy> */
    public function policies(): array
    {
        $policies = StockPolicy::query()->get()->keyBy('movement_class');
        $result = [];

        foreach (self::MOVEMENT_CLASSES as $movementClass) {
            $result[$movementClass] = $policies->get($movementClass) ?? new StockPolicy([
                'movement_class' => $movementClass,
            ]);
        }

        return $result;
    }

    /** @param array<string, array{min_stock_days: int|string|null, safety_factor: int|string|null}> $policies */
    public function savePolicies(array $policies): void
    {
        DB::transaction(function () use ($policies): void {
            foreach (self::MOVEMENT_CLASSES as $movementClass) {
                $values = $policies[$movementClass] ?? null;

                if (! is_array($values)) {
                    throw new InvalidArgumentException('Setiap klasifikasi stok harus memiliki konfigurasi.');
                }

                $policy = StockPolicy::query()->firstOrNew(['movement_class' => $movementClass]);
                $policy->forceFill([
                    'min_stock_days' => $values['min_stock_days'] ?? null,
                    'safety_factor' => $values['safety_factor'] ?? null,
                    'updated_by' => Auth::id(),
                    'created_by' => $policy->exists ? $policy->created_by : Auth::id(),
                ])->save();
            }
        });
    }

    public function updateItemPolicy(Item $item, ?string $movementClass, ?string $minStockOverride): Item
    {
        if ($movementClass !== null && ! in_array($movementClass, self::MOVEMENT_CLASSES, true)) {
            throw new InvalidArgumentException('Klasifikasi pergerakan stok tidak valid.');
        }

        if ($minStockOverride !== null && (! is_numeric($minStockOverride) || bccomp($minStockOverride, '0', 3) < 0)) {
            throw new InvalidArgumentException('Override stok minimum harus bernilai nol atau lebih.');
        }

        $item->forceFill([
            'movement_class' => $movementClass,
            'min_stock_override' => $minStockOverride === null ? null : bcadd($minStockOverride, '0', 3),
            'updated_by' => Auth::id(),
        ])->save();

        return $this->recalculate($item->refresh());
    }

    /**
     * @return array{average_monthly_usage: ?string, average_daily_usage: ?string, min_stock: ?string, buffer_stock: ?string, reorder_point: ?string, available_stock: string, kurang_buffer: ?string, lead_time_days: ?string, policy_configured: bool}
     */
    public function snapshot(Item $item): array
    {
        $usage = $this->usageAverage($item);
        $policy = $item->movement_class === null
            ? null
            : StockPolicy::query()->where('movement_class', $item->movement_class)->first();
        $available = $this->stock->available($item);
        $leadTime = $this->supplierLeadTime($item);
        $averageDaily = $usage['average_monthly_usage'] === null
            ? null
            : bcdiv($usage['average_monthly_usage'], '30', 6);
        $minStock = $item->min_stock;
        $bufferStock = $item->buffer_stock;
        $kurangBuffer = $bufferStock === null
            ? null
            : (bccomp($bufferStock, $available, 3) > 0 ? bcsub($bufferStock, $available, 3) : '0.000');

        return [
            'average_monthly_usage' => $usage['average_monthly_usage'],
            'average_daily_usage' => $averageDaily,
            'min_stock' => $minStock,
            'buffer_stock' => $bufferStock,
            'reorder_point' => $item->reorder_point,
            'available_stock' => $available,
            'kurang_buffer' => $kurangBuffer,
            'lead_time_days' => $leadTime,
            'policy_configured' => $policy !== null,
        ];
    }

    public function recalculate(Item $item): Item
    {
        $usage = $this->usageAverage($item);
        $policy = $item->movement_class === null
            ? null
            : StockPolicy::query()->where('movement_class', $item->movement_class)->first();
        $averageDaily = $usage['average_monthly_usage'] === null
            ? null
            : bcdiv($usage['average_monthly_usage'], '30', 6);
        $minStock = $item->min_stock_override;

        if ($minStock === null && $averageDaily !== null && $policy?->min_stock_days !== null) {
            $minStock = bcmul($averageDaily, (string) $policy->min_stock_days, 3);
        }

        $leadTime = $this->supplierLeadTime($item);
        $bufferStock = null;

        if ($averageDaily !== null && $leadTime !== null && $policy?->safety_factor !== null) {
            $bufferStock = bcmul(
                bcmul($averageDaily, $leadTime, 6),
                (string) $policy->safety_factor,
                3,
            );
        }

        $item->forceFill([
            'min_stock' => $minStock,
            'average_monthly_usage' => $usage['average_monthly_usage'],
            'buffer_stock' => $bufferStock,
            'reorder_point' => $minStock,
            'stock_policy_recalculated_at' => now(),
        ])->saveQuietly();

        return $item;
    }

    public function recalculateAll(): void
    {
        Item::query()->active()->chunkById(100, function (Collection $items): void {
            foreach ($items as $item) {
                $this->recalculate($item);
            }
        });
    }

    public function recalculateAndNotify(Item $item): void
    {
        $item = $this->recalculate($item);
        $this->notifyReorderPoint($item);
    }

    public function notifyReorderPoint(Item $item): void
    {
        if (! $item->is_active || $item->reorder_point === null) {
            return;
        }

        $available = $this->stock->available($item);

        if (bccomp($available, (string) $item->reorder_point, 3) > 0) {
            return;
        }

        $url = route('inventory.stock.show', $item);

        foreach ($this->alertRecipients() as $recipient) {
            $this->notifications->send(
                $recipient,
                'ReorderPointReached',
                'item-'.$item->getKey(),
                [
                    'item_id' => $item->getKey(),
                    'item_name' => $item->name,
                    'message' => "Stok tersedia {$available} untuk {$item->name}, sudah mencapai ROP {$item->reorder_point}.",
                    'available_stock' => $available,
                    'reorder_point' => $item->reorder_point,
                    'url' => $url,
                ],
            );
        }
    }

    public function notifyReorderPoints(): void
    {
        Item::query()
            ->active()
            ->whereNotNull('reorder_point')
            ->orderBy('id')
            ->chunkById(100, function (Collection $items): void {
                foreach ($items as $item) {
                    $this->notifyReorderPoint($item);
                }
            });
    }

    public function notifyNearExpiryBatches(): void
    {
        $alertDays = $this->settings->get('expiry_alert_days');

        if ($alertDays === null || ! is_numeric($alertDays)) {
            return;
        }

        $expiryThrough = today()->addDays((int) $alertDays)->toDateString();
        $recipients = $this->alertRecipients();

        ItemBatch::query()
            ->with('item:id,name,code')
            ->where('qty_on_hand', '>', 0)
            ->whereDate('expiry_date', '<=', $expiryThrough)
            ->whereHas('item', static fn (Builder $query) => $query->where('is_active', true))
            ->orderBy('id')
            ->chunkById(100, function (Collection $batches) use ($recipients, $alertDays): void {
                foreach ($batches as $batch) {
                    foreach ($recipients as $recipient) {
                        $this->notifications->send(
                            $recipient,
                            'BatchNearExpiry',
                            'batch-'.$batch->getKey(),
                            [
                                'item_id' => $batch->item_id,
                                'item_name' => $batch->item->name,
                                'message' => "Batch {$batch->batch_no} untuk {$batch->item->name} memiliki stok {$batch->qty_on_hand} dan ED {$batch->expiry_date->format('d-m-Y')}.",
                                'batch_no' => $batch->batch_no,
                                'qty_on_hand' => $batch->qty_on_hand,
                                'expiry_date' => $batch->expiry_date->toDateString(),
                                'alert_days' => (int) $alertDays,
                                'url' => route('inventory.expiry.index'),
                            ],
                        );
                    }
                }
            });
    }

    /** @return Collection<int, User> */
    private function alertRecipients(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->permission('inventory.stock.view')
            ->orderBy('id')
            ->get();
    }

    /** @return array{average_monthly_usage: ?string, has_usage_history: bool} */
    private function usageAverage(Item $item): array
    {
        $months = max(1, (int) $this->settings->get('usage_average_months', 3));
        $periodEnd = now();
        $periodStart = $periodEnd->copy()->startOfDay()->subMonthsNoOverflow($months);
        $usage = StockMovement::query()
            ->whereHas('itemBatch', static fn (Builder $query) => $query->where('item_id', $item->getKey()))
            ->whereIn('type', ['sale', 'dispensed', 'used'])
            ->where('qty_change', '<', 0)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->selectRaw('COALESCE(SUM(ABS(qty_change)), 0) AS total_usage, COUNT(*) AS usage_count')
            ->first();

        if ((int) $usage?->usage_count === 0) {
            return ['average_monthly_usage' => null, 'has_usage_history' => false];
        }

        return [
            'average_monthly_usage' => bcdiv((string) $usage->total_usage, (string) $months, 6),
            'has_usage_history' => true,
        ];
    }

    private function supplierLeadTime(Item $item): ?string
    {
        $receiptItem = GoodsReceiptItem::query()
            ->where('item_id', $item->getKey())
            ->whereHas('receipt', static fn (Builder $query) => $query->where('status', 'confirmed'))
            ->with('receipt.supplier')
            ->orderByDesc('id')
            ->first();
        $leadTime = $receiptItem?->receipt?->supplier?->lead_time_days;

        return $leadTime === null ? null : (string) $leadTime;
    }
}
