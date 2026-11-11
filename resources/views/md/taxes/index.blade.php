@extends('layouts.app')

@section('title', 'Pengaturan pajak')
@section('breadcrumb', 'Data master / Harga / Pajak')
@section('page-heading', 'Pengaturan pajak')

@section('content')
    <div class="grid gap-5 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
            <h2 class="font-bold text-slate-900">Tambah tarif pajak</h2>
            <p class="mt-1 text-sm text-slate-500">Tarif bersifat historis dan mulai berlaku pada tanggal yang dipilih. Tarif tidak di-hardcode.</p>
            <form method="POST" action="{{ route('md.taxes.rates.store') }}" class="mt-5 grid gap-4">
                @csrf
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Nama pajak<input name="name" value="PPN" readonly class="rounded-xl border-slate-300 bg-slate-50 font-normal">@error('name')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror</label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Tarif (%)<input type="number" name="rate_percent" min="0" max="100" step="0.01" value="{{ old('rate_percent') }}" required class="rounded-xl border-slate-300 font-normal">@error('rate_percent')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror</label>
                <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Berlaku mulai<input type="date" name="effective_from" value="{{ old('effective_from', today()->toDateString()) }}" required class="rounded-xl border-slate-300 font-normal">@error('effective_from')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror</label>
                <button class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white">Tambahkan tarif</button>
            </form>
            <p class="mt-4 text-xs text-slate-500">Tarif yang sudah disimpan tidak dapat diubah atau dihapus; buat tarif baru untuk periode berikutnya atau koreksi sesuai tanggal berlaku.</p>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-900">Histori tarif</h2></div>
            <div class="overflow-x-auto"><table class="w-full min-w-100 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Pajak</th><th class="px-5 py-3">Tarif</th><th class="px-5 py-3">Berlaku mulai</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($taxSettings as $setting)
                        <tr><td class="px-5 py-4">{{ $setting->name }}</td><td class="px-5 py-4 font-semibold">{{ $setting->rate_percent }}%</td><td class="px-5 py-4">{{ $setting->effective_from->format('d-m-Y') }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-slate-500">Tarif PPN belum dikonfigurasi. Barang kena pajak belum dapat dihitung harganya.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6 lg:col-span-2">
            <h2 class="font-bold text-slate-900">Default status PPN barang</h2>
            <p class="mt-1 text-sm text-slate-500">Default diterapkan pada pilihan awal saat membuat barang baru. Perubahan tidak menimpa pilihan pajak barang yang sudah ada.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                @foreach ($itemTypes as $itemType => $label)
                    @php($rule = $taxRules->get($itemType))
                    <form method="POST" action="{{ $rule ? route('md.taxes.rules.update', $rule) : route('md.taxes.rules.store') }}" class="grid gap-3 rounded-xl border border-slate-200 p-4">
                        @csrf
                        @if ($rule)@method('PUT')@endif
                        <input type="hidden" name="item_type" value="{{ $itemType }}">
                        <strong class="text-sm text-slate-800">{{ $label }}</strong>
                        <select name="is_taxable" required class="rounded-xl border-slate-300 bg-white text-sm"><option value="1" @selected(old('is_taxable', $rule?->is_taxable ? '1' : '') === '1')>Kena PPN</option><option value="0" @selected(old('is_taxable', $rule ? ($rule->is_taxable ? '1' : '0') : '') === '0')>Tidak kena PPN</option></select>
                        @error('is_taxable')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror
                        <button class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">{{ $rule ? 'Perbarui default' : 'Simpan default' }}</button>
                    </form>
                @endforeach
            </div>
        </section>
    </div>
@endsection
