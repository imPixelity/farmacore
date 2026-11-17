@extends('layouts.app')

@section('title', 'Ringkasan')
@section('breadcrumb', 'Beranda')
@section('page-heading', 'Ringkasan operasional')

@section('content')
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-800 px-6 py-8 text-white shadow-lg shadow-emerald-950/10 sm:px-9 sm:py-10">
        <div class="relative z-10 max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200">FarmaCore · Operasional apotek</p>
            <h2 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Selamat datang, {{ $user->name }}</h2>
            <p class="mt-3 max-w-xl text-sm leading-6 text-emerald-100 sm:text-base">
                Pantau aktivitas dan lanjutkan pekerjaan dari modul yang tersedia untuk akun Anda.
            </p>
        </div>
        <div class="relative z-10 mt-6 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3.5 py-2 text-xs font-medium text-emerald-50 backdrop-blur-sm">
            <span class="size-2 rounded-full bg-emerald-300"></span>
            Ringkasan diperbarui {{ now()->format('d/m/Y H:i') }}
        </div>
        <div class="pointer-events-none absolute -right-12 -top-24 size-72 rounded-full border-[36px] border-white/5"></div>
        <div class="pointer-events-none absolute -bottom-24 right-36 size-48 rounded-full bg-emerald-700/40"></div>
    </section>

    <section aria-labelledby="metrics-heading">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-emerald-700">Hari ini</p>
                <h2 id="metrics-heading" class="mt-1 text-xl font-bold tracking-tight text-slate-950">Gambaran operasional</h2>
            </div>
            <p class="text-sm text-slate-500">Ringkasan disesuaikan dengan hak akses Anda.</p>
        </div>

        @if ($metrics !== [])
            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($metrics as $metric)
                    <a href="{{ $metric['url'] }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02] transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-emerald-100">
                        <div class="flex items-start justify-between gap-3">
                            <span class="grid size-10 place-items-center rounded-xl bg-emerald-50 text-xs font-bold text-emerald-800">{{ $metric['symbol'] }}</span>
                            <span class="text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-emerald-700" aria-hidden="true">→</span>
                        </div>
                        <p class="mt-5 text-sm font-medium text-slate-500">{{ $metric['label'] }}</p>
                        <p class="mt-1 text-3xl font-bold tracking-tight text-slate-950">{{ $metric['value'] }}</p>
                        <p class="mt-2 text-sm text-slate-500">{{ $metric['description'] }}</p>
                    </a>
                @endforeach
            </div>
        @else
            <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
                Belum ada ringkasan operasional yang tersedia untuk akun ini.
            </div>
        @endif
    </section>

    @if ($canViewSales)
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/[0.02]" aria-labelledby="sales-heading">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-5 sm:px-6">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Penjualan</p>
                    <h2 id="sales-heading" class="mt-1 text-lg font-bold tracking-tight text-slate-950">Transaksi terbaru</h2>
                </div>
                <a href="{{ route('pos.sales.index') }}" class="text-sm font-semibold text-emerald-800 hover:text-emerald-950">Lihat semua transaksi <span aria-hidden="true">→</span></a>
            </div>

            @if ($recentSales->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[38rem] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-semibold sm:px-6">Nomor transaksi</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Waktu</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Pembayaran</th>
                                <th scope="col" class="px-5 py-3 text-right font-semibold sm:px-6">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($recentSales as $sale)
                                <tr class="text-slate-700">
                                    <td class="px-5 py-4 font-semibold text-slate-900 sm:px-6">{{ $sale->invoice_no }}</td>
                                    <td class="px-5 py-4 text-slate-500">{{ $sale->sale_date->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-4"><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800">{{ str_replace('_', ' ', $sale->payment_status) }}</span></td>
                                    <td class="px-5 py-4 text-right font-semibold text-slate-900 sm:px-6">Rp {{ number_format((float) $sale->grand_total, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="px-5 py-8 text-center text-sm text-slate-500 sm:px-6">Belum ada transaksi penjualan.</p>
            @endif
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02] sm:p-6" aria-labelledby="notifications-heading">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-emerald-700">Pusat notifikasi</p>
                <h2 id="notifications-heading" class="mt-1 text-lg font-bold tracking-tight text-slate-950">Ringkasan notifikasi</h2>
            </div>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">{{ $notificationSummary['unreadCount'] }} belum dibaca</span>
        </div>
        <ul class="mt-4 divide-y divide-slate-100">
            @forelse ($notificationSummary['items'] as $notification)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div>
                        <p class="text-sm font-medium text-slate-800">{{ data_get($notification->data, 'message', 'Notifikasi baru') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    @if (! $notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs font-semibold text-emerald-800 hover:text-emerald-950">Tandai dibaca</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="py-3 text-sm text-slate-500">Belum ada notifikasi.</li>
            @endforelse
        </ul>
    </section>
@endsection
