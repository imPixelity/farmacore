@extends('layouts.app')

@section('title', 'Peta Modul')
@section('breadcrumb', 'Peta modul')
@section('page-heading', 'Peta modul & alur kerja')

@section('content')
    <div class="grid gap-6">
        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-800 px-6 py-8 text-white shadow-lg shadow-emerald-950/10 sm:px-9 sm:py-10">
            <div class="relative z-10 max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200">Navigasi berbasis requirement</p>
                <h2 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Peta modul &amp; alur kerja</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-emerald-100 sm:text-base">
                    Temukan fitur berdasarkan domain ERP atau alur operasional apotek. Daftar yang tampil mengikuti hak akses akun Anda.
                </p>
                <p class="mt-4 text-xs font-medium text-emerald-200">Acuan: 01-MASTER-DOC.md bagian 1.3 dan 4 · Status implementasi: 02-PHASE.md</p>
            </div>
            <div class="pointer-events-none absolute -right-12 -top-24 size-72 rounded-full border-[36px] border-white/5"></div>
            <div class="pointer-events-none absolute -bottom-24 right-36 size-48 rounded-full bg-emerald-700/40"></div>
        </section>

        <section aria-labelledby="workflow-heading">
            <div>
                <p class="text-sm font-medium text-emerald-700">Acuan 01-MASTER-DOC.md §1.3</p>
                <h2 id="workflow-heading" class="mt-1 text-xl font-bold tracking-tight text-slate-950">Alur bisnis utama</h2>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @canany(['purchasing.po.manage', 'inventory.receipt.view', 'purchasing.payable.view'])
                    <a href="#purchasing" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-200 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-emerald-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Pengadaan</p>
                        <p class="mt-2 font-semibold text-slate-900">PO → penerimaan → utang</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">REQ-PUR-01–13 · REQ-INV-03–07 · REQ-ACC-02–03</p>
                    </a>
                @endcanany
                @can('pos.sale.view')
                    <a href="#pos" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-200 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-emerald-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Penjualan</p>
                        <p class="mt-2 font-semibold text-slate-900">Resep / non-resep → POS → pembayaran</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">REQ-POS-01–17 · terkait piutang REQ-ACC-04</p>
                    </a>
                @endcan
                @canany(['rx.prescription.manage', 'rx.patient_history.view'])
                    <a href="#rx" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-200 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-emerald-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Pelayanan resep</p>
                        <p class="mt-2 font-semibold text-slate-900">Pencatatan → screening → penyerahan</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">REQ-RX-01–10</p>
                    </a>
                @endcanany
                @canany(['inventory.stock.view', 'inventory.stock.manage', 'inventory.opname.manage', 'inventory.purchase_return.manage'])
                    <a href="#inventory" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-200 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-emerald-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Pengendalian stok</p>
                        <p class="mt-2 font-semibold text-slate-900">Stok &amp; ED → opname → penyesuaian</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">REQ-INV-01–21</p>
                    </a>
                @endcanany
            </div>
        </section>

        <section aria-labelledby="modules-heading">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Acuan 01-MASTER-DOC.md §4</p>
                    <h2 id="modules-heading" class="mt-1 text-xl font-bold tracking-tight text-slate-950">Modul ERP</h2>
                </div>
                <p class="text-sm text-slate-500">Pilih fitur untuk membuka halaman modul.</p>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                @if (auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->can('core.audit.view') || auth()->user()->can('core.settings.view') || auth()->user()->can('core.data_import.manage'))
                    <article id="core" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02] sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">CORE</p><h3 class="mt-1 text-lg font-bold text-slate-950">Administrasi &amp; kontrol akses</h3></div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">REQ-NFR-SEC-01–06</span>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Autentikasi, pengguna, audit, notifikasi, dan pengaturan sistem.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @can('viewAny', App\Models\User::class)<a href="{{ route('users.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Pengguna</a>@endcan
                            @can('core.audit.view')<a href="{{ route('audit-logs.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Audit log</a>@endcan
                            @can('core.settings.view')<a href="{{ route('settings.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Pengaturan</a>@endcan
                            @can('core.data_import.manage')<a href="{{ route('core.data-imports.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Migrasi data</a>@endcan
                        </div>
                    </article>
                @endif

                @canany(['md.item.view', 'md.item.manage', 'md.price.manage'])
                    <article id="md" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02] sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">MD · MASTER DATA</p><h3 class="mt-1 text-lg font-bold text-slate-950">Data master</h3></div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">REQ-MD-01–17</span>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Barang, satuan, mitra, pasien, layanan, harga, margin, diskon, dan aturan pajak.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @can('md.item.view')
                                <a href="{{ route('md.items.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Barang</a>
                                <a href="{{ route('md.suppliers.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Supplier</a>
                                <a href="{{ route('md.patients.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Pasien</a>
                                <a href="{{ route('md.doctors.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Dokter</a>
                                <a href="{{ route('md.pharmacy-staff.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Tenaga farmasi</a>
                                <a href="{{ route('md.health-services.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Layanan</a>
                            @endcan
                            @can('md.item.manage')<a href="{{ route('md.categories.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Kategori &amp; satuan</a>@endcan
                            @can('md.price.manage')
                                <a href="{{ route('md.prices.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Harga</a>
                                <a href="{{ route('md.margins.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Margin</a>
                                <a href="{{ route('md.taxes.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Pajak</a>
                                <a href="{{ route('md.discounts.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Diskon</a>
                            @endcan
                        </div>
                    </article>
                @endcanany

                @canany(['inventory.stock.view', 'inventory.stock.manage', 'inventory.receipt.view', 'inventory.receipt.manage', 'inventory.opname.manage', 'inventory.purchase_return.manage'])
                    <article id="inventory" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02] sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">INV · INVENTORY &amp; WAREHOUSE</p><h3 class="mt-1 text-lg font-bold text-slate-950">Persediaan &amp; gudang</h3></div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">REQ-INV-01–21</span>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Stok batch, penerimaan, ROP, kedaluwarsa, opname, penyesuaian, dan retur.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @can('inventory.stock.view')
                                <a href="{{ route('inventory.stock.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Stok per batch</a>
                                <a href="{{ route('inventory.expiry.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Monitor kedaluwarsa</a>
                            @endcan
                            @can('inventory.stock.manage')<a href="{{ route('inventory.stock-policies.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">ROP &amp; kebijakan stok</a>@endcan
                            @can('inventory.receipt.view')<a href="{{ route('inventory.receipts.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Penerimaan barang</a>@endcan
                            @can('inventory.opname.manage')<a href="{{ route('inventory.opnames.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Stock opname</a>@endcan
                            @can('inventory.purchase_return.manage')<a href="{{ route('inventory.purchase-returns.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Retur pembelian</a>@endcan
                        </div>
                    </article>
                @endcanany

                @canany(['purchasing.po.manage', 'purchasing.payable.view'])
                    <article id="purchasing" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02] sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">PUR · PURCHASING &amp; SUPPLIER</p><h3 class="mt-1 text-lg font-bold text-slate-950">Pembelian &amp; supplier</h3></div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">REQ-PUR-01–13</span>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Perencanaan, purchase order, pencocokan penerimaan, evaluasi supplier, dan utang.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @can('purchasing.po.manage')
                                <a href="{{ route('purchasing.purchase-orders.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Purchase Order</a>
                                <a href="{{ route('purchasing.supplier-evaluations.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Evaluasi supplier</a>
                            @endcan
                            @can('purchasing.payable.view')<a href="{{ route('purchasing.payables.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Utang &amp; pembayaran</a>@endcan
                        </div>
                    </article>
                @endcanany

                @can('pos.sale.view')
                    <article id="pos" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02] sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">POS · PENJUALAN</p><h3 class="mt-1 text-lg font-bold text-slate-950">Kasir &amp; penjualan</h3></div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">REQ-POS-01–17</span>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Transaksi resep/non-resep, pembayaran, retur, struk, dan layanan pemeriksaan.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('pos.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Buka kasir</a>
                            <a href="{{ route('pos.sales.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Riwayat transaksi</a>
                        </div>
                    </article>
                @endcan

                @canany(['rx.prescription.manage', 'rx.patient_history.view'])
                    <article id="rx" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02] sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">RX · PHARMACY SERVICES</p><h3 class="mt-1 text-lg font-bold text-slate-950">Pelayanan resep &amp; farmasi</h3></div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">REQ-RX-01–10</span>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Pencatatan, screening, penyiapan, peracikan, penyerahan, dan riwayat pelayanan.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @can('rx.prescription.manage')<a href="{{ route('rx.prescriptions.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Antrean resep</a>@endcan
                            @can('rx.patient_history.view')
                                <a href="{{ route('rx.patient-history.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Riwayat pasien</a>
                                <a href="{{ route('rx.staff-activity.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Aktivitas tenaga farmasi</a>
                            @endcan
                        </div>
                    </article>
                @endcanany

                @canany(['accounting.cash.view', 'accounting.receivable.manage', 'accounting.tax.manage'])
                    <article id="accounting" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02] sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">ACC · ACCOUNTING &amp; FINANCE</p><h3 class="mt-1 text-lg font-bold text-slate-950">Akuntansi &amp; keuangan</h3></div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">REQ-ACC-01–10</span>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Kas/bank, piutang, pemasukan/pengeluaran, dan kewajiban pajak.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @can('accounting.cash.view')<a href="{{ route('accounting.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Kas &amp; bank</a>@endcan
                            @can('accounting.receivable.manage')<a href="{{ route('accounting.receivables.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Piutang pelanggan</a>@endcan
                            @can('accounting.tax.manage')<a href="{{ route('accounting.taxes.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Pajak &amp; kewajiban</a>@endcan
                        </div>
                    </article>
                @endcanany

                @canany(['report.view.finance', 'report.view.inventory', 'report.view.purchasing', 'report.view.sales', 'report.view.service'])
                    <article id="reporting" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02] sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">RPT · PELAPORAN</p><h3 class="mt-1 text-lg font-bold text-slate-950">Laporan</h3></div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">REQ-RPT-01–14</span>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Laporan operasional dan keuangan, filter periode, serta ekspor sesuai izin.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('reports.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:border-emerald-300 hover:text-emerald-800">Daftar laporan</a>
                        </div>
                    </article>
                @endcanany
            </div>
        </section>
    </div>
@endsection
