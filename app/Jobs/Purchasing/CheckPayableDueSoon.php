<?php

namespace App\Jobs\Purchasing;

use App\Modules\Purchasing\Services\PayableService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckPayableDueSoon implements ShouldQueue
{
    use Queueable;

    public function handle(PayableService $payables): void
    {
        $payables->notifyDueSoon();
    }
}
