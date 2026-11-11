@extends('layouts.app')

@section('title', 'Persediaan')
@section('breadcrumb', 'Persediaan')
@section('page-heading', 'Persediaan')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="font-bold text-slate-900">Stok per barang</h2>
                <p class="mt-1 text-sm text-slate-500">Jumlah disimpan dalam satuan dasar dan ditampilkan juga menurut konversi barang.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('inventory.expiry.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Monitor ED</a>
                @can('inventory.stock.manage')
                    <a href="{{ route('inventory.stock-policies.index') }}" class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Kebijakan stok</a>
                @endcan
                <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">Baca saja · saldo berasal dari mutasi</span>
            </div>
        </div>

        <form method="GET" action="{{ route('inventory.stock.index') }}" class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-end sm:px-6">
            <label class="w-full sm:max-w-md">
                <span class="mb-1.5 block text-xs font-semibold text-slate-600">Cari barang</span>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Nama, kode, atau barcode" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
            </label>
            <div class="flex gap-2">
                <a href="{{ route('inventory.stock.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Cari</button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-190 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Barang</th>
                        <th class="px-6 py-3 font-semibold">Total stok per satuan</th>
                        <th class="px-6 py-3 font-semibold">Tersedia</th>
                        <th class="px-6 py-3 text-right font-semibold">Batch</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($items as $item)
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                {{ $item->name }}
                                <span class="mt-1 block text-xs font-normal text-slate-500">{{ $item->code }} · {{ $item->category?->name ?? 'Tanpa kategori' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-slate-700">
                                    @foreach ($item->stock_by_unit as $unitStock)
                                        <span>{{ $unitStock['quantity'] }} {{ $unitStock['name'] }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-emerald-800">{{ $item->available_total ?? '0.000' }} {{ $item->baseUnit->name }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('inventory.stock.show', $item) }}" class="font-semibold text-emerald-700 hover:text-emerald-900">{{ $item->batches_count }} batch · Rincian</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-10 text-center text-slate-500">Belum ada barang yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">{{ $items->links() }}</div>
        @endif
    </section>
@endsection
