@extends('layouts.app')

@section('title', 'Monitor kedaluwarsa')
@section('breadcrumb', 'Persediaan / Monitor kedaluwarsa')
@section('page-heading', 'Monitor kedaluwarsa')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="font-bold text-slate-900">Batch dengan stok tersisa</h2>
                <p class="mt-1 text-sm text-slate-500">
                    @if ($expiryAlertDays === null)
                        Ambang notifikasi belum dikonfigurasi; gunakan filter tanggal untuk mempersempit daftar.
                    @else
                        Notifikasi aktif untuk batch yang kedaluwarsa dalam {{ $expiryAlertDays }} hari atau sudah lewat.
                    @endif
                </p>
            </div>
            <a href="{{ route('inventory.stock.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">Kembali ke persediaan</a>
        </div>

        <form method="GET" action="{{ route('inventory.expiry.index') }}" class="grid gap-3 border-b border-slate-100 p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end sm:px-6">
            <label class="grid gap-1.5 text-xs font-semibold text-slate-600">
                ED mulai
                <input type="date" name="expiry_from" value="{{ $filters['expiry_from'] ?? '' }}" class="rounded-xl border-slate-300 text-sm font-normal text-slate-900 focus:border-emerald-600 focus:ring-emerald-600">
                @error('expiry_from')
                    <span class="font-normal text-rose-700">{{ $message }}</span>
                @enderror
            </label>
            <label class="grid gap-1.5 text-xs font-semibold text-slate-600">
                ED sampai
                <input type="date" name="expiry_to" value="{{ $filters['expiry_to'] ?? '' }}" class="rounded-xl border-slate-300 text-sm font-normal text-slate-900 focus:border-emerald-600 focus:ring-emerald-600">
                @error('expiry_to')
                    <span class="font-normal text-rose-700">{{ $message }}</span>
                @enderror
            </label>
            <div class="flex gap-2">
                <a href="{{ route('inventory.expiry.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Filter</button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-180 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Barang</th>
                        <th class="px-6 py-3 font-semibold">Nomor batch</th>
                        <th class="px-6 py-3 text-right font-semibold">Stok</th>
                        <th class="px-6 py-3 font-semibold">Tanggal ED</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($batches as $batch)
                        @php($expired = $batch->expiry_date->lt(today()))
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                {{ $batch->item->name }}
                                <span class="mt-1 block text-xs font-normal text-slate-500">{{ $batch->item->code }}</span>
                            </td>
                            <td class="px-6 py-4 text-slate-700">{{ $batch->batch_no }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-slate-900">{{ $batch->qty_on_hand }}</td>
                            <td class="px-6 py-4 text-slate-700">{{ $batch->expiry_date->format('d-m-Y') }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $expired ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700' }}">{{ $expired ? 'Kedaluwarsa · tidak dapat dijual' : 'Belum kedaluwarsa' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">Tidak ada batch dengan stok pada rentang tanggal ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($batches->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">{{ $batches->links() }}</div>
        @endif
    </section>
@endsection
