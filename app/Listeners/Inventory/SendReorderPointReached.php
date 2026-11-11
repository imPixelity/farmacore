<?php

namespace App\Listeners\Inventory;

use App\Events\Inventory\StockDecreased;
use App\Modules\Inventory\Services\StockPolicyService;

class SendReorderPointReached
{
    public function __construct(private StockPolicyService $policies) {}

    public function handle(StockDecreased $event): void
    {
        $this->policies->recalculateAndNotify($event->batch->item);
    }
}
