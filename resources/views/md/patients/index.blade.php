@extends('layouts.app')

@section('title', 'Pasien')
@section('breadcrumb', 'Data master / Pasien')
@section('page-heading', 'Pasien')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div><h2 class="font-bold text-slate-900">Daftar pasien</h2><p class="mt-1 text-sm text-slate-500">Identitas dan kontak ditampilkan penuh hanya untuk peran berwenang.</p></div>
            @can('md.item.manage')<a href="{{ route('md.patients.create') }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">Tambah pasien</a>@endcan
        </div>
        <form method="GET" action="{{ route('md.patients.index') }}" class="grid gap-3 border-b border-slate-100 p-5 sm:grid-cols-[1fr_12rem_auto] sm:px-6">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Nama atau kode pasien{{ $canViewSensitiveData ? ', identitas' : '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
            <select name="status" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select>
            <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Cari</button>
        </form>
        <div class="overflow-x-auto"><table class="w-full min-w-230 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-6 py-3">Pasien</th><th class="px-6 py-3">Nomor identitas</th><th class="px-6 py-3">Telepon</th><th class="px-6 py-3">Alamat</th><th class="px-6 py-3">Tanggal lahir</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($patients as $patient)
                    <tr><td class="px-6 py-4 font-medium text-slate-900">{{ $patient->name }}<span class="mt-1 block text-xs font-normal text-slate-500">{{ $patient->code }}</span></td>
                        <td class="px-6 py-4 text-slate-600">{{ $canViewSensitiveData ? ($patient->identity_no ?: '—') : ($patient->maskedIdentityNo() ?: '—') }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $canViewSensitiveData ? ($patient->phone ?: '—') : ($patient->maskedPhone() ?: '—') }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $canViewSensitiveData ? ($patient->address ?: '—') : ($patient->maskedAddress() ?: '—') }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $patient->birth_date ? ($canViewSensitiveData ? $patient->birth_date->format('d-m-Y') : $patient->maskedBirthDate()) : '—' }}</td>
                        <td class="px-6 py-4">{{ $patient->is_active ? 'Aktif' : 'Nonaktif' }}</td><td class="px-6 py-4 text-right">@can('md.item.manage')<a href="{{ route('md.patients.edit', $patient) }}" class="font-semibold text-emerald-700">Ubah</a><form method="POST" action="{{ route('md.patients.status', $patient) }}" class="ml-3 inline">@csrf @method('PATCH')<button class="font-semibold text-rose-700">{{ $patient->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>@endcan</td></tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-10 text-center text-slate-500">Belum ada pasien yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if ($patients->hasPages())<div class="border-t border-slate-100 px-6 py-4">{{ $patients->links() }}</div>@endif
    </section>
@endsection
