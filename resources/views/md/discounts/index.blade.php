@extends('layouts.app')

@section('title', 'Aturan diskon')
@section('breadcrumb', 'Data master / Harga / Diskon')
@section('page-heading', 'Aturan diskon')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div><h2 class="font-bold text-slate-900">Aturan diskon penjualan</h2><p class="mt-1 text-sm text-slate-500">Aturan yang cocok dievaluasi tanpa ditumpuk; potongan terbesar diterapkan pada barang yang sama.</p></div>
            <a href="{{ route('md.discounts.create') }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">Tambah aturan</a>
        </div>
        <div class="overflow-x-auto"><table class="w-full min-w-240 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Aturan</th><th class="px-5 py-3">Syarat</th><th class="px-5 py-3">Target</th><th class="px-5 py-3">Diskon</th><th class="px-5 py-3">Masa berlaku</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($discountRules as $rule)
                    <tr>
                        <td class="px-5 py-4 font-medium text-slate-900">{{ $rule->name }}<span class="mt-1 block text-xs font-normal text-slate-500">{{ $rule->sales_type ? ['resep' => 'Resep', 'obat_bebas' => 'Obat bebas', 'produk_lain' => 'Produk lainnya'][$rule->sales_type] : 'Semua jenis' }}</span></td>
                        <td class="px-5 py-4">{{ $rule->rule_type === 'qty' ? 'Jumlah ≥ '.$rule->min_qty : 'Subtotal ≥ Rp '.number_format((float) $rule->min_amount, 2, ',', '.') }}</td>
                        <td class="px-5 py-4">{{ $rule->item?->name ?? $rule->category?->name ?? 'Semua barang' }}</td>
                        <td class="px-5 py-4">{{ $rule->discount_type === 'percent' ? $rule->discount_value.'%' : 'Rp '.number_format((float) $rule->discount_value, 2, ',', '.') }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $rule->effective_from?->format('d-m-Y') ?? '—' }} – {{ $rule->effective_until?->format('d-m-Y') ?? '—' }}</td>
                        <td class="px-5 py-4">{{ $rule->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td class="px-5 py-4 text-right"><a href="{{ route('md.discounts.edit', $rule) }}" class="font-semibold text-emerald-700">Ubah</a><form method="POST" action="{{ route('md.discounts.status', $rule) }}" class="ml-3 inline">@csrf @method('PATCH')<button class="font-semibold text-rose-700">{{ $rule->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Belum ada aturan diskon.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if ($discountRules->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $discountRules->links() }}</div>@endif
    </section>
@endsection
