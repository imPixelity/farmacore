@extends('layouts.app')

@section('title', 'Riwayat pasien')
@section('breadcrumb', 'Resep & layanan / Riwayat pasien')
@section('page-heading', 'Riwayat pasien')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 px-5 py-4"><a href="{{ route('rx.patient-history.index') }}" class="text-sm font-semibold text-emerald-700">← Kembali ke daftar pasien</a><div class="mt-3 flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-xl font-bold">{{ $patient->name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $patient->code }} · Identitas sensitif ditampilkan sesuai izin akses.</p></div><span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">Riwayat diakses</span></div></div><div class="divide-y divide-slate-100">@forelse ($timeline as $event)<div class="flex gap-4 px-5 py-4"><div class="mt-1 size-2 shrink-0 rounded-full bg-emerald-600"></div><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ \Carbon\CarbonImmutable::parse($event['date'])->format('d-m-Y H:i') }} · {{ $event['type'] }}</p><p class="mt-1 font-semibold">{{ $event['title'] }}</p><p class="mt-1 text-sm text-slate-600">{{ $event['description'] }}</p></div></div>@empty<p class="px-5 py-10 text-center text-slate-500">Belum ada riwayat pelayanan.</p>@endforelse</div></section>
@endsection
