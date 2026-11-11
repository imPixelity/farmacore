@extends('layouts.app')

@section('title', 'Barang')
@section('breadcrumb', 'Data master / Barang')
@section('page-heading', 'Barang')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div><h2 class="font-bold text-slate-900">Daftar barang</h2><p class="mt-1 text-sm text-slate-500">Cari berdasarkan nama, kode internal, atau barcode.</p></div>
            @can('md.item.manage')<a href="{{ route('md.items.create') }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Tambah barang</a>@endcan
        </div>
        <form method="GET" action="{{ route('md.items.index') }}" class="grid gap-3 border-b border-slate-100 p-5 sm:grid-cols-4 sm:px-6">
            <label class="sm:col-span-2"><span class="mb-1.5 block text-xs font-semibold text-slate-600">Pencarian</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Nama, kode, barcode" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100"></label>
            <label><span class="mb-1.5 block text-xs font-semibold text-slate-600">Kategori</span><select name="category_id" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Semua kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label><span class="mb-1.5 block text-xs font-semibold text-slate-600">Status</span><select name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Semua</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select></label>
            <div class="flex gap-2 sm:col-span-4 sm:justify-end"><a href="{{ route('md.items.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a><button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Cari</button></div>
        </form>
        <div class="overflow-x-auto"><table class="w-full min-w-210 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-6 py-3 font-semibold">Barang</th><th class="px-6 py-3 font-semibold">Kategori</th><th class="px-6 py-3 font-semibold">Barcode</th><th class="px-6 py-3 font-semibold">Satuan</th><th class="px-6 py-3 font-semibold">Status</th><th class="px-6 py-3 text-right font-semibold">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($items as $item)
                    <tr><td class="px-6 py-4 font-medium text-slate-900">{{ $item->name }}<span class="mt-1 block text-xs font-normal text-slate-500">{{ $item->code }} · {{ str($item->item_type)->replace('_', ' ')->title() }}</span></td><td class="px-6 py-4 text-slate-600">{{ $item->category->name }}</td><td class="px-6 py-4 text-slate-600">{{ $item->barcode ?: '—' }}</td><td class="px-6 py-4 text-slate-600">{{ $item->baseUnit->name }} <span class="text-slate-400">/ {{ $item->orderUnit->name }}</span></td><td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="px-6 py-4 text-right">@can('md.item.manage')<a href="{{ route('md.items.edit', $item) }}" class="font-semibold text-emerald-700 hover:text-emerald-900">Ubah</a><form method="POST" action="{{ route('md.items.status', $item) }}" class="ml-3 inline">@csrf @method('PATCH')<button class="font-semibold text-rose-700 hover:text-rose-900">{{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>@endcan</td></tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-10 text-center text-slate-500">Belum ada barang yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if ($items->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $items->links() }}</div>@endif
    </section>
@endsection
