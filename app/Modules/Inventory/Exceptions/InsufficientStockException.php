<?php

namespace App\Modules\Inventory\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public int $itemBatchId,
        public string $requestedQuantity,
        public string $availableQuantity,
    ) {
        parent::__construct('Stok batch tidak mencukupi untuk pengurangan yang diminta.');
    }
}
