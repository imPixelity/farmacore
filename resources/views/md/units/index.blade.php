@extends('layouts.app')

@section('title', 'Satuan')
@section('breadcrumb', 'Data master / Satuan')
@section('page-heading', 'Satuan barang')

@section('content')
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="font-bold text-slate-900">Daftar satuan</h2></div>
            <div class="overflow-x-auto"><table class="w-full min-w-100 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-6 py-3">Nama</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($units as $unit)
                        <tr><td class="px-6 py-4 font-medium text-slate-900">{{ $unit->name }}</td><td class="px-6 py-4">{{ $unit->is_active ? 'Aktif' : 'Nonaktif' }}</td><td class="px-6 py-4 text-right"><a href="{{ route('md.units.edit', $unit) }}" class="font-semibold text-emerald-700">Ubah</a><form method="POST" action="{{ route('md.units.status', $unit) }}" class="ml-3 inline">@csrf @method('PATCH')<button class="font-semibold text-rose-700">{{ $unit->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></td></tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-10 text-center text-slate-500">Belum ada satuan.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            @if ($units->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $units->links() }}</div>@endif
        </section>
        <section class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2">
            <h2 class="font-bold text-slate-900">Tambah satuan</h2>
            <form method="POST" action="{{ route('md.units.store') }}" class="mt-4 grid gap-4">@csrf
                <label><span class="mb-1.5 block text-sm font-semibold text-slate-700">Nama satuan</span><input name="name" value="{{ old('name') }}" required maxlength="30" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">@error('name')<span class="mt-1 block text-sm text-rose-700">{{ $message }}</span>@enderror</label>
                <button class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">Simpan satuan</button>
            </form>
        </section>
    </div>
@endsection
