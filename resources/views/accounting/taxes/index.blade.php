@extends('layouts.app')

@section('title', 'Pajak & kewajiban')
@section('breadcrumb', 'Keuangan / Pajak')
@section('page-heading', 'Pajak & kewajiban')

@section('content')
    <div class="grid gap-5">
        <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:flex-row sm:items-end sm:justify-between sm:p-6">
            <div>
                <h2 class="font-bold text-slate-900">Laporan pendukung pajak</h2>
                <p class="mt-1 text-sm text-slate-500">Rekap PPN masukan/keluaran berdasarkan pajak yang tercatat pada transaksi.</p>
            </div>
            <form method="GET" action="{{ route('accounting.taxes.index') }}" class="flex items-end gap-2">
                <label class="grid gap-1.5 text-xs font-semibold text-slate-600">Periode
                    <input type="month" name="period" value="{{ $period }}" class="rounded-xl border-slate-300 text-sm">
                </label>
                <button class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">Tampilkan</button>
            </form>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([['Penjualan bruto', $summary['gross_sales']], ['DPP masukan', $summary['input_base']], ['PPN masukan', $summary['input_tax']], ['DPP keluaran', $summary['output_base']], ['PPN keluaran', $summary['output_tax']]] as [$label, $amount])
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-xl font-bold text-slate-900">Rp {{ number_format((float) $amount, 2, ',', '.') }}</p>
                </article>
            @endforeach
        </section>
        <p class="rounded-xl bg-sky-50 px-4 py-3 text-sm text-sky-800">Selisih PPN keluaran - masukan: <strong>Rp {{ number_format((float) $summary['tax_difference'], 2, ',', '.') }}</strong>. Sistem tidak menghitung PPh otomatis; PPh dicatat manual sebagai kewajiban pajak.</p>

        <div class="grid gap-5 xl:grid-cols-3">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
                <h2 class="font-bold text-slate-900">Catat kewajiban pajak</h2>
                <p class="mt-1 text-sm text-slate-500">Gunakan untuk PPN, PPh, atau kewajiban lain yang diinput staf keuangan.</p>
                <form method="POST" action="{{ route('accounting.taxes.store') }}" class="mt-5 grid gap-4">
                    @csrf
                    <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Jenis pajak<select name="tax_type" required class="rounded-xl border-slate-300 bg-white font-normal"><option value="PPN">PPN</option><option value="PPh">PPh</option><option value="lainnya">Lainnya</option></select></label>
                    <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Periode<input type="month" name="period" value="{{ old('period', $period) }}" required class="rounded-xl border-slate-300 font-normal"></label>
                    <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Jumlah kewajiban<input type="number" name="amount_due" min="0.01" step="0.01" value="{{ old('amount_due') }}" required class="rounded-xl border-slate-300 font-normal"></label>
                    <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Jatuh tempo<input type="date" name="due_date" value="{{ old('due_date') }}" class="rounded-xl border-slate-300 font-normal"></label>
                    <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Catatan<textarea name="notes" rows="3" maxlength="5000" class="rounded-xl border-slate-300 font-normal">{{ old('notes') }}</textarea></label>
                    @foreach (['tax_type', 'period', 'amount_due', 'due_date', 'notes'] as $field) @error($field)<span class="text-xs text-rose-700">{{ $message }}</span>@enderror @endforeach
                    <button class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white">Simpan kewajiban</button>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2 xl:col-span-2">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="font-bold text-slate-900">Kewajiban periode {{ $period }}</h2></div>
                <div class="overflow-x-auto"><table class="w-full min-w-180 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Jenis</th><th class="px-5 py-3">Jumlah</th><th class="px-5 py-3">Jatuh tempo</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($obligations as $obligation)
                            <tr>
                                <td class="px-5 py-4 font-medium text-slate-900">{{ $obligation->tax_type }}</td>
                                <td class="px-5 py-4">Rp {{ number_format((float) $obligation->amount_due, 2, ',', '.') }}</td>
                                <td class="px-5 py-4">{{ $obligation->due_date?->format('d-m-Y') ?? '—' }}</td>
                                <td class="px-5 py-4">{{ $obligation->status === 'dibayar' ? 'Dibayar '.$obligation->paid_at?->format('d-m-Y') : 'Belum dibayar' }}</td>
                                <td class="space-y-2 px-5 py-4">
                                    @if ($obligation->status === 'belum')
                                        <form method="POST" action="{{ route('accounting.taxes.paid', $obligation) }}">@csrf<input type="hidden" name="paid_at" value="{{ today()->toDateString() }}"><button class="rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700">Tandai dibayar</button></form>
                                        <details><summary class="cursor-pointer text-xs font-semibold text-slate-600">Edit</summary><form method="POST" action="{{ route('accounting.taxes.update', $obligation) }}" class="mt-2 grid gap-2"><input type="hidden" name="_method" value="PUT"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input name="tax_type" value="{{ $obligation->tax_type }}" required class="rounded-lg border-slate-300 text-xs"><input type="month" name="period" value="{{ $obligation->period }}" required class="rounded-lg border-slate-300 text-xs"><input type="number" name="amount_due" value="{{ $obligation->amount_due }}" min="0.01" step="0.01" required class="rounded-lg border-slate-300 text-xs"><input type="date" name="due_date" value="{{ $obligation->due_date?->toDateString() }}" class="rounded-lg border-slate-300 text-xs"><textarea name="notes" rows="2" class="rounded-lg border-slate-300 text-xs">{{ $obligation->notes }}</textarea><button class="rounded-lg bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white">Simpan edit</button></form></details>
                                    @else
                                        <span class="text-xs text-slate-400">Tidak dapat diubah</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Belum ada kewajiban pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </section>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="font-bold text-slate-900">Tax records periode {{ $period }}</h2><p class="mt-1 text-sm text-slate-500">PPN tercatat otomatis dari penerimaan barang dan penjualan.</p></div>
            <div class="overflow-x-auto"><table class="w-full min-w-180 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Arah</th><th class="px-5 py-3">Tarif</th><th class="px-5 py-3">DPP</th><th class="px-5 py-3">Pajak</th><th class="px-5 py-3">Sumber</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($summary['records'] as $record)<tr><td class="px-5 py-4">{{ $record->transaction_date->format('d-m-Y') }}</td><td class="px-5 py-4">{{ $record->direction === 'input' ? 'Masukan' : 'Keluaran' }}</td><td class="px-5 py-4">{{ $record->rate_percent }}%</td><td class="px-5 py-4">Rp {{ number_format((float) $record->taxable_amount, 2, ',', '.') }}</td><td class="px-5 py-4 font-semibold">Rp {{ number_format((float) $record->tax_amount, 2, ',', '.') }}</td><td class="px-5 py-4">{{ $record->source?->invoice_no ?? $record->source?->receipt_no ?? class_basename($record->source_type).' #'.$record->source_id }}</td></tr>@empty<tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Belum ada tax record pada periode ini.</td></tr>@endforelse</tbody></table></div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2"><div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="font-bold text-slate-900">Riwayat kewajiban</h2></div><div class="overflow-x-auto"><table class="w-full min-w-180 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Periode</th><th class="px-5 py-3">Jenis</th><th class="px-5 py-3">Jumlah</th><th class="px-5 py-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach ($allObligations as $obligation)<tr><td class="px-5 py-4">{{ $obligation->period }}</td><td class="px-5 py-4">{{ $obligation->tax_type }}</td><td class="px-5 py-4">Rp {{ number_format((float) $obligation->amount_due, 2, ',', '.') }}</td><td class="px-5 py-4">{{ $obligation->status === 'dibayar' ? 'Dibayar' : 'Belum dibayar' }}</td></tr>@endforeach</tbody></table></div><div class="border-t border-slate-100 px-5 py-4">{{ $allObligations->links() }}</div></section>
    </div>
@endsection
