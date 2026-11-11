<?php

namespace App\Jobs\Inventory;

use App\Modules\Inventory\Services\StockPolicyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckExpiry implements ShouldQueue
{
    use Queueable;

    public function handle(StockPolicyService $policies): void
    {
        $policies->notifyNearExpiryBatches();
    }
}
