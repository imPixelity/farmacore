@extends('layouts.app')

@section('title', 'Ubah kategori')
@section('breadcrumb', 'Data master / Kategori / Ubah')
@section('page-heading', 'Ubah kategori')

@section('content')
    <section class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
        <form method="POST" action="{{ route('md.categories.update', $category) }}" class="grid gap-5">@csrf @method('PUT')
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Nama</span><input name="name" value="{{ old('name', $category->name) }}" required maxlength="100" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">@error('name')<span class="mt-1 block text-sm text-rose-700">{{ $message }}</span>@enderror</label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Tipe kategori</span><select name="type" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"><option value="obat" @selected(old('type', $category->type) === 'obat')>Obat</option><option value="non_obat" @selected(old('type', $category->type) === 'non_obat')>Non-obat</option></select>@error('type')<span class="mt-1 block text-sm text-rose-700">{{ $message }}</span>@enderror</label>
            <div class="flex justify-end gap-3"><a href="{{ route('md.categories.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</a><button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white">Simpan perubahan</button></div>
        </form>
    </section>
@endsection
