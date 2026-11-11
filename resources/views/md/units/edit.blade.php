@extends('layouts.app')

@section('title', 'Ubah satuan')
@section('breadcrumb', 'Data master / Satuan / Ubah')
@section('page-heading', 'Ubah satuan')

@section('content')
    <section class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
        <form method="POST" action="{{ route('md.units.update', $unit) }}" class="grid gap-5">@csrf @method('PUT')
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Nama satuan</span><input name="name" value="{{ old('name', $unit->name) }}" required maxlength="30" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">@error('name')<span class="mt-1 block text-sm text-rose-700">{{ $message }}</span>@enderror</label>
            <div class="flex justify-end gap-3"><a href="{{ route('md.units.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</a><button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white">Simpan perubahan</button></div>
        </form>
    </section>
@endsection
