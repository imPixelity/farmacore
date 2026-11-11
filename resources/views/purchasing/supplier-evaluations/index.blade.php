@extends('layouts.app')

@section('title', 'Evaluasi supplier')
@section('breadcrumb', 'Pembelian / Evaluasi supplier')
@section('page-heading', 'Evaluasi supplier')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="font-bold text-slate-900">Kinerja supplier dari penerimaan terkonfirmasi</h2><p class="mt-1 text-sm text-slate-500">Metrik dihitung dari PO dan penerimaan nyata; riwayat harga tersedia pada detail supplier di tahap berikutnya.</p></div>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Supplier</th><th class="px-5 py-3">Lead time rata-rata</th><th class="px-5 py-3">Ketepatan pengiriman</th><th class="px-5 py-3">Kesesuaian</th><th class="px-5 py-3">Riwayat harga</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($evaluations as $evaluation)<tr><td class="px-5 py-4 font-semibold text-slate-900">{{ $evaluation['supplier']->name }}</td><td class="px-5 py-4 text-slate-600">{{ $evaluation['metrics']['average_lead_time_days'] ?? '-' }} hari</td><td class="px-5 py-4 text-slate-600">{{ $evaluation['metrics']['delivery_accuracy_percent'] ?? '-' }}{{ $evaluation['metrics']['delivery_accuracy_percent'] !== null ? '%' : '' }}</td><td class="px-5 py-4 text-slate-600">{{ $evaluation['metrics']['matching_accuracy_percent'] ?? '-' }}{{ $evaluation['metrics']['matching_accuracy_percent'] !== null ? '%' : '' }}</td><td class="px-5 py-4 text-slate-600"><span class="block">{{ $evaluation['metrics']['price_history']->count() }} baris harga</span>@foreach ($evaluation['metrics']['price_history']->take(3) as $price)<span class="mt-1 block text-xs text-slate-500">{{ $price->item->name }} · Rp {{ number_format((float) $price->unit_price, 2, ',', '.') }} · {{ $price->receipt->invoice_date->format('d-m-Y') }}</span>@endforeach</td></tr>@empty<tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Belum ada supplier aktif.</td></tr>@endforelse</tbody></table></div>
    </section>
@endsection
