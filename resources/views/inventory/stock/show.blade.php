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

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-900">Kebijakan dan rekomendasi stok</h2>
                    <p class="mt-1 text-sm text-slate-500">Klasifikasi: {{ $item->movement_class ? ucfirst($item->movement_class).' moving' : 'belum ditetapkan' }} · Supplier/lead time terakhir: {{ $stockPolicy['lead_time_days'] ?? 'belum tersedia' }} hari</p>
                </div>
                @can('inventory.stock.manage')
                    <a href="{{ route('inventory.stock-policies.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">Atur parameter klasifikasi</a>
                @endcan
            </div>
            <dl class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rata-rata pemakaian / bulan</dt><dd class="mt-1 font-bold text-slate-900">{{ $stockPolicy['average_monthly_usage'] ?? '—' }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stok minimum / ROP</dt><dd class="mt-1 font-bold text-slate-900">{{ $stockPolicy['min_stock'] ?? '—' }} {{ $item->baseUnit->name }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Buffer stock</dt><dd class="mt-1 font-bold text-slate-900">{{ $stockPolicy['buffer_stock'] ?? '—' }} {{ $item->baseUnit->name }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kekurangan buffer</dt><dd class="mt-1 font-bold text-slate-900">{{ $stockPolicy['kurang_buffer'] ?? '—' }} {{ $item->baseUnit->name }}</dd></div>
            </dl>
            @if ($stockPolicy['average_monthly_usage'] === null)
                <p class="mt-4 text-sm text-amber-800">Belum ada riwayat pemakaian. Tetapkan stok minimum manual atau tunggu transaksi pemakaian tercatat.</p>
            @endif
            @can('inventory.stock.manage')
                <form method="POST" action="{{ route('inventory.stock.policy.update', $item) }}" class="mt-5 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                    @csrf
                    @method('PUT')
                    <label class="grid gap-1.5 text-sm font-semibold text-slate-700">
                        Klasifikasi pergerakan
                        <select name="movement_class" class="rounded-xl border-slate-300 font-normal focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="">Belum ditetapkan</option>
                            @foreach (['fast' => 'Fast moving', 'medium' => 'Medium moving', 'slow' => 'Slow moving'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('movement_class', $item->movement_class) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('movement_class')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror
                    </label>
                    <label class="grid gap-1.5 text-sm font-semibold text-slate-700">
                        Override stok minimum ({{ $item->baseUnit->name }})
                        <input type="number" name="min_stock_override" min="0" step="0.001" value="{{ old('min_stock_override', $item->min_stock_override) }}" class="rounded-xl border-slate-300 font-normal focus:border-emerald-600 focus:ring-emerald-600">
                        @error('min_stock_override')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror
                    </label>
                    <button class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan</button>
                </form>
            @endcan
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
