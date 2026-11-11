@extends('layouts.app')

@section('title', 'Riwayat pasien')
@section('breadcrumb', 'Resep & layanan / Riwayat pasien')
@section('page-heading', 'Riwayat pasien')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4"><div><h2 class="font-bold">Cari pasien</h2><p class="mt-1 text-sm text-slate-500">Pembukaan detail riwayat dicatat dalam audit log.</p></div><form class="flex gap-2"><input name="search" value="{{ request('search') }}" placeholder="Nama atau kode pasien" class="rounded-xl border-slate-300 text-sm"><button class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Cari</button></form></div><div class="divide-y divide-slate-100">@forelse ($patients as $patient)<a href="{{ route('rx.patient-history.show', $patient) }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-slate-50"><div><p class="font-semibold">{{ $patient->name }}</p><p class="text-xs text-slate-500">{{ $patient->code }} · {{ $patient->maskedPhone() ?: 'Kontak disembunyikan' }}</p></div><span class="text-sm font-semibold text-emerald-700">Buka riwayat →</span></a>@empty<p class="px-5 py-10 text-center text-slate-500">Pasien tidak ditemukan.</p>@endforelse</div>@if ($patients->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $patients->links() }}</div>@endif</section>
@endsection
