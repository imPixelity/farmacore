<?php

namespace App\Modules\Reporting\Services;

use App\Models\User;
use App\Modules\Md\Models\PharmacyStaff;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReportService
{
    /**
     * @var array<string, array{title: string, requirement: string, permission: string, description: string, columns: array<int, array{key: string, label: string, format: string}>}>
     */
    private const REPORTS = [
        'pemasukan-pengeluaran' => [
            'title' => 'Pemasukan dan pengeluaran',
            'requirement' => 'REQ-RPT-01',
            'permission' => 'report.view.finance',
            'description' => 'Ringkasan transaksi keuangan berdasarkan arah dan kategori.',
            'columns' => [
                ['key' => 'direction', 'label' => 'Arah', 'format' => 'direction'],
                ['key' => 'category', 'label' => 'Kategori', 'format' => 'text'],
                ['key' => 'transaction_count', 'label' => 'Jumlah transaksi', 'format' => 'integer'],
                ['key' => 'amount', 'label' => 'Total', 'format' => 'money'],
            ],
        ],
        'arus-kas' => [
            'title' => 'Arus kas',
            'requirement' => 'REQ-RPT-02',
            'permission' => 'report.view.finance',
            'description' => 'Saldo awal, pemasukan, pengeluaran, dan saldo akhir per akun.',
            'columns' => [
                ['key' => 'account_name', 'label' => 'Akun', 'format' => 'text'],
                ['key' => 'account_type', 'label' => 'Jenis', 'format' => 'text'],
                ['key' => 'opening_balance', 'label' => 'Saldo awal', 'format' => 'money'],
                ['key' => 'inflow', 'label' => 'Pemasukan', 'format' => 'money'],
                ['key' => 'outflow', 'label' => 'Pengeluaran', 'format' => 'money'],
                ['key' => 'closing_balance', 'label' => 'Saldo akhir', 'format' => 'money'],
            ],
        ],
        'laba-rugi' => [
            'title' => 'Laba rugi',
            'requirement' => 'REQ-RPT-03',
            'permission' => 'report.view.finance',
            'description' => 'Pendapatan setelah retur dikurangi HPP, biaya operasional, dan kerugian write-off.',
            'columns' => [
                ['key' => 'component', 'label' => 'Komponen', 'format' => 'text'],
                ['key' => 'amount', 'label' => 'Nilai', 'format' => 'money'],
            ],
        ],
        'utang-supplier' => [
            'title' => 'Utang supplier',
            'requirement' => 'REQ-RPT-04',
            'permission' => 'report.view.finance',
            'description' => 'Utang per supplier berdasarkan tanggal jatuh tempo.',
            'columns' => [
                ['key' => 'supplier', 'label' => 'Supplier', 'format' => 'text'],
                ['key' => 'invoice_no', 'label' => 'Faktur', 'format' => 'text'],
                ['key' => 'due_date', 'label' => 'Jatuh tempo', 'format' => 'date'],
                ['key' => 'total_amount', 'label' => 'Nilai utang', 'format' => 'money'],
                ['key' => 'paid_amount', 'label' => 'Dibayar', 'format' => 'money'],
                ['key' => 'outstanding_amount', 'label' => 'Sisa', 'format' => 'money'],
                ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
            ],
        ],
        'piutang-pelanggan' => [
            'title' => 'Piutang pelanggan',
            'requirement' => 'REQ-RPT-05',
            'permission' => 'report.view.finance',
            'description' => 'Piutang penjualan kredit berdasarkan tanggal transaksi.',
            'columns' => [
                ['key' => 'customer', 'label' => 'Pelanggan', 'format' => 'text'],
                ['key' => 'invoice_no', 'label' => 'Faktur', 'format' => 'text'],
                ['key' => 'sale_date', 'label' => 'Tanggal penjualan', 'format' => 'datetime'],
                ['key' => 'total_amount', 'label' => 'Nilai piutang', 'format' => 'money'],
                ['key' => 'paid_amount', 'label' => 'Dibayar', 'format' => 'money'],
                ['key' => 'outstanding_amount', 'label' => 'Sisa', 'format' => 'money'],
                ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
            ],
        ],
        'biaya-operasional' => [
            'title' => 'Biaya operasional',
            'requirement' => 'REQ-RPT-06',
            'permission' => 'report.view.finance',
            'description' => 'Pengeluaran non-pembelian dan non-retur penjualan per kategori.',
            'columns' => [
                ['key' => 'trx_date', 'label' => 'Tanggal', 'format' => 'date'],
                ['key' => 'category', 'label' => 'Kategori', 'format' => 'text'],
                ['key' => 'description', 'label' => 'Keterangan', 'format' => 'text'],
                ['key' => 'amount', 'label' => 'Jumlah', 'format' => 'money'],
            ],
        ],
        'transaksi-kas-bank' => [
            'title' => 'Transaksi kas dan bank',
            'requirement' => 'REQ-RPT-07',
            'permission' => 'report.view.finance',
            'description' => 'Ledger transaksi yang sudah diposting per akun kas/bank.',
            'columns' => [
                ['key' => 'trx_date', 'label' => 'Tanggal', 'format' => 'date'],
                ['key' => 'account_name', 'label' => 'Akun', 'format' => 'text'],
                ['key' => 'direction', 'label' => 'Arah', 'format' => 'direction'],
                ['key' => 'category', 'label' => 'Kategori', 'format' => 'text'],
                ['key' => 'trx_no', 'label' => 'Nomor transaksi', 'format' => 'text'],
                ['key' => 'description', 'label' => 'Keterangan', 'format' => 'text'],
                ['key' => 'amount', 'label' => 'Jumlah', 'format' => 'money'],
            ],
        ],
        'stok-batch' => [
            'title' => 'Stok per batch, ROP, dan kedaluwarsa',
            'requirement' => 'REQ-RPT-08',
            'permission' => 'report.view.inventory',
            'description' => 'Saldo stok saat ini per batch; tanggal mulai/akhir memfilter tanggal kedaluwarsa.',
            'columns' => [
                ['key' => 'item_code', 'label' => 'Kode barang', 'format' => 'text'],
                ['key' => 'item_name', 'label' => 'Barang', 'format' => 'text'],
                ['key' => 'category', 'label' => 'Kategori', 'format' => 'text'],
                ['key' => 'batch_no', 'label' => 'Batch', 'format' => 'text'],
                ['key' => 'qty_on_hand', 'label' => 'Stok batch', 'format' => 'quantity'],
                ['key' => 'item_stock', 'label' => 'Total stok', 'format' => 'quantity'],
                ['key' => 'reorder_point', 'label' => 'ROP', 'format' => 'quantity'],
                ['key' => 'expiry_date', 'label' => 'Kedaluwarsa', 'format' => 'date'],
                ['key' => 'stock_status', 'label' => 'Status stok', 'format' => 'status'],
            ],
        ],
        'opname-mutasi' => [
            'title' => 'Riwayat stock opname dan mutasi stok',
            'requirement' => 'REQ-RPT-09',
            'permission' => 'report.view.inventory',
            'description' => 'Riwayat penyesuaian opname dan seluruh mutasi stok dalam periode.',
            'columns' => [
                ['key' => 'report_date', 'label' => 'Tanggal', 'format' => 'datetime'],
                ['key' => 'event_type', 'label' => 'Jenis aktivitas', 'format' => 'text'],
                ['key' => 'item_name', 'label' => 'Barang', 'format' => 'text'],
                ['key' => 'batch_no', 'label' => 'Batch', 'format' => 'text'],
                ['key' => 'quantity_change', 'label' => 'Selisih/mutasi', 'format' => 'quantity'],
                ['key' => 'quantity_after', 'label' => 'Stok setelah', 'format' => 'quantity'],
                ['key' => 'status', 'label' => 'Status', 'format' => 'status'],
                ['key' => 'description', 'label' => 'Keterangan', 'format' => 'text'],
            ],
        ],
        'riwayat-pembelian' => [
            'title' => 'Riwayat pembelian dan evaluasi supplier',
            'requirement' => 'REQ-RPT-10',
            'permission' => 'report.view.purchasing',
            'description' => 'Penerimaan terkonfirmasi dan metrik ketepatan serta kesesuaian supplier.',
            'columns' => [
                ['key' => 'invoice_date', 'label' => 'Tanggal faktur', 'format' => 'date'],
                ['key' => 'supplier', 'label' => 'Supplier', 'format' => 'text'],
                ['key' => 'invoice_no', 'label' => 'Faktur', 'format' => 'text'],
                ['key' => 'po_no', 'label' => 'Nomor PO', 'format' => 'text'],
                ['key' => 'total_after_tax', 'label' => 'Total', 'format' => 'money'],
                ['key' => 'matching_status', 'label' => 'Pencocokan', 'format' => 'status'],
                ['key' => 'lead_time_days', 'label' => 'Lead time (hari)', 'format' => 'decimal'],
                ['key' => 'delivery_status', 'label' => 'Ketepatan', 'format' => 'status'],
            ],
        ],
        'penjualan-per-jenis' => [
            'title' => 'Penjualan per jenis',
            'requirement' => 'REQ-RPT-11',
            'permission' => 'report.view.sales',
            'description' => 'Jumlah transaksi, nilai penjualan, pajak, dan retur per jenis penjualan.',
            'columns' => [
                ['key' => 'sale_type', 'label' => 'Jenis penjualan', 'format' => 'text'],
                ['key' => 'transaction_count', 'label' => 'Transaksi', 'format' => 'integer'],
                ['key' => 'gross_amount', 'label' => 'Penjualan bruto', 'format' => 'money'],
                ['key' => 'output_tax', 'label' => 'PPN keluaran', 'format' => 'money'],
                ['key' => 'return_amount', 'label' => 'Retur', 'format' => 'money'],
                ['key' => 'net_amount', 'label' => 'Penjualan bersih', 'format' => 'money'],
            ],
        ],
        'aktivitas-farmasi' => [
            'title' => 'Aktivitas tenaga farmasi dan riwayat pelayanan pasien',
            'requirement' => 'REQ-RPT-12',
            'permission' => 'report.view.service',
            'description' => 'Aktivitas resep, screening, peracikan, dan penyerahan sesuai hak akses pasien.',
            'columns' => [
                ['key' => 'activity_date', 'label' => 'Tanggal', 'format' => 'datetime'],
                ['key' => 'activity_type', 'label' => 'Aktivitas', 'format' => 'text'],
                ['key' => 'staff_name', 'label' => 'Tenaga farmasi', 'format' => 'text'],
                ['key' => 'patient_name', 'label' => 'Pasien', 'format' => 'text'],
                ['key' => 'rx_no', 'label' => 'Nomor resep', 'format' => 'text'],
                ['key' => 'detail', 'label' => 'Detail', 'format' => 'text'],
            ],
        ],
    ];

    /**
     * @return array<string, array{title: string, requirement: string, permission: string, description: string, columns: array<int, array{key: string, label: string, format: string}>}>
     */
    public function definitions(): array
    {
        return self::REPORTS;
    }

    /**
     * @return array<string, array{title: string, requirement: string, permission: string, description: string, columns: array<int, array{key: string, label: string, format: string}>}>
     */
    public function availableTo(User $user): array
    {
        return array_filter(
            self::REPORTS,
            fn (array $report): bool => $user->can($report['permission']),
        );
    }

    /**
     * @return array{definition: array{title: string, requirement: string, permission: string, description: string, columns: array<int, array{key: string, label: string, format: string}>}, rows: LengthAwarePaginator|array<int, array<string, mixed>>, summary: array<int, array{label: string, value: string|int|float|null, format: string}>}
     */
    public function run(string $key, CarbonImmutable $from, CarbonImmutable $to, User $user): array
    {
        $definition = self::REPORTS[$key] ?? throw new InvalidArgumentException('Jenis laporan tidak dikenal.');
        [$rows, $summary] = match ($key) {
            'pemasukan-pengeluaran' => $this->incomeAndExpenses($from, $to),
            'arus-kas' => $this->cashFlow($from, $to),
            'laba-rugi' => $this->profitAndLoss($from, $to),
            'utang-supplier' => $this->supplierPayables($from, $to),
            'piutang-pelanggan' => $this->customerReceivables($from, $to, $user),
            'biaya-operasional' => $this->operationalExpenses($from, $to),
            'transaksi-kas-bank' => $this->cashBankTransactions($from, $to),
            'stok-batch' => $this->batchStock($from, $to),
            'opname-mutasi' => $this->stockHistory($from, $to),
            'riwayat-pembelian' => $this->purchaseHistory($from, $to),
            'penjualan-per-jenis' => $this->salesByType($from, $to),
            'aktivitas-farmasi' => $this->pharmacyActivity($from, $to, $user),
            default => throw new InvalidArgumentException('Jenis laporan tidak dikenal.'),
        };

        return ['definition' => $definition, 'rows' => $rows, 'summary' => $summary];
    }

    /** @return array{0: LengthAwarePaginator, 1: array<int, array{label: string, value: string|int|float|null, format: string}>} */
    private function incomeAndExpenses(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $dateRange = [$from->toDateString(), $to->toDateString()];
        $transactions = DB::table('financial_transactions as transactions')
            ->join('transaction_categories as categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.status', 'posted')
            ->whereBetween('transactions.trx_date', $dateRange);
        $totals = (clone $transactions)
            ->selectRaw("COALESCE(SUM(CASE WHEN transactions.direction = 'in' THEN transactions.amount ELSE 0 END), 0) as income")
            ->selectRaw("COALESCE(SUM(CASE WHEN transactions.direction = 'out' THEN transactions.amount ELSE 0 END), 0) as expenses")
            ->first();
        $rows = (clone $transactions)
            ->select('transactions.direction', 'categories.name as category')
            ->selectRaw('COUNT(transactions.id) as transaction_count, SUM(transactions.amount) as amount')
            ->groupBy('transactions.direction', 'categories.id', 'categories.name')
            ->orderBy('transactions.direction')
            ->orderBy('categories.name')
            ->paginate(25)
            ->withQueryString();

        return [$rows, [
            $this->card('Total pemasukan', (string) $totals->income),
            $this->card('Total pengeluaran', (string) $totals->expenses),
        ]];
    }

    /** @return array{0: LengthAwarePaginator, 1: array<int, array{label: string, value: string|int|float|null, format: string}>} */
    private function cashFlow(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $ledger = DB::table('financial_transactions')
            ->where('status', 'posted')
            ->select('cash_bank_account_id')
            ->selectRaw("SUM(CASE WHEN trx_date < ? THEN CASE WHEN direction = 'in' THEN amount ELSE -amount END ELSE 0 END) as prior_net", [$fromDate])
            ->selectRaw("SUM(CASE WHEN trx_date BETWEEN ? AND ? AND direction = 'in' THEN amount ELSE 0 END) as period_in", [$fromDate, $toDate])
            ->selectRaw("SUM(CASE WHEN trx_date BETWEEN ? AND ? AND direction = 'out' THEN amount ELSE 0 END) as period_out", [$fromDate, $toDate])
            ->where('trx_date', '<=', $toDate)
            ->groupBy('cash_bank_account_id');
        $accounts = DB::table('cash_bank_accounts as accounts')
            ->leftJoinSub($ledger, 'ledger', 'ledger.cash_bank_account_id', '=', 'accounts.id')
            ->select('accounts.id', 'accounts.name as account_name', 'accounts.type as account_type')
            ->selectRaw('accounts.opening_balance + COALESCE(ledger.prior_net, 0) as opening_balance')
            ->selectRaw('COALESCE(ledger.period_in, 0) as inflow, COALESCE(ledger.period_out, 0) as outflow')
            ->selectRaw('accounts.opening_balance + COALESCE(ledger.prior_net, 0) + COALESCE(ledger.period_in, 0) - COALESCE(ledger.period_out, 0) as closing_balance')
            ->where('accounts.is_active', true)
            ->orderBy('accounts.name');
        $accountRows = (clone $accounts)->get();
        $summary = [
            $this->card('Saldo awal seluruh akun', $this->sumMoney($accountRows->pluck('opening_balance')->all())),
            $this->card('Pemasukan periode', $this->sumMoney($accountRows->pluck('inflow')->all())),
            $this->card('Pengeluaran periode', $this->sumMoney($accountRows->pluck('outflow')->all())),
            $this->card('Saldo akhir seluruh akun', $this->sumMoney($accountRows->pluck('closing_balance')->all())),
        ];

        return [$accounts->paginate(25)->withQueryString(), $summary];
    }

    /** @return array{0: LengthAwarePaginator, 1: array<int, array{label: string, value: string|int|float|null, format: string}>} */
    private function profitAndLoss(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $saleRange = [$from->startOfDay()->toDateTimeString(), $to->endOfDay()->toDateTimeString()];
        $dateRange = [$from->toDateString(), $to->toDateString()];
        $salesRevenue = (string) DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', $saleRange)
            ->selectRaw('COALESCE(SUM(grand_total - tax_amount), 0) as amount')
            ->value('amount');
        $returns = (string) DB::table('sale_return_items as returned_items')
            ->join('sale_returns as returns', 'returns.id', '=', 'returned_items.sale_return_id')
            ->join('sales', 'sales.id', '=', 'returns.sale_id')
            ->join('sale_items', 'sale_items.id', '=', 'returned_items.sale_item_id')
            ->where('sales.status', 'completed')
            ->whereBetween('returns.return_date', $saleRange)
            ->selectRaw('COALESCE(SUM(returned_items.line_total - ROUND(sale_items.tax_amount * returned_items.qty_base / NULLIF(sale_items.qty_base, 0), 2)), 0) as amount')
            ->value('amount');
        $saleCost = (string) DB::table('sale_item_batches as allocations')
            ->join('sale_items as sale_items', 'sale_items.id', '=', 'allocations.sale_item_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('item_batches as batches', 'batches.id', '=', 'allocations.item_batch_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', $saleRange)
            ->selectRaw('COALESCE(SUM(allocations.qty_base * COALESCE(batches.unit_cost_base, 0)), 0) as amount')
            ->value('amount');
        $restockedCost = (string) DB::table('sale_return_items as returned_items')
            ->join('sale_returns as returns', 'returns.id', '=', 'returned_items.sale_return_id')
            ->join('sales', 'sales.id', '=', 'returns.sale_id')
            ->join('item_batches as batches', 'batches.id', '=', 'returned_items.item_batch_id')
            ->where('sales.status', 'completed')
            ->where('returns.restock', true)
            ->whereBetween('returns.return_date', $saleRange)
            ->selectRaw('COALESCE(SUM(returned_items.qty_base * COALESCE(batches.unit_cost_base, 0)), 0) as amount')
            ->value('amount');
        $operatingExpenses = (string) DB::table('financial_transactions as transactions')
            ->join('transaction_categories as categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.direction', 'out')
            ->where('transactions.status', 'posted')
            ->whereBetween('transactions.trx_date', $dateRange)
            ->where(function (Builder $query): void {
                $query->whereNull('categories.source_code')
                    ->orWhereNotIn('categories.source_code', ['PURCHASE', 'PAYABLE_PAYMENT', 'SALE_REFUND']);
            })
            ->sum('transactions.amount');
        $writeOffLoss = (string) DB::table('stock_write_offs')
            ->where('status', 'approved')
            ->whereBetween('write_off_date', $dateRange)
            ->sum('loss_amount');

        $netRevenue = bcsub($salesRevenue, $returns, 2);
        $netCost = bcsub($saleCost, $restockedCost, 2);
        $profit = bcsub(bcsub(bcsub($netRevenue, $netCost, 2), $operatingExpenses, 2), $writeOffLoss, 2);
        $rows = [
            ['component' => 'Pendapatan penjualan (di luar PPN keluaran)', 'amount' => $salesRevenue],
            ['component' => 'Retur penjualan', 'amount' => '-'.bcadd($returns, '0', 2)],
            ['component' => 'HPP setelah retur yang direstock', 'amount' => '-'.bcadd($netCost, '0', 2)],
            ['component' => 'Biaya operasional', 'amount' => '-'.bcadd($operatingExpenses, '0', 2)],
            ['component' => 'Kerugian write-off', 'amount' => '-'.bcadd($writeOffLoss, '0', 2)],
            ['component' => 'Laba / (rugi) bersih', 'amount' => $profit],
        ];

        return [$this->paginateArray($rows), [
            $this->card('Pendapatan setelah retur', $netRevenue),
            $this->card('HPP setelah retur restock', $netCost),
            $this->card('Biaya operasional', $operatingExpenses),
            $this->card('Kerugian write-off', $writeOffLoss),
            $this->card('Laba / (rugi) bersih', $profit),
        ]];
    }

    /** @return array{0: LengthAwarePaginator, 1: array<int, array{label: string, value: string|int|float|null, format: string}>} */
    private function supplierPayables(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $payables = DB::table('payables')
            ->join('suppliers', 'suppliers.id', '=', 'payables.supplier_id')
            ->whereBetween('payables.due_date', [$from->toDateString(), $to->toDateString()]);
        $totals = (clone $payables)
            ->selectRaw('COUNT(payables.id) as payable_count, COALESCE(SUM(payables.total_amount - payables.paid_amount), 0) as outstanding')
            ->first();
        $rows = (clone $payables)
            ->select('suppliers.name as supplier', 'payables.invoice_no', 'payables.due_date', 'payables.total_amount', 'payables.paid_amount', 'payables.status')
            ->selectRaw('payables.total_amount - payables.paid_amount as outstanding_amount')
            ->orderBy('payables.due_date')
            ->orderBy('payables.id')
            ->paginate(25)
            ->withQueryString();

        return [$rows, [
            $this->card('Jumlah utang', (int) $totals->payable_count, 'integer'),
            $this->card('Sisa utang', (string) $totals->outstanding),
        ]];
    }

    /** @return array{0: LengthAwarePaginator, 1: array<int, array{label: string, value: string|int|float|null, format: string}>} */
    private function customerReceivables(CarbonImmutable $from, CarbonImmutable $to, User $user): array
    {
        $customerColumn = $user->can('md.patient.view_sensitive') ? 'patients.name' : 'patients.code';
        $receivables = DB::table('receivables')
            ->join('patients', 'patients.id', '=', 'receivables.patient_id')
            ->join('sales', 'sales.id', '=', 'receivables.sale_id')
            ->whereNull('receivables.cancelled_at')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$from->startOfDay()->toDateTimeString(), $to->endOfDay()->toDateTimeString()]);
        $totals = (clone $receivables)
            ->selectRaw('COUNT(receivables.id) as receivable_count, COALESCE(SUM(receivables.total_amount - receivables.paid_amount), 0) as outstanding')
            ->first();
        $rows = (clone $receivables)
            ->selectRaw($customerColumn.' as customer')
            ->addSelect('sales.invoice_no', 'sales.sale_date', 'receivables.total_amount', 'receivables.paid_amount', 'receivables.status')
            ->selectRaw('receivables.total_amount - receivables.paid_amount as outstanding_amount')
            ->orderByDesc('sales.sale_date')
            ->orderByDesc('receivables.id')
            ->paginate(25)
            ->withQueryString();

        return [$rows, [
            $this->card('Jumlah piutang', (int) $totals->receivable_count, 'integer'),
            $this->card('Sisa piutang', (string) $totals->outstanding),
        ]];
    }

    /** @return array{0: LengthAwarePaginator, 1: array<int, array{label: string, value: string|int|float|null, format: string}>} */
    private function operationalExpenses(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $expenses = DB::table('financial_transactions as transactions')
            ->join('transaction_categories as categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.direction', 'out')
            ->where('transactions.status', 'posted')
            ->whereBetween('transactions.trx_date', [$from->toDateString(), $to->toDateString()])
            ->where(function (Builder $query): void {
                $query->whereNull('categories.source_code')
                    ->orWhereNotIn('categories.source_code', ['PURCHASE', 'PAYABLE_PAYMENT', 'SALE_REFUND']);
            });
        $totals = (clone $expenses)
            ->selectRaw('COUNT(transactions.id) as transaction_count, COALESCE(SUM(transactions.amount), 0) as amount')
            ->first();
        $rows = (clone $expenses)
            ->select('transactions.trx_date', 'categories.name as category', 'transactions.description', 'transactions.amount')
            ->orderByDesc('transactions.trx_date')
            ->orderByDesc('transactions.id')
            ->paginate(25)
            ->withQueryString();

        return [$rows, [
            $this->card('Jumlah transaksi', (int) $totals->transaction_count, 'integer'),
            $this->card('Total biaya', (string) $totals->amount),
        ]];
    }

    /** @return array{0: LengthAwarePaginator, 1: array<int, array{label: string, value: string|int|float|null, format: string}>} */
    private function cashBankTransactions(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $transactions = DB::table('financial_transactions as transactions')
            ->join('cash_bank_accounts as accounts', 'accounts.id', '=', 'transactions.cash_bank_account_id')
            ->join('transaction_categories as categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.status', 'posted')
            ->whereBetween('transactions.trx_date', [$from->toDateString(), $to->toDateString()]);
        $totals = (clone $transactions)
            ->selectRaw("COALESCE(SUM(CASE WHEN transactions.direction = 'in' THEN transactions.amount ELSE 0 END), 0) as income")
            ->selectRaw("COALESCE(SUM(CASE WHEN transactions.direction = 'out' THEN transactions.amount ELSE 0 END), 0) as expenses")
            ->first();
        $rows = (clone $transactions)
            ->select('transactions.trx_date', 'accounts.name as account_name', 'transactions.direction', 'categories.name as category', 'transactions.trx_no', 'transactions.description', 'transactions.amount')
            ->orderByDesc('transactions.trx_date')
            ->orderByDesc('transactions.id')
            ->paginate(25)
            ->withQueryString();

        return [$rows, [
            $this->card('Pemasukan', (string) $totals->income),
            $this->card('Pengeluaran', (string) $totals->expenses),
        ]];
    }

    /** @return array{0: LengthAwarePaginator, 1: array<int, array{label: string, value: string|int|float|null, format: string}>} */
    private function batchStock(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $stockByItem = DB::table('item_batches')
            ->select('item_id')
            ->selectRaw('SUM(qty_on_hand) as total_qty')
            ->groupBy('item_id');
        $batches = DB::table('item_batches as batches')
            ->join('items', 'items.id', '=', 'batches.item_id')
            ->leftJoin('item_categories as categories', 'categories.id', '=', 'items.category_id')
            ->leftJoinSub($stockByItem, 'item_stock', 'item_stock.item_id', '=', 'items.id')
            ->whereBetween('batches.expiry_date', [$from->toDateString(), $to->toDateString()]);
        $totals = (clone $batches)
            ->selectRaw('COUNT(batches.id) as batch_count, COALESCE(SUM(batches.qty_on_hand), 0) as total_qty')
            ->selectRaw('SUM(CASE WHEN batches.expiry_date < ? THEN 1 ELSE 0 END) as expired_count', [today()->toDateString()])
            ->selectRaw('SUM(CASE WHEN COALESCE(item_stock.total_qty, 0) <= items.reorder_point THEN 1 ELSE 0 END) as reorder_count')
            ->first();
        $rows = (clone $batches)
            ->select('items.code as item_code', 'items.name as item_name', 'categories.name as category', 'batches.batch_no', 'batches.qty_on_hand', 'items.reorder_point', 'batches.expiry_date')
            ->selectRaw('COALESCE(item_stock.total_qty, 0) as item_stock')
            ->selectRaw("CASE WHEN batches.expiry_date < ? THEN 'kedaluwarsa' WHEN COALESCE(item_stock.total_qty, 0) <= items.reorder_point THEN 'perlu_pesan' ELSE 'aman' END as stock_status", [today()->toDateString()])
            ->orderBy('batches.expiry_date')
            ->orderBy('items.name')
            ->paginate(25)
            ->withQueryString();

        return [$rows, [
            $this->card('Batch pada rentang ED', (int) $totals->batch_count, 'integer'),
            $this->card('Jumlah stok', (string) $totals->total_qty, 'quantity'),
            $this->card('Batch kedaluwarsa', (int) $totals->expired_count, 'integer'),
            $this->card('Batch barang di bawah ROP', (int) $totals->reorder_count, 'integer'),
        ]];
    }

    /** @return array{0: LengthAwarePaginator, 1: array<int, array{label: string, value: string|int|float|null, format: string}>} */
    private function stockHistory(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $dateRange = [$from->startOfDay()->toDateTimeString(), $to->endOfDay()->toDateTimeString()];
        $opnames = DB::table('stock_opnames as opnames')
            ->join('stock_opname_items as opname_items', 'opname_items.stock_opname_id', '=', 'opnames.id')
            ->join('item_batches as batches', 'batches.id', '=', 'opname_items.item_batch_id')
            ->join('items', 'items.id', '=', 'batches.item_id')
            ->whereBetween('opnames.opname_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('opname_items.id as record_id, opnames.opname_date as report_date')
            ->selectRaw("'Stock opname' as event_type, items.name as item_name, batches.batch_no")
            ->selectRaw('opname_items.diff_qty as quantity_change, opname_items.physical_qty as quantity_after')
            ->selectRaw('opnames.status as status, opname_items.note as description');
        $movements = DB::table('stock_movements as movements')
            ->join('item_batches as batches', 'batches.id', '=', 'movements.item_batch_id')
            ->join('items', 'items.id', '=', 'batches.item_id')
            ->whereBetween('movements.created_at', $dateRange)
            ->selectRaw('movements.id as record_id, movements.created_at as report_date')
            ->selectRaw('movements.type as event_type, items.name as item_name, batches.batch_no')
            ->selectRaw('movements.qty_change as quantity_change, movements.qty_after as quantity_after')
            ->selectRaw('NULL as status, movements.reason as description');
        $history = $opnames->unionAll($movements);
        $count = DB::query()->fromSub(clone $history, 'stock_history')->count();
        $rows = DB::query()
            ->fromSub($history, 'stock_history')
            ->orderByDesc('report_date')
            ->orderByDesc('record_id')
            ->paginate(25)
            ->withQueryString();

        return [$rows, [
            $this->card('Baris riwayat', $count, 'integer'),
        ]];
    }

    /** @return array{0: LengthAwarePaginator, 1: array<int, array{label: string, value: string|int|float|null, format: string}>} */
    private function purchaseHistory(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $leadTime = DB::getDriverName() === 'sqlite'
            ? 'CAST(julianday(goods_receipts.invoice_date) - julianday(date(purchase_orders.sent_at)) AS INTEGER)'
            : 'DATEDIFF(goods_receipts.invoice_date, DATE(purchase_orders.sent_at))';
        $receipts = DB::table('goods_receipts')
            ->leftJoin('purchase_orders', 'purchase_orders.id', '=', 'goods_receipts.purchase_order_id')
            ->where('goods_receipts.status', 'confirmed')
            ->whereBetween('goods_receipts.invoice_date', [$from->toDateString(), $to->toDateString()]);
        $metrics = (clone $receipts)
            ->selectRaw('COUNT(goods_receipts.id) as receipt_count')
            ->selectRaw('AVG(CASE WHEN purchase_orders.sent_at IS NOT NULL THEN '.$leadTime.' END) as average_lead_time')
            ->selectRaw('AVG(CASE WHEN purchase_orders.expected_date IS NULL THEN NULL WHEN goods_receipts.invoice_date <= purchase_orders.expected_date THEN 100.0 ELSE 0.0 END) as on_time_percent')
            ->selectRaw("AVG(CASE WHEN goods_receipts.purchase_order_id IS NULL THEN NULL WHEN goods_receipts.matching_status = 'sesuai' THEN 100.0 ELSE 0.0 END) as matching_percent")
            ->first();
        $rows = (clone $receipts)
            ->join('suppliers', 'suppliers.id', '=', 'goods_receipts.supplier_id')
            ->select('goods_receipts.invoice_date', 'suppliers.name as supplier', 'goods_receipts.invoice_no', 'purchase_orders.po_no', 'goods_receipts.total_after_tax', 'goods_receipts.matching_status')
            ->selectRaw('CASE WHEN purchase_orders.sent_at IS NOT NULL THEN '.$leadTime.' END as lead_time_days')
            ->selectRaw("CASE WHEN purchase_orders.expected_date IS NULL THEN 'tanpa_target' WHEN goods_receipts.invoice_date <= purchase_orders.expected_date THEN 'tepat_waktu' ELSE 'terlambat' END as delivery_status")
            ->orderByDesc('goods_receipts.invoice_date')
            ->orderByDesc('goods_receipts.id')
            ->paginate(25)
            ->withQueryString();

        return [$rows, [
            $this->card('Penerimaan terkonfirmasi', (int) $metrics->receipt_count, 'integer'),
            $this->card('Rata-rata lead time (hari)', $metrics->average_lead_time === null ? null : number_format((float) $metrics->average_lead_time, 1, '.', ''), 'decimal'),
            $this->card('Ketepatan pengiriman', $metrics->on_time_percent === null ? null : number_format((float) $metrics->on_time_percent, 2, '.', ''), 'percent'),
            $this->card('Kesesuaian pencocokan', $metrics->matching_percent === null ? null : number_format((float) $metrics->matching_percent, 2, '.', ''), 'percent'),
        ]];
    }

    /** @return array{0: LengthAwarePaginator, 1: array<int, array{label: string, value: string|int|float|null, format: string}>} */
    private function salesByType(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $dateTimeRange = [$from->startOfDay()->toDateTimeString(), $to->endOfDay()->toDateTimeString()];
        $sales = DB::table('sales')
            ->where('status', 'completed')
            ->whereBetween('sale_date', $dateTimeRange)
            ->select('sale_type as sale_type_key')
            ->selectRaw('1 as transaction_count, grand_total as gross_amount, tax_amount as output_tax')
            ->selectRaw('0 as return_amount, grand_total - tax_amount as net_amount');
        $returns = DB::table('sale_return_items as returned_items')
            ->join('sale_returns as returns', 'returns.id', '=', 'returned_items.sale_return_id')
            ->join('sales', 'sales.id', '=', 'returns.sale_id')
            ->join('sale_items', 'sale_items.id', '=', 'returned_items.sale_item_id')
            ->where('sales.status', 'completed')
            ->whereBetween('returns.return_date', $dateTimeRange)
            ->select('sales.sale_type as sale_type_key')
            ->selectRaw('0 as transaction_count, 0 as gross_amount, 0 as output_tax')
            ->selectRaw('returned_items.line_total as return_amount')
            ->selectRaw('-(returned_items.line_total - ROUND(sale_items.tax_amount * returned_items.qty_base / NULLIF(sale_items.qty_base, 0), 2)) as net_amount');
        $periodEntries = $sales->unionAll($returns);
        $rows = DB::query()
            ->fromSub($periodEntries, 'period_entries')
            ->selectRaw("CASE sale_type_key WHEN 'resep' THEN 'Resep' WHEN 'obat_bebas' THEN 'Obat bebas' ELSE 'Produk lainnya' END as sale_type")
            ->addSelect('sale_type_key')
            ->selectRaw('SUM(transaction_count) as transaction_count, SUM(gross_amount) as gross_amount, SUM(output_tax) as output_tax')
            ->selectRaw('SUM(return_amount) as return_amount, SUM(net_amount) as net_amount')
            ->groupBy('sale_type_key')
            ->orderBy('sale_type_key')
            ->get();
        $summary = [
            $this->card('Jumlah transaksi', (int) $rows->sum('transaction_count'), 'integer'),
            $this->card('Penjualan bruto', $this->sumMoney($rows->pluck('gross_amount')->all())),
            $this->card('Retur', $this->sumMoney($rows->pluck('return_amount')->all())),
            $this->card('Penjualan bersih di luar PPN', $this->sumMoney($rows->pluck('net_amount')->all())),
        ];
        $data = $rows->map(fn (object $row): array => [
            'sale_type' => $row->sale_type,
            'transaction_count' => $row->transaction_count,
            'gross_amount' => $row->gross_amount,
            'output_tax' => $row->output_tax,
            'return_amount' => $row->return_amount,
            'net_amount' => $row->net_amount,
        ])->all();

        return [$this->paginateArray($data), $summary];
    }

    /** @return array{0: LengthAwarePaginator, 1: array<int, array{label: string, value: string|int|float|null, format: string}>} */
    private function pharmacyActivity(CarbonImmutable $from, CarbonImmutable $to, User $user): array
    {
        $staffId = $user->hasRole('tenaga_farmasi')
            ? (int) (PharmacyStaff::query()->where('user_id', $user->id)->value('id') ?? 0)
            : null;
        $dateTimeRange = [$from->startOfDay()->toDateTimeString(), $to->endOfDay()->toDateTimeString()];

        $prescriptions = DB::table('prescriptions')
            ->join('patients', 'patients.id', '=', 'prescriptions.patient_id')
            ->leftJoin('pharmacy_staff as staff', 'staff.user_id', '=', 'prescriptions.created_by')
            ->whereBetween('prescriptions.rx_date', [$from->toDateString(), $to->toDateString()])
            ->when($staffId !== null, fn (Builder $query) => $query->where('staff.id', $staffId))
            ->selectRaw("prescriptions.id as activity_id, prescriptions.rx_date as activity_date, 'Resep diterima' as activity_type")
            ->selectRaw('staff.name as staff_name, patients.id as patient_id, patients.name as patient_name, prescriptions.rx_no')
            ->selectRaw('prescriptions.status as detail');
        $screenings = DB::table('prescription_screenings as screenings')
            ->join('pharmacy_staff as staff', 'staff.id', '=', 'screenings.pharmacy_staff_id')
            ->join('prescriptions', 'prescriptions.id', '=', 'screenings.prescription_id')
            ->join('patients', 'patients.id', '=', 'prescriptions.patient_id')
            ->whereBetween('screenings.performed_at', $dateTimeRange)
            ->when($staffId !== null, fn (Builder $query) => $query->where('staff.id', $staffId))
            ->selectRaw("screenings.id as activity_id, screenings.performed_at as activity_date, 'Screening' as activity_type")
            ->selectRaw('staff.name as staff_name, patients.id as patient_id, patients.name as patient_name, prescriptions.rx_no')
            ->selectRaw('screenings.result as detail');
        $compoundings = DB::table('prescription_compoundings as compoundings')
            ->join('pharmacy_staff as staff', 'staff.id', '=', 'compoundings.pharmacy_staff_id')
            ->join('prescriptions', 'prescriptions.id', '=', 'compoundings.prescription_id')
            ->join('patients', 'patients.id', '=', 'prescriptions.patient_id')
            ->whereBetween('compoundings.performed_at', $dateTimeRange)
            ->when($staffId !== null, fn (Builder $query) => $query->where('staff.id', $staffId))
            ->selectRaw("compoundings.id as activity_id, compoundings.performed_at as activity_date, 'Peracikan' as activity_type")
            ->selectRaw('staff.name as staff_name, patients.id as patient_id, patients.name as patient_name, prescriptions.rx_no')
            ->selectRaw('compoundings.compound_name as detail');
        $dispenses = DB::table('prescription_dispenses as dispenses')
            ->join('pharmacy_staff as staff', 'staff.id', '=', 'dispenses.pharmacy_staff_id')
            ->join('prescriptions', 'prescriptions.id', '=', 'dispenses.prescription_id')
            ->join('patients', 'patients.id', '=', 'prescriptions.patient_id')
            ->whereBetween('dispenses.dispensed_at', $dateTimeRange)
            ->when($staffId !== null, fn (Builder $query) => $query->where('staff.id', $staffId))
            ->selectRaw("dispenses.id as activity_id, dispenses.dispensed_at as activity_date, 'Penyerahan' as activity_type")
            ->selectRaw('staff.name as staff_name, patients.id as patient_id, patients.name as patient_name, prescriptions.rx_no')
            ->selectRaw('dispenses.received_by_name as detail');
        $activities = $prescriptions
            ->unionAll($screenings)
            ->unionAll($compoundings)
            ->unionAll($dispenses);
        $count = DB::query()->fromSub(clone $activities, 'pharmacy_activities')->count();
        $rows = DB::query()
            ->fromSub($activities, 'pharmacy_activities')
            ->orderByDesc('activity_date')
            ->orderByDesc('activity_id')
            ->paginate(25)
            ->withQueryString();

        return [$rows, [
            $this->card('Jumlah aktivitas pelayanan', $count, 'integer'),
        ]];
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function paginateArray(array $rows): LengthAwarePaginator
    {
        $perPage = 25;
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            array_slice($rows, ($page - 1) * $perPage, $perPage),
            count($rows),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()],
        );
    }

    /** @return array{label: string, value: string|int|float|null, format: string} */
    private function card(string $label, string|int|float|null $value, string $format = 'money'): array
    {
        return ['label' => $label, 'value' => $value, 'format' => $format];
    }

    /** @param array<int, mixed> $values */
    private function sumMoney(array $values): string
    {
        return array_reduce(
            $values,
            static fn (string $carry, mixed $value): string => bcadd($carry, (string) ($value ?? '0'), 2),
            '0.00',
        );
    }
}
