@extends('layouts.app')

@section('title', 'Kebijakan stok')
@section('breadcrumb', 'Persediaan / Kebijakan stok')
@section('page-heading', 'Kebijakan stok')

@section('content')
    <section class="max-w-5xl rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <h2 class="font-bold text-slate-900">Parameter pergerakan barang</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Isi parameter yang telah disetujui apotek. Nilai kosong tidak digunakan dalam perhitungan, sehingga tidak ada angka kebijakan yang diasumsikan oleh sistem.</p>
        </div>

        <form method="POST" action="{{ route('inventory.stock-policies.update') }}" class="p-5 sm:p-6">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($policies as $movementClass => $policy)
                    <fieldset class="rounded-xl border border-slate-200 p-4">
                        <legend class="px-1 text-sm font-bold capitalize text-slate-900">{{ $movementClass }} moving</legend>
                        <label class="mt-2 grid gap-1.5 text-sm font-semibold text-slate-700">
                            Hari stok minimum
                            <input type="number" name="policies[{{ $movementClass }}][min_stock_days]" min="0" max="3650" step="1" value="{{ old("policies.{$movementClass}.min_stock_days", $policy->min_stock_days) }}" class="rounded-xl border-slate-300 font-normal focus:border-emerald-600 focus:ring-emerald-600">
                            @error("policies.{$movementClass}.min_stock_days")
                                <span class="font-normal text-rose-700">{{ $message }}</span>
                            @enderror
                        </label>
                        <label class="mt-4 grid gap-1.5 text-sm font-semibold text-slate-700">
                            Faktor pengaman buffer
                            <input type="number" name="policies[{{ $movementClass }}][safety_factor]" min="0" max="99.99" step="0.01" value="{{ old("policies.{$movementClass}.safety_factor", $policy->safety_factor) }}" class="rounded-xl border-slate-300 font-normal focus:border-emerald-600 focus:ring-emerald-600">
                            @error("policies.{$movementClass}.safety_factor")
                                <span class="font-normal text-rose-700">{{ $message }}</span>
                            @enderror
                        </label>
                    </fieldset>
                @endforeach
            </div>

            @error('policies')
                <p class="mt-3 text-sm text-rose-700">{{ $message }}</p>
            @enderror

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('inventory.stock.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">Kembali ke persediaan</a>
                <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan kebijakan</button>
            </div>
        </form>
    </section>
@endsection
