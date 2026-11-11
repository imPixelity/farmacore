@extends('layouts.app')

@section('title', 'Penerimaan barang')
@section('breadcrumb', 'Persediaan / Penerimaan')
@section('page-heading', 'Penerimaan barang')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="font-bold text-slate-900">Daftar penerimaan</h2>
                <p class="mt-1 text-sm text-slate-500">Konfirmasi draft memposting stok, pajak, dan dampak pembayaran secara atomik.</p>
            </div>
            @can('inventory.receipt.manage')
                <a href="{{ route('inventory.receipts.create') }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Buat penerimaan</a>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-190 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Nomor penerimaan / faktur</th>
                        <th class="px-6 py-3 font-semibold">Supplier</th>
                        <th class="px-6 py-3 font-semibold">Tanggal</th>
                        <th class="px-6 py-3 font-semibold">Pembayaran</th>
                        <th class="px-6 py-3 text-right font-semibold">Total</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($receipts as $receipt)
                        <tr>
                            <td class="px-6 py-4">
                                <a href="{{ route('inventory.receipts.show', $receipt) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">{{ $receipt->receipt_no }}</a>
                                <span class="mt-1 block text-xs text-slate-500">Faktur {{ $receipt->invoice_no }}</span>
                            </td>
                            <td class="px-6 py-4 text-slate-700">{{ $receipt->supplier->name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $receipt->invoice_date->format('d-m-Y') }}</td>
                            <td class="px-6 py-4 capitalize text-slate-600">{{ $receipt->payment_method }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-slate-900">{{ $receipt->total_after_tax }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $receipt->status === 'confirmed' ? 'bg-emerald-50 text-emerald-700' : ($receipt->status === 'cancelled' ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-800') }}">{{ ucfirst($receipt->status) }}</span>
                                <span class="mt-1 block text-xs text-slate-500">{{ $receipt->items_count }} baris</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-10 text-center text-slate-500">Belum ada penerimaan barang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($receipts->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">{{ $receipts->links() }}</div>
        @endif
    </section>
@endsection
