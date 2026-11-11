@extends('layouts.app')

@section('title', 'Rincian batch stok')
@section('breadcrumb', 'Persediaan / Rincian batch')
@section('page-heading', 'Rincian batch stok')

@section('content')
    <div class="grid gap-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <a href="{{ route('inventory.stock.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">← Kembali ke persediaan</a>
                    <h2 class="mt-3 text-xl font-bold text-slate-900">{{ $item->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $item->code }} · {{ $item->baseUnit->name }} sebagai satuan dasar</p>
                </div>
                <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $item->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $item->is_active ? 'Barang aktif' : 'Barang nonaktif' }}</span>
            </div>
            <dl class="mt-6 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total stok semua batch</dt>
                    <dd class="mt-1 text-lg font-bold text-slate-900">{{ $stockTotal }} {{ $item->baseUnit->name }}</dd>
                </div>
                <div class="rounded-xl bg-emerald-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Stok tersedia (belum kedaluwarsa)</dt>
                    <dd class="mt-1 text-lg font-bold text-emerald-900">{{ $availableTotal }} {{ $item->baseUnit->name }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="font-bold text-slate-900">Batch barang</h2>
                <p class="mt-1 text-sm text-slate-500">Saldo setiap batch merupakan hasil posting mutasi dan tidak dapat diedit dari layar ini.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-150 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Nomor batch</th>
                            <th class="px-6 py-3 font-semibold">Tanggal kedaluwarsa</th>
                            <th class="px-6 py-3 text-right font-semibold">Stok ({{ $item->baseUnit->name }})</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($batches as $batch)
                            @php($expired = $batch->expiry_date->lt(today()))
                            <tr>
                                <td class="px-6 py-4 font-semibold text-slate-900">{{ $batch->batch_no }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $batch->expiry_date->format('d-m-Y') }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-900">{{ $batch->qty_on_hand }}</td>
                                <td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $expired ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $expired ? 'Kedaluwarsa' : 'Belum kedaluwarsa' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10 text-center text-slate-500">Belum ada batch untuk barang ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($batches->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">{{ $batches->links() }}</div>
            @endif
        </section>
    </div>
@endsection
