@extends('layouts.app')

@section('title', 'Layanan pemeriksaan')
@section('breadcrumb', 'Data master / Layanan')
@section('page-heading', 'Layanan pemeriksaan')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6"><div><h2 class="font-bold text-slate-900">Daftar layanan kesehatan</h2><p class="mt-1 text-sm text-slate-500">Kelola nama layanan dan biaya standar.</p></div>@can('md.item.manage')<a href="{{ route('md.health-services.create') }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">Tambah layanan</a>@endcan</div>
        <form method="GET" action="{{ route('md.health-services.index') }}" class="grid gap-3 border-b border-slate-100 p-5 sm:grid-cols-[1fr_12rem_auto] sm:px-6"><input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama layanan" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"><select name="status" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select><button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Cari</button></form>
        <div class="overflow-x-auto"><table class="w-full min-w-120 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-6 py-3">Nama layanan</th><th class="px-6 py-3">Biaya standar</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse ($healthServices as $healthService)
                <tr><td class="px-6 py-4 font-medium text-slate-900">{{ $healthService->name }}</td><td class="px-6 py-4 text-slate-600">Rp {{ number_format((float) $healthService->default_fee, 2, ',', '.') }}</td><td class="px-6 py-4">{{ $healthService->is_active ? 'Aktif' : 'Nonaktif' }}</td><td class="px-6 py-4 text-right">@can('md.item.manage')<a href="{{ route('md.health-services.edit', $healthService) }}" class="font-semibold text-emerald-700">Ubah</a><form method="POST" action="{{ route('md.health-services.status', $healthService) }}" class="ml-3 inline">@csrf @method('PATCH')<button class="font-semibold text-rose-700">{{ $healthService->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>@endcan</td></tr>
            @empty
                <tr><td colspan="4" class="px-6 py-10 text-center text-slate-500">Belum ada layanan.</td></tr>
            @endforelse
        </tbody></table></div>
        @if ($healthServices->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $healthServices->links() }}</div>@endif
    </section>
@endsection
