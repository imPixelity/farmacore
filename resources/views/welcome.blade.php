@extends('layouts.app')

@section('title', 'Ringkasan')
@section('breadcrumb', 'Beranda')
@section('page-heading', 'Selamat datang di FarmaCore')

@section('content')
    <section class="relative overflow-hidden rounded-3xl bg-emerald-900 px-6 py-8 text-white sm:px-9 sm:py-10">
        <div class="relative z-10 max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200">Fondasi sistem siap</p>
            <h2 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Operasional apotek, lebih terstruktur.</h2>
            <p class="mt-3 max-w-xl text-sm leading-6 text-emerald-100 sm:text-base">
                FarmaCore sedang disiapkan. Modul data, persediaan, pembelian, penjualan, resep, dan laporan akan dibangun bertahap mengikuti roadmap proyek.
            </p>
        </div>
        <div class="pointer-events-none absolute -right-12 -top-24 size-72 rounded-full border-[36px] border-emerald-800/70"></div>
        <div class="pointer-events-none absolute -bottom-24 right-36 size-48 rounded-full bg-emerald-800/70"></div>
    </section>

    <section aria-labelledby="status-heading">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-emerald-700">Gambaran umum</p>
                <h2 id="status-heading" class="mt-1 text-xl font-bold tracking-tight text-slate-950">Status modul</h2>
            </div>
            <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">Tahap fondasi</span>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['Data master', 'MD', 'Fondasi data barang dan mitra'], ['Persediaan', 'INV', 'Stok batch dan pergerakan'], ['Pembelian', 'PUR', 'Pemesanan ke supplier'], ['Penjualan', 'POS', 'Kasir dan transaksi']] as [$module, $code, $description])
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02]">
                    <div class="flex items-center justify-between gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-emerald-50 text-xs font-bold text-emerald-800">{{ $code }}</span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-500">Direncanakan</span>
                    </div>
                    <h3 class="mt-5 font-semibold text-slate-900">{{ $module }}</h3>
                    <p class="mt-1 text-sm leading-5 text-slate-500">{{ $description }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-[1.3fr_0.7fr]">
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/[0.02]">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Langkah berikutnya</p>
                    <h2 class="mt-1 text-lg font-bold tracking-tight text-slate-950">Roadmap pengembangan</h2>
                </div>
                <span class="hidden rounded-xl bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 sm:inline-flex">Phase 1 / 28</span>
            </div>
            <ol class="mt-5 space-y-4">
                <li class="flex gap-3">
                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-800">1</span>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Fondasi proyek dan tooling</p>
                        <p class="mt-1 text-sm text-slate-500">Laravel, struktur modul, aset, dan pengujian dasar.</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-slate-100 text-xs font-bold text-slate-500">2</span>
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Autentikasi dan hak akses</p>
                        <p class="mt-1 text-sm text-slate-500">Pengguna, role, permission, dan kebijakan akses.</p>
                    </div>
                </li>
                <li class="flex gap-3">
                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-slate-100 text-xs font-bold text-slate-500">3</span>
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Audit dan pengaturan</p>
                        <p class="mt-1 text-sm text-slate-500">Pencatatan aktivitas dan konfigurasi aplikasi.</p>
                    </div>
                </li>
            </ol>
        </article>

        <aside class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-6">
            <span class="grid size-11 place-items-center rounded-xl bg-white text-xl text-emerald-800">✓</span>
            <h2 class="mt-4 text-lg font-bold tracking-tight text-slate-950">Siap dikembangkan</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Menu modul saat ini merupakan placeholder. Data operasional baru akan ditampilkan setelah fitur dan alur bisnisnya selesai dibangun.
            </p>
        </aside>
    </section>
@endsection
