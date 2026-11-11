@extends('layouts.app')

@section('title', 'Pengaturan')
@section('breadcrumb', 'Pengaturan')
@section('page-heading', 'Pengaturan aplikasi')

@section('content')
    <section class="max-w-4xl rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <h2 class="font-bold text-slate-900">Konfigurasi FarmaCore</h2>
            <p class="mt-1 text-sm text-slate-500">Nilai berlaku pada permintaan berikutnya dan disimpan sebagai pengaturan aplikasi.</p>
        </div>

        <form method="POST" action="{{ route('settings.update') }}" class="space-y-5 p-5 sm:p-6">
            @csrf
            @method('PUT')

            @foreach ($settings as $key => $setting)
                <label for="{{ $key }}" class="grid gap-1.5 text-sm font-semibold text-slate-800">
                    {{ $setting['label'] }}
                    @if ($setting['type'] === 'text')
                        <input id="{{ $key }}" type="text" name="{{ $key }}" value="{{ old($key, $setting['value']) }}" class="rounded-xl border-slate-300 font-normal text-slate-900 focus:border-emerald-600 focus:ring-emerald-600">
                    @else
                        <input id="{{ $key }}" type="number" name="{{ $key }}" value="{{ old($key, $setting['value']) }}" step="{{ $key === 'price_rounding_step' ? 'any' : '1' }}" class="rounded-xl border-slate-300 font-normal text-slate-900 focus:border-emerald-600 focus:ring-emerald-600">
                    @endif
                    <span class="font-normal leading-5 text-slate-500">{{ $setting['help'] }}</span>
                    @error($key)
                        <span class="font-normal text-rose-700">{{ $message }}</span>
                    @enderror
                </label>
            @endforeach

            <div class="flex justify-end border-t border-slate-100 pt-5">
                @can('core.settings.update')
                    <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan pengaturan</button>
                @endcan
            </div>
        </form>
    </section>
@endsection
