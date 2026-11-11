@extends('layouts.app')

@section('title', 'Tenaga farmasi')
@section('breadcrumb', 'Data master / Tenaga farmasi')
@section('page-heading', 'Tenaga farmasi')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6"><div><h2 class="font-bold text-slate-900">Daftar tenaga farmasi</h2><p class="mt-1 text-sm text-slate-500">Setiap profil terhubung ke akun dengan peran tenaga farmasi.</p></div>@can('md.item.manage')<a href="{{ route('md.pharmacy-staff.create') }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">Tambah tenaga farmasi</a>@endcan</div>
        <form method="GET" action="{{ route('md.pharmacy-staff.index') }}" class="grid gap-3 border-b border-slate-100 p-5 sm:grid-cols-[1fr_12rem_auto] sm:px-6"><input type="search" name="search" value="{{ request('search') }}" placeholder="Nama, jabatan, username" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"><select name="status" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select><button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Cari</button></form>
        <div class="overflow-x-auto"><table class="w-full min-w-150 text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-6 py-3">Tenaga farmasi</th><th class="px-6 py-3">Akun</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse ($pharmacyStaff as $staff)
                <tr><td class="px-6 py-4 font-medium text-slate-900">{{ $staff->name }}<span class="mt-1 block text-xs font-normal text-slate-500">{{ $staff->position }}</span></td><td class="px-6 py-4 text-slate-600">{{ $staff->user->name }}<span class="mt-1 block text-xs">{{ $staff->user->username }}</span></td><td class="px-6 py-4">{{ $staff->is_active ? 'Aktif' : 'Nonaktif' }}</td><td class="px-6 py-4 text-right">@can('md.item.manage')<a href="{{ route('md.pharmacy-staff.edit', $staff) }}" class="font-semibold text-emerald-700">Ubah</a><form method="POST" action="{{ route('md.pharmacy-staff.status', $staff) }}" class="ml-3 inline">@csrf @method('PATCH')<button class="font-semibold text-rose-700">{{ $staff->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>@endcan</td></tr>
            @empty
                <tr><td colspan="4" class="px-6 py-10 text-center text-slate-500">Belum ada tenaga farmasi.</td></tr>
            @endforelse
        </tbody></table></div>
        @if ($pharmacyStaff->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $pharmacyStaff->links() }}</div>@endif
    </section>
@endsection
