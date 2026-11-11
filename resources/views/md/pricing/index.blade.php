@extends('layouts.app')

@section('title', 'Harga barang')
@section('breadcrumb', 'Data master / Harga')
@section('page-heading', 'Harga barang')

@section('content')
    <div class="grid gap-5">
        <nav class="flex flex-wrap gap-2">
            <a href="{{ route('md.margins.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Margin</a>
            <a href="{{ route('md.taxes.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Pajak</a>
            <a href="{{ route('md.discounts.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Diskon</a>
        </nav>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="font-bold text-slate-900">Histori harga per satuan terkecil</h2>
                <p class="mt-1 text-sm text-slate-500">Harga baru dibuat oleh PricingService saat HNA, margin, atau tarif pajak berubah. HNA diperbarui melalui penerimaan barang.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-230 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Barang</th><th class="px-5 py-3">Jenis</th><th class="px-5 py-3">HNA</th><th class="px-5 py-3">Beli + PPN</th><th class="px-5 py-3">Margin</th><th class="px-5 py-3">Harga jual</th><th class="px-5 py-3">Mulai berlaku</th><th class="px-5 py-3">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($prices as $price)
                            <tr>
                                <td class="px-5 py-4 font-medium text-slate-900">{{ $price->item->name }}<span class="mt-1 block text-xs font-normal text-slate-500">{{ $price->item->code }}</span></td>
                                <td class="px-5 py-4 text-slate-600">{{ ['resep' => 'Resep', 'obat_bebas' => 'Obat bebas', 'produk_lain' => 'Produk lainnya'][$price->sales_type] }}</td>
                                <td class="px-5 py-4 text-slate-600">Rp {{ number_format((float) $price->item->hna_base, 4, ',', '.') }}</td>
                                <td class="px-5 py-4 text-slate-600">Rp {{ number_format((float) $price->purchase_price_incl_tax, 4, ',', '.') }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $price->margin_percent }}%</td>
                                <td class="px-5 py-4 font-semibold text-slate-900">Rp {{ number_format((float) $price->selling_price, 2, ',', '.') }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $price->effective_from->format('d-m-Y H:i') }}</td>
                                <td class="px-5 py-4">{{ $price->is_current ? 'Berlaku' : 'Riwayat' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-10 text-center text-slate-500">Belum ada histori harga. Atur margin dan tarif PPN; harga akan terbentuk setelah HNA barang tersedia.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($prices->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $prices->links() }}</div>@endif
        </section>
    </div>
@endsection
