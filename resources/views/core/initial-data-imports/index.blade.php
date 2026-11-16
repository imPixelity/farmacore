@extends('layouts.app')

@section('title', 'Migrasi data awal')
@section('breadcrumb', 'Migrasi data awal')
@section('page-heading', 'Migrasi data awal')

@section('content')
    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800" role="status">
                <p class="font-semibold">{{ session('success') }}</p>
                @if (is_array(session('import_summary')))
                    <dl class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach (session('import_summary') as $label => $count)
                            <div class="rounded-lg bg-white/70 px-3 py-2">
                                <dt class="text-xs text-emerald-700">{{ str_replace('_', ' ', $label) }}</dt>
                                <dd class="font-bold">{{ $count }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800" role="alert">
                <p class="font-semibold">Berkas belum dapat diproses:</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="font-bold text-slate-900">1. Unduh template</h2>
                <p class="mt-1 text-sm text-slate-500">Gunakan workbook XLSX untuk mengisi semua jenis data, atau template CSV sesuai jenis data.</p>
            </div>
            <div class="flex flex-wrap gap-2 p-5 sm:px-6">
                <a href="{{ route('core.data-imports.template.xlsx') }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Template XLSX lengkap</a>
                <a href="{{ route('core.data-imports.template.csv', 'items') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">CSV barang & konversi</a>
                <a href="{{ route('core.data-imports.template.csv', 'suppliers') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">CSV supplier</a>
                <a href="{{ route('core.data-imports.template.csv', 'stock') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">CSV stok awal</a>
            </div>
            <div class="px-5 pb-5 text-sm text-slate-600 sm:px-6">
                Isi tanggal ED dengan format <code class="rounded bg-slate-100 px-1.5 py-0.5">YYYY-MM-DD</code>. Qty stok dan faktor konversi memakai satuan dasar dan maksimal tiga angka desimal. Kategori dan satuan yang belum ada akan dibuat dari sheet Barang.
            </div>
        </section>

        @if (! $plan || ! $plan['valid'])
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <h2 class="font-bold text-slate-900">2. Periksa berkas</h2>
                    <p class="mt-1 text-sm text-slate-500">Preview hanya membaca data dan tidak menyimpan perubahan. Berkas maksimal 10 MB dan 5.000 baris per sheet.</p>
                </div>
                <form method="POST" action="{{ route('core.data-imports.preview') }}" enctype="multipart/form-data" class="space-y-4 p-5 sm:px-6">
                    @csrf
                    <label class="block max-w-2xl">
                        <span class="mb-1.5 block text-sm font-semibold text-slate-700">Berkas CSV/XLSX</span>
                        <input type="file" name="file" accept=".csv,.xlsx" required class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:font-semibold file:text-emerald-800">
                    </label>
                    <label class="block max-w-sm">
                        <span class="mb-1.5 block text-sm font-semibold text-slate-700">Jenis template CSV</span>
                        <select name="csv_type" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                            <option value="items" @selected(old('csv_type', 'items') === 'items')>Barang & konversi satuan</option>
                            <option value="suppliers" @selected(old('csv_type') === 'suppliers')>Supplier</option>
                            <option value="stock" @selected(old('csv_type') === 'stock')>Stok awal per batch</option>
                        </select>
                        <span class="mt-1 block text-xs text-slate-500">Pilihan ini hanya digunakan saat berkas yang diunggah berformat CSV.</span>
                    </label>
                    <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Validasi & tampilkan preview</button>
                </form>
            </section>
        @endif

        @if ($plan)
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="font-bold text-slate-900">{{ $plan['valid'] ? '3. Konfirmasi impor' : 'Hasil validasi' }}</h2>
                        @if ($previewName)
                            <p class="mt-1 text-sm text-slate-500">Berkas: {{ $previewName }}</p>
                        @endif
                    </div>
                    <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $plan['valid'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                        {{ $plan['valid'] ? 'Valid · belum disimpan' : 'Perlu diperbaiki' }}
                    </span>
                </div>

                @if ($plan['valid'])
                    <div class="grid gap-3 p-5 sm:grid-cols-3 sm:px-6">
                        @foreach ($plan['counts'] as $type => $count)
                            <div class="rounded-xl bg-slate-50 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $type }}</p>
                                <p class="mt-1 text-xl font-bold text-slate-900">{{ $count }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t border-slate-100 px-5 py-4 text-sm text-slate-600 sm:px-6">
                        Barang/supplier yang sudah ada dengan data identik tidak dibuat ulang. Batch stok awal yang sama tidak menambah saldo lagi. Setelah impor, buat stock opname untuk mencocokkan stok fisik sebelum go-live.
                    </div>
                    <div class="flex flex-wrap gap-2 border-t border-slate-100 px-5 py-4 sm:px-6">
                        <form method="POST" action="{{ route('core.data-imports.confirm') }}">
                            @csrf
                            <button class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Konfirmasi & impor</button>
                        </form>
                        <form method="POST" action="{{ route('core.data-imports.cancel') }}">
                            @csrf
                            <button class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batalkan preview</button>
                        </form>
                        <a href="{{ route('inventory.opnames.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Buka stock opname</a>
                    </div>
                @else
                    <ul class="space-y-2 p-5 text-sm text-rose-800 sm:px-6">
                        @foreach ($plan['errors'] as $error)
                            <li class="rounded-lg bg-rose-50 px-3 py-2">{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif

        <section class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900 sm:px-6">
            <h2 class="font-bold">Penting sebelum go-live</h2>
            <p class="mt-1">Impor stok membuat batch dan mutasi <code class="rounded bg-white/70 px-1.5 py-0.5">manual_in</code> bertipe saldo awal. Saldo tidak dapat diedit langsung. Koreksi setelah impor harus dilakukan melalui proses stok/opname yang berwenang; jangan menghapus ledger.</p>
        </section>
    </div>
@endsection
