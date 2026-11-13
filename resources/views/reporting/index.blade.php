@extends('layouts.app')

@section('title', 'Laporan')
@section('breadcrumb', 'Pelaporan')
@section('page-heading', 'Laporan')

@section('content')
    <div class="grid gap-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
            <h2 class="font-bold text-slate-900">Laporan operasional dan keuangan</h2>
            <p class="mt-1 text-sm text-slate-500">Pilih laporan yang tersedia untuk peran Anda, lalu tentukan rentang tanggal.</p>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($reports as $key => $report)
                <a href="{{ route('reports.show', $key) }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 transition hover:border-emerald-300 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">{{ $report['requirement'] }}</p>
                            <h2 class="mt-2 font-bold text-slate-900 group-hover:text-emerald-800">{{ $report['title'] }}</h2>
                        </div>
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-emerald-50 font-semibold text-emerald-700" aria-hidden="true">↗</span>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-500">{{ $report['description'] }}</p>
                </a>
            @endforeach
        </section>
    </div>
@endsection
