<tr data-receipt-line class="align-top">
    <td class="min-w-56 px-4 py-4">
        <select name="lines[{{ $index }}][item_id]" data-item-select required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
            <option value="">Pilih barang</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}" @selected((string) ($line['item_id'] ?? '') === (string) $item->id)>{{ $item->code }} · {{ $item->name }}</option>
            @endforeach
        </select>
        @error("lines.{$index}.item_id")<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
    </td>
    <td class="min-w-40 px-4 py-4">
        <select name="lines[{{ $index }}][item_unit_id]" data-unit-select required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
            <option value="">Pilih satuan</option>
            @foreach ($items as $item)
                @foreach ($item->itemUnits as $itemUnit)
                    <option value="{{ $itemUnit->id }}" data-item-id="{{ $item->id }}" @selected((string) ($line['item_unit_id'] ?? '') === (string) $itemUnit->id)>{{ $item->name }} · {{ $itemUnit->unit->name }} (×{{ $itemUnit->factor_to_base }})</option>
                @endforeach
            @endforeach
        </select>
        @error("lines.{$index}.item_unit_id")<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
    </td>
    <td class="min-w-32 px-4 py-4">
        <input name="lines[{{ $index }}][qty_received]" type="number" min="0.001" step="0.001" value="{{ $line['qty_received'] ?? '' }}" required placeholder="Jumlah" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
        @error("lines.{$index}.qty_received")<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
    </td>
    <td class="min-w-36 px-4 py-4">
        <input name="lines[{{ $index }}][batch_no]" type="text" maxlength="60" value="{{ $line['batch_no'] ?? '' }}" required placeholder="Nomor batch" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
        @error("lines.{$index}.batch_no")<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
    </td>
    <td class="min-w-40 px-4 py-4">
        <input name="lines[{{ $index }}][expiry_date]" type="date" value="{{ $line['expiry_date'] ?? '' }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
        @error("lines.{$index}.expiry_date")<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
        @can('inventory.receipt.approve_expiry')
            <label class="mt-2 flex items-start gap-2 text-xs text-amber-800">
                <input type="checkbox" name="lines[{{ $index }}][approve_expiry]" value="1" @checked(filter_var($line['approve_expiry'] ?? false, FILTER_VALIDATE_BOOLEAN)) class="mt-0.5 rounded border-amber-400 text-amber-700 focus:ring-amber-500">
                <span>Setujui ED pengecualian</span>
            </label>
        @endcan
    </td>
    <td class="min-w-36 px-4 py-4">
        <input name="lines[{{ $index }}][unit_price]" type="number" min="0.01" step="0.01" value="{{ $line['unit_price'] ?? '' }}" required placeholder="Harga beli" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
        @error("lines.{$index}.unit_price")<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
    </td>
    <td class="min-w-36 px-4 py-4">
        <input name="lines[{{ $index }}][discount_amount]" type="number" min="0" step="0.01" value="{{ $line['discount_amount'] ?? '0.00' }}" placeholder="Diskon" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
        @error("lines.{$index}.discount_amount")<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
    </td>
    <td class="px-4 py-4 text-right">
        <button type="button" data-remove-line class="rounded-lg px-2 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Hapus</button>
    </td>
</tr>
