@extends('layouts.app')

@section('title', 'Supplier')
@section('breadcrumb', 'Data master / Supplier')
@section('page-heading', 'Supplier / PBF')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div><h2 class="font-bold text-slate-900">Daftar supplier</h2><p class="mt-1 text-sm text-slate-500">Kelola informasi kontak dan ketentuan pembayaran.</p></div>
            @can('md.item.manage')<a href="{{ route('md.suppliers.create') }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">Tambah supplier</a>@endcan
        </div>
        <form method="GET" action="{{ route('md.suppliers.index') }}" class="grid gap-3 border-b border-slate-100 p-5 sm:grid-cols-[1fr_12rem_auto] sm:px-6">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Nama, kontak, telepon, email" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
            <select name="status" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select>
            <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Cari</button>
        </form>
        <div class="overflow-x-auto"><table class="w-full min-w-190 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-6 py-3">Supplier</th><th class="px-6 py-3">Kontak</th><th class="px-6 py-3">Pembayaran</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($suppliers as $supplier)
                    <tr><td class="px-6 py-4 font-medium text-slate-900">{{ $supplier->name }}<span class="mt-1 block text-xs font-normal text-slate-500">{{ $supplier->address ?: 'Alamat belum diisi' }}</span></td><td class="px-6 py-4 text-slate-600">{{ $supplier->contact_person ?: '—' }}<span class="mt-1 block text-xs">{{ $supplier->phone ?: '—' }} · {{ $supplier->email ?: '—' }}</span></td><td class="px-6 py-4 text-slate-600">{{ $supplier->payment_terms ?: '—' }}<span class="mt-1 block text-xs">Tenor: {{ $supplier->payment_term_days ?? '—' }} hari · Lead time: {{ $supplier->lead_time_days ?? '—' }} hari</span></td><td class="px-6 py-4">{{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}</td><td class="px-6 py-4 text-right">@can('md.item.manage')<a href="{{ route('md.suppliers.edit', $supplier) }}" class="font-semibold text-emerald-700">Ubah</a><form method="POST" action="{{ route('md.suppliers.status', $supplier) }}" class="ml-3 inline">@csrf @method('PATCH')<button class="font-semibold text-rose-700">{{ $supplier->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>@endcan</td></tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">Belum ada supplier.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if ($suppliers->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $suppliers->links() }}</div>@endif
    </section>
@endsection
