<?php

namespace App\Modules\Inventory\Exceptions;

use RuntimeException;

class ExpiredBatchNotSellableException extends RuntimeException
{
    public function __construct(public int $itemBatchId)
    {
        parent::__construct('Batch kedaluwarsa tidak dapat digunakan untuk penjualan.');
    }
}
