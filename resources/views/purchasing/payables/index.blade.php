@extends('layouts.app')

@section('title', 'Utang supplier')
@section('breadcrumb', 'Pembelian / Utang supplier')
@section('page-heading', 'Utang supplier')

@section('content')
    <div class="grid gap-6 xl:grid-cols-[1fr_1.35fr]">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold">Total utang per supplier</h2><p class="mt-1 text-sm text-slate-500">Sisa utang dari faktur yang belum lunas.</p></div>
            <div class="divide-y divide-slate-100">
                @forelse ($supplierTotals as $summary)
                    <div class="flex items-center justify-between gap-4 px-5 py-4"><div><p class="font-semibold">{{ $summary->supplier->name }}</p><p class="text-xs text-slate-500">{{ $summary->supplier->phone ?: 'Kontak tidak tersedia' }}</p></div><p class="font-semibold text-rose-700">Rp {{ number_format($summary->remaining, 2, ',', '.') }}</p></div>
                @empty
                    <p class="px-5 py-8 text-center text-slate-500">Tidak ada utang aktif.</p>
                @endforelse
            </div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4"><div><h2 class="font-bold">Daftar belum lunas</h2><p class="mt-1 text-sm text-slate-500">Total sisa: <strong>Rp {{ number_format((float) $totalOutstanding, 2, ',', '.') }}</strong></p></div><form class="flex flex-wrap gap-2 text-sm"><select name="status" class="rounded-xl border-slate-300"><option value="">Semua status</option><option value="belum_dibayar" @selected(($filters['status'] ?? '') === 'belum_dibayar')>Belum dibayar</option><option value="sebagian" @selected(($filters['status'] ?? '') === 'sebagian')>Sebagian</option></select><select name="due" class="rounded-xl border-slate-300"><option value="">Semua jatuh tempo</option><option value="overdue" @selected(($filters['due'] ?? '') === 'overdue')>Lewat jatuh tempo</option><option value="soon" @selected(($filters['due'] ?? '') === 'soon')>7 hari ke depan</option></select><button class="rounded-xl bg-slate-900 px-3 py-2 font-semibold text-white">Filter</button></form></div>
            <div class="overflow-x-auto"><table class="w-full min-w-180 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Faktur</th><th class="px-5 py-3">Supplier</th><th class="px-5 py-3">Jatuh tempo</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Sisa</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($payables as $payable)<tr><td class="px-5 py-4"><a href="{{ route('purchasing.payables.show', $payable) }}" class="font-semibold text-emerald-800 hover:text-emerald-950">{{ $payable->invoice_no }}</a><span class="mt-1 block text-xs text-slate-500">{{ $payable->goodsReceipt?->receipt_no }}</span></td><td class="px-5 py-4">{{ $payable->supplier->name }}</td><td class="px-5 py-4 {{ $payable->due_date->isPast() ? 'font-semibold text-rose-700' : 'text-slate-600' }}">{{ $payable->due_date->format('d-m-Y') }}</td><td class="px-5 py-4"><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">{{ str_replace('_', ' ', ucfirst($payable->status)) }}</span></td><td class="px-5 py-4 text-right font-semibold">Rp {{ number_format((float) $payable->remainingAmount(), 2, ',', '.') }}</td></tr>@empty<tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Tidak ada utang sesuai filter.</td></tr>@endforelse</tbody></table></div>
        </section>
    </div>
@endsection
