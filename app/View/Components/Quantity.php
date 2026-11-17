<?php

namespace App\View\Components;

use App\Modules\Core\Services\QuantityFormatter;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Quantity extends Component
{
    public string $formatted;

    public string $formattedUnit;

    public function __construct(QuantityFormatter $formatter, mixed $value, public ?string $unit = null)
    {
        $this->formatted = $formatter->format($value);
        $this->formattedUnit = $unit !== null && $this->formatted !== '—' ? ' '.$unit : '';
    }

    public function render(): View|Closure|string
    {
        return view('components.quantity');
    }
}
