<nav class="flex-1 space-y-2 overflow-y-auto px-4 py-6">
    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Navigasi</p>
    <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold {{ request()->routeIs('home') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}" @if (request()->routeIs('home')) aria-current="page" @endif>
        <span class="grid size-8 place-items-center rounded-lg bg-white text-emerald-700"><x-icon name="house" /></span>
        Ringkasan
    </a>
    <a href="{{ route('modules.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold {{ request()->routeIs('modules.index') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}" @if (request()->routeIs('modules.index')) aria-current="page" @endif>
        <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="map" /></span>
        Peta modul
    </a>

    @if (auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->can('core.audit.view') || auth()->user()->can('core.settings.view') || auth()->user()->can('core.data_import.manage'))
        <details class="group rounded-xl" @if (request()->routeIs('users.*', 'audit-logs.*', 'settings.*', 'core.data-imports.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="shield" /></span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Administrasi</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                @can('viewAny', App\Models\User::class)
                    <a href="{{ route('users.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="users" /></span>Pengguna</a>
                @endcan
                @can('core.audit.view')
                    <a href="{{ route('audit-logs.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="clipboard-list" /></span>Audit log</a>
                @endcan
                @can('core.settings.view')
                    <a href="{{ route('settings.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="settings" /></span>Pengaturan</a>
                @endcan
                @can('core.data_import.manage')
                    <a href="{{ route('core.data-imports.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="database" /></span>Migrasi data awal</a>
                @endcan
            </div>
        </details>
    @endif

    @canany(['md.item.view', 'md.item.manage', 'md.price.manage'])
        <details class="group rounded-xl" @if (request()->routeIs('md.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="package" /></span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Data master</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                @can('md.item.view')
                    <a href="{{ route('md.items.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="pill" /></span>Barang</a>
                    <a href="{{ route('md.suppliers.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="truck" /></span>Supplier</a>
                    <a href="{{ route('md.patients.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="user-round" /></span>Pasien</a>
                    <a href="{{ route('md.doctors.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="stethoscope" /></span>Dokter</a>
                    <a href="{{ route('md.pharmacy-staff.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="badge-check" /></span>Tenaga farmasi</a>
                    <a href="{{ route('md.health-services.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="heart-pulse" /></span>Layanan kesehatan</a>
                @endcan
                @can('md.item.manage')
                    <a href="{{ route('md.categories.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="tags" /></span>Kategori &amp; satuan</a>
                @endcan
                @can('md.price.manage')
                    <a href="{{ route('md.prices.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="badge-dollar" /></span>Harga &amp; margin</a>
                    <a href="{{ route('md.taxes.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="receipt" /></span>Aturan pajak</a>
                    <a href="{{ route('md.discounts.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="percent" /></span>Diskon</a>
                @endcan
            </div>
        </details>
    @endcanany

    @canany(['inventory.stock.view', 'inventory.stock.manage', 'inventory.receipt.view', 'inventory.receipt.manage', 'inventory.opname.manage', 'inventory.purchase_return.manage'])
        <details class="group rounded-xl" @if (request()->routeIs('inventory.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="boxes" /></span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Persediaan &amp; gudang</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                @can('inventory.stock.view')
                    <a href="{{ route('inventory.stock.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="boxes" /></span>Stok per barang/batch</a>
                    <a href="{{ route('inventory.expiry.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="calendar-clock" /></span>Kedaluwarsa</a>
                @endcan
                @can('inventory.stock.manage')
                    <a href="{{ route('inventory.stock-policies.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="settings" /></span>ROP &amp; kebijakan stok</a>
                @endcan
                @can('inventory.opname.manage')
                    <a href="{{ route('inventory.opnames.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="clipboard-check" /></span>Stock opname</a>
                @endcan
                @can('inventory.purchase_return.manage')
                    <a href="{{ route('inventory.purchase-returns.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="rotate-ccw" /></span>Retur pembelian</a>
                @endcan
                @can('inventory.receipt.view')
                    <a href="{{ route('inventory.receipts.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="package-check" /></span>Penerimaan barang</a>
                @endcan
            </div>
        </details>
    @endcanany

    @canany(['purchasing.po.manage', 'purchasing.payable.view'])
        <details class="group rounded-xl" @if (request()->routeIs('purchasing.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="truck" /></span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Pembelian &amp; supplier</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                @can('purchasing.po.manage')
                    <a href="{{ route('purchasing.purchase-orders.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="clipboard-list" /></span>Purchase Order</a>
                    <a href="{{ route('purchasing.supplier-evaluations.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="chart" /></span>Evaluasi supplier</a>
                @endcan
                @can('purchasing.payable.view')
                    <a href="{{ route('purchasing.payables.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="wallet" /></span>Utang supplier</a>
                @endcan
            </div>
        </details>
    @endcanany

    @can('pos.sale.view')
        <details class="group rounded-xl" @if (request()->routeIs('pos.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="shopping-cart" /></span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Penjualan</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                <a href="{{ route('pos.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="scan-barcode" /></span>Kasir / POS</a>
                <a href="{{ route('pos.sales.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="receipt" /></span>Riwayat transaksi</a>
            </div>
        </details>
    @endcan

    @canany(['rx.prescription.manage', 'rx.patient_history.view'])
        <details class="group rounded-xl" @if (request()->routeIs('rx.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="pill" /></span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Pelayanan farmasi</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                @can('rx.prescription.manage')
                    <a href="{{ route('rx.prescriptions.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="clipboard-list" /></span>Antrean resep</a>
                @endcan
                @can('rx.patient_history.view')
                    <a href="{{ route('rx.patient-history.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="user-round" /></span>Riwayat pasien</a>
                    <a href="{{ route('rx.staff-activity.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="activity" /></span>Aktivitas farmasi</a>
                @endcan
            </div>
        </details>
    @endcanany

    @canany(['accounting.cash.view', 'accounting.receivable.manage', 'accounting.tax.manage'])
        <details class="group rounded-xl" @if (request()->routeIs('accounting.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="landmark" /></span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Akuntansi &amp; keuangan</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                @can('accounting.cash.view')
                    <a href="{{ route('accounting.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="wallet" /></span>Kas &amp; bank</a>
                @endcan
                @can('accounting.receivable.manage')
                    <a href="{{ route('accounting.receivables.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="hand-coins" /></span>Piutang pelanggan</a>
                @endcan
                @can('accounting.tax.manage')
                    <a href="{{ route('accounting.taxes.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="receipt" /></span>Pajak &amp; kewajiban</a>
                @endcan
            </div>
        </details>
    @endcanany

    @canany(['report.view.finance', 'report.view.inventory', 'report.view.purchasing', 'report.view.sales', 'report.view.service'])
        <details class="group rounded-xl" @if (request()->routeIs('reports.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="chart" /></span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Pelaporan</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                <a href="{{ route('reports.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500"><x-icon name="file-chart" /></span>Laporan</a>
            </div>
        </details>
    @endcanany
</nav>
