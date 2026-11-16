@extends('layouts.app')

@section('title', 'Riwayat penjualan')
@section('breadcrumb', 'POS / Riwayat penjualan')
@section('page-heading', 'Riwayat penjualan')

@section('content')
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="font-bold text-slate-900">Transaksi terbaru</h2>
                <p class="mt-1 text-sm text-slate-500">Cetak ulang membuka struk bertanda salinan dan tidak mengubah transaksi.</p>
            </div>
            <a href="{{ route('pos.index') }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Kembali ke POS</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-220 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Faktur / tanggal</th>
                        <th class="px-5 py-3 font-semibold">Kasir</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                        <th class="px-5 py-3 text-right font-semibold">Total</th>
                        <th class="px-5 py-3 text-right font-semibold">Struk</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="px-5 py-4 font-semibold text-slate-900">
                                {{ $sale->invoice_no }}
                                <span class="mt-1 block text-xs font-normal text-slate-500">{{ $sale->sale_date->format('d-m-Y H:i') }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ $sale->cashier->name }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ ucfirst(str_replace('_', ' ', $sale->status)) }}</td>
                            <td class="px-5 py-4 text-right font-semibold text-slate-900">Rp {{ number_format((float) $sale->grand_total, 2, ',', '.') }}</td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-3 whitespace-nowrap">
                                    <a href="{{ route('pos.sales.receipt', $sale) }}" target="_blank" rel="noopener" class="font-semibold text-slate-600 hover:text-slate-900">Struk</a>
                                    <a href="{{ route('pos.sales.receipt', ['sale' => $sale, 'copy' => 1]) }}" target="_blank" rel="noopener" class="font-semibold text-emerald-700 hover:text-emerald-900">Cetak ulang (SALINAN)</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Belum ada transaksi penjualan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sales->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">{{ $sales->links() }}</div>
        @endif
    </section>
@endsection
