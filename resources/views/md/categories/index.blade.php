@extends('layouts.app')

@section('title', 'Kategori barang')
@section('breadcrumb', 'Data master / Kategori')
@section('page-heading', 'Kategori barang')

@section('content')
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="font-bold text-slate-900">Daftar kategori</h2></div>
            <div class="overflow-x-auto"><table class="w-full min-w-130 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-6 py-3">Nama</th><th class="px-6 py-3">Tipe</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        <tr><td class="px-6 py-4 font-medium text-slate-900">{{ $category->name }}</td><td class="px-6 py-4 text-slate-600">{{ str($category->type)->replace('_', ' ')->title() }}</td><td class="px-6 py-4">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</td><td class="px-6 py-4 text-right"><a href="{{ route('md.categories.edit', $category) }}" class="font-semibold text-emerald-700">Ubah</a><form method="POST" action="{{ route('md.categories.status', $category) }}" class="ml-3 inline">@csrf @method('PATCH')<button class="font-semibold text-rose-700">{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></td></tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-10 text-center text-slate-500">Belum ada kategori.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            @if ($categories->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $categories->links() }}</div>@endif
        </section>
        <section class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2">
            <h2 class="font-bold text-slate-900">Tambah kategori</h2>
            <form method="POST" action="{{ route('md.categories.store') }}" class="mt-4 grid gap-4">@csrf
                <label><span class="mb-1.5 block text-sm font-semibold text-slate-700">Nama</span><input name="name" value="{{ old('name') }}" required maxlength="100" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">@error('name')<span class="mt-1 block text-sm text-rose-700">{{ $message }}</span>@enderror</label>
                <label><span class="mb-1.5 block text-sm font-semibold text-slate-700">Tipe kategori</span><select name="type" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="obat" @selected(old('type') === 'obat')>Obat</option><option value="non_obat" @selected(old('type') === 'non_obat')>Non-obat</option></select>@error('type')<span class="mt-1 block text-sm text-rose-700">{{ $message }}</span>@enderror</label>
                <button class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">Simpan kategori</button>
            </form>
        </section>
    </div>
@endsection
