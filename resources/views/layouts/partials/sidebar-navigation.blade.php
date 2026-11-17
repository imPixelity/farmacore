<nav class="flex-1 space-y-2 overflow-y-auto px-4 py-6">
    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Navigasi</p>
    <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold {{ request()->routeIs('home') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}" @if (request()->routeIs('home')) aria-current="page" @endif>
        <span class="grid size-8 place-items-center rounded-lg bg-white text-emerald-700">⌂</span>
        Ringkasan
    </a>
    <a href="{{ route('modules.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold {{ request()->routeIs('modules.index') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}" @if (request()->routeIs('modules.index')) aria-current="page" @endif>
        <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">↗</span>
        Peta modul
    </a>

    @if (auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->can('core.audit.view') || auth()->user()->can('core.settings.view') || auth()->user()->can('core.data_import.manage'))
        <details class="group rounded-xl" @if (request()->routeIs('users.*', 'audit-logs.*', 'settings.*', 'core.data-imports.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-[10px] font-bold text-slate-500">CORE</span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Administrasi</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                @can('viewAny', App\Models\User::class)
                    <a href="{{ route('users.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">U</span>Pengguna</a>
                @endcan
                @can('core.audit.view')
                    <a href="{{ route('audit-logs.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">A</span>Audit log</a>
                @endcan
                @can('core.settings.view')
                    <a href="{{ route('settings.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">⚙</span>Pengaturan</a>
                @endcan
                @can('core.data_import.manage')
                    <a href="{{ route('core.data-imports.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">I</span>Migrasi data awal</a>
                @endcan
            </div>
        </details>
    @endif

    @canany(['md.item.view', 'md.item.manage', 'md.price.manage'])
        <details class="group rounded-xl" @if (request()->routeIs('md.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-[10px] font-bold text-slate-500">MD</span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Data master</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                @can('md.item.view')
                    <a href="{{ route('md.items.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">B</span>Barang</a>
                    <a href="{{ route('md.suppliers.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">S</span>Supplier</a>
                    <a href="{{ route('md.patients.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">P</span>Pasien</a>
                    <a href="{{ route('md.doctors.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">D</span>Dokter</a>
                    <a href="{{ route('md.pharmacy-staff.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">T</span>Tenaga farmasi</a>
                    <a href="{{ route('md.health-services.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">L</span>Layanan kesehatan</a>
                @endcan
                @can('md.item.manage')
                    <a href="{{ route('md.categories.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">K</span>Kategori &amp; satuan</a>
                @endcan
                @can('md.price.manage')
                    <a href="{{ route('md.prices.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">H</span>Harga &amp; margin</a>
                    <a href="{{ route('md.taxes.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">P</span>Aturan pajak</a>
                    <a href="{{ route('md.discounts.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">D</span>Diskon</a>
                @endcan
            </div>
        </details>
    @endcanany

    @canany(['inventory.stock.view', 'inventory.stock.manage', 'inventory.receipt.view', 'inventory.receipt.manage', 'inventory.opname.manage', 'inventory.purchase_return.manage'])
        <details class="group rounded-xl" @if (request()->routeIs('inventory.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-[10px] font-bold text-slate-500">INV</span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Persediaan &amp; gudang</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                @can('inventory.stock.view')
                    <a href="{{ route('inventory.stock.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">S</span>Stok per barang/batch</a>
                    <a href="{{ route('inventory.expiry.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">E</span>Kedaluwarsa</a>
                @endcan
                @can('inventory.stock.manage')
                    <a href="{{ route('inventory.stock-policies.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">R</span>ROP &amp; kebijakan stok</a>
                @endcan
                @can('inventory.opname.manage')
                    <a href="{{ route('inventory.opnames.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">O</span>Stock opname</a>
                @endcan
                @can('inventory.purchase_return.manage')
                    <a href="{{ route('inventory.purchase-returns.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">R</span>Retur pembelian</a>
                @endcan
                @can('inventory.receipt.view')
                    <a href="{{ route('inventory.receipts.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">P</span>Penerimaan barang</a>
                @endcan
            </div>
        </details>
    @endcanany

    @canany(['purchasing.po.manage', 'purchasing.payable.view'])
        <details class="group rounded-xl" @if (request()->routeIs('purchasing.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-[10px] font-bold text-slate-500">PUR</span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Pembelian &amp; supplier</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                @can('purchasing.po.manage')
                    <a href="{{ route('purchasing.purchase-orders.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">PO</span>Purchase Order</a>
                    <a href="{{ route('purchasing.supplier-evaluations.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">E</span>Evaluasi supplier</a>
                @endcan
                @can('purchasing.payable.view')
                    <a href="{{ route('purchasing.payables.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">U</span>Utang supplier</a>
                @endcan
            </div>
        </details>
    @endcanany

    @can('pos.sale.view')
        <details class="group rounded-xl" @if (request()->routeIs('pos.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-[10px] font-bold text-slate-500">POS</span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Penjualan</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                <a href="{{ route('pos.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">K</span>Kasir / POS</a>
                <a href="{{ route('pos.sales.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">T</span>Riwayat transaksi</a>
            </div>
        </details>
    @endcan

    @canany(['rx.prescription.manage', 'rx.patient_history.view'])
        <details class="group rounded-xl" @if (request()->routeIs('rx.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-[10px] font-bold text-slate-500">RX</span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Pelayanan farmasi</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                @can('rx.prescription.manage')
                    <a href="{{ route('rx.prescriptions.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">R</span>Antrean resep</a>
                @endcan
                @can('rx.patient_history.view')
                    <a href="{{ route('rx.patient-history.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">H</span>Riwayat pasien</a>
                    <a href="{{ route('rx.staff-activity.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">A</span>Aktivitas farmasi</a>
                @endcan
            </div>
        </details>
    @endcanany

    @canany(['accounting.cash.view', 'accounting.receivable.manage', 'accounting.tax.manage'])
        <details class="group rounded-xl" @if (request()->routeIs('accounting.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-[10px] font-bold text-slate-500">ACC</span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Akuntansi &amp; keuangan</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                @can('accounting.cash.view')
                    <a href="{{ route('accounting.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">K</span>Kas &amp; bank</a>
                @endcan
                @can('accounting.receivable.manage')
                    <a href="{{ route('accounting.receivables.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">P</span>Piutang pelanggan</a>
                @endcan
                @can('accounting.tax.manage')
                    <a href="{{ route('accounting.taxes.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">T</span>Pajak &amp; kewajiban</a>
                @endcan
            </div>
        </details>
    @endcanany

    @canany(['report.view.finance', 'report.view.inventory', 'report.view.purchasing', 'report.view.sales', 'report.view.service'])
        <details class="group rounded-xl" @if (request()->routeIs('reports.*')) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-[10px] font-bold text-slate-500">RPT</span>
                <span class="min-w-0 flex-1 text-sm font-semibold text-slate-700">Pelaporan</span>
                <span class="text-xs text-slate-400" aria-hidden="true">⌄</span>
            </summary>
            <div class="mt-1 space-y-1 pl-3">
                <a href="{{ route('reports.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950"><span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">L</span>Laporan</a>
            </div>
        </details>
    @endcanany
</nav>
