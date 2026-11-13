<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\TaxObligation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaxObligationService
{
    /** @param array{tax_type: string, period: string, amount_due: string|int|float, due_date?: string|null, notes?: string|null} $data */
    public function create(array $data): TaxObligation
    {
        return DB::transaction(function () use ($data): TaxObligation {
            $obligation = new TaxObligation;
            $obligation->fill([
                ...$data,
                'status' => 'belum',
                'paid_at' => null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $obligation->save();

            return $obligation;
        });
    }

    /** @param array{tax_type: string, period: string, amount_due: string|int|float, due_date?: string|null, notes?: string|null} $data */
    public function update(TaxObligation $taxObligation, array $data): TaxObligation
    {
        return DB::transaction(function () use ($taxObligation, $data): TaxObligation {
            $locked = TaxObligation::query()->lockForUpdate()->findOrFail($taxObligation->id);
            if ($locked->status === 'dibayar') {
                throw ValidationException::withMessages(['tax_obligation' => 'Kewajiban yang sudah dibayar tidak dapat diubah.']);
            }

            $locked->fill([
                ...$data,
                'updated_by' => Auth::id(),
            ])->save();

            return $locked;
        });
    }

    public function markPaid(TaxObligation $taxObligation, string $paidAt): TaxObligation
    {
        return DB::transaction(function () use ($taxObligation, $paidAt): TaxObligation {
            $locked = TaxObligation::query()->lockForUpdate()->findOrFail($taxObligation->id);
            if ($locked->status === 'dibayar') {
                throw ValidationException::withMessages(['tax_obligation' => 'Kewajiban ini sudah ditandai dibayar.']);
            }

            $locked->forceFill([
                'status' => 'dibayar',
                'paid_at' => $paidAt,
                'updated_by' => Auth::id(),
            ])->save();

            return $locked;
        });
    }
}
