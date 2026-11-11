@extends('layouts.app')

@section('title', 'Pengaturan margin')
@section('breadcrumb', 'Data master / Harga / Margin')
@section('page-heading', 'Pengaturan margin')

@section('content')
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
            <h2 class="font-bold text-slate-900">Simpan margin</h2>
            <p class="mt-1 text-sm text-slate-500">Prioritas perhitungan: barang, kategori, lalu global. Perubahan menerbitkan histori harga baru untuk barang yang sudah memiliki HNA.</p>
            <form method="POST" action="{{ route('md.margins.store') }}" class="mt-5 grid gap-4" x-data="{ scope: @js(old('scope_type', 'global')) }">
                @csrf
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Cakupan
                    <select name="scope_type" x-model="scope" class="rounded-xl border-slate-300 bg-white font-normal">
                        <option value="global">Global</option><option value="category">Kategori</option><option value="item">Barang</option>
                    </select>
                    @error('scope_type')<span class="text-rose-700">{{ $message }}</span>@enderror
                </label>
                <label x-show="scope === 'category'" class="grid gap-1.5 text-sm font-semibold text-slate-700">Kategori
                    <select name="scope_id" :disabled="scope !== 'category'" class="rounded-xl border-slate-300 bg-white font-normal">
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) old('scope_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach
                    </select>
                </label>
                <label x-show="scope === 'item'" class="grid gap-1.5 text-sm font-semibold text-slate-700">Barang
                    <select name="scope_id" :disabled="scope !== 'item'" class="rounded-xl border-slate-300 bg-white font-normal">
                        <option value="">Pilih barang</option>
                        @foreach ($items as $item)<option value="{{ $item->id }}" @selected((string) old('scope_id') === (string) $item->id)>{{ $item->code }} — {{ $item->name }}</option>@endforeach
                    </select>
                </label>
                @error('scope_id')<span class="text-sm text-rose-700">{{ $message }}</span>@enderror
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Jenis penjualan
                    <select name="sales_type" required class="rounded-xl border-slate-300 bg-white font-normal">
                        @foreach ($salesTypes as $value => $label)<option value="{{ $value }}" @selected(old('sales_type') === $value)>{{ $label }}</option>@endforeach
                    </select>
                    @error('sales_type')<span class="text-rose-700">{{ $message }}</span>@enderror
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Margin (%)
                    <input type="number" name="margin_percent" min="0" max="9999.99" step="0.01" value="{{ old('margin_percent') }}" required class="rounded-xl border-slate-300 font-normal">
                    @error('margin_percent')<span class="text-rose-700">{{ $message }}</span>@enderror
                </label>
                <button class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white">Simpan margin</button>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-900">Margin tersimpan</h2></div>
            <div class="overflow-x-auto"><table class="w-full min-w-150 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Cakupan</th><th class="px-5 py-3">Jenis penjualan</th><th class="px-5 py-3">Margin</th><th class="px-5 py-3">Diperbarui</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($marginSettings as $setting)
                        <tr><td class="px-5 py-4">{{ match ($setting->scope_type) { 'item' => 'Barang: '.$setting->item?->name, 'category' => 'Kategori: '.$setting->category?->name, default => 'Global' } }}</td><td class="px-5 py-4">{{ $salesTypes[$setting->sales_type] }}</td><td class="px-5 py-4 font-semibold">{{ $setting->margin_percent }}%</td><td class="px-5 py-4 text-slate-500">{{ $setting->updated_at->format('d-m-Y H:i') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-slate-500">Belum ada margin. Harga tidak akan dihitung sebelum margin dikonfigurasi.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>
    </div>
@endsection
