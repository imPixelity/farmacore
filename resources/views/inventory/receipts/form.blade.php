@extends('layouts.app')

@section('title', $receipt ? 'Ubah draft penerimaan' : 'Penerimaan barang baru')
@section('breadcrumb', 'Persediaan / Penerimaan barang')
@section('page-heading', $receipt ? 'Ubah draft penerimaan' : 'Penerimaan barang baru')

@section('content')
    <form method="POST" action="{{ $receipt ? route('inventory.receipts.update', $receipt) : route('inventory.receipts.store') }}" class="grid gap-5">
        @csrf
        @if ($receipt)
            @method('PUT')
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
            <div class="mb-5">
                <h2 class="font-bold text-slate-900">Informasi faktur</h2>
                <p class="mt-1 text-sm text-slate-500">Draft tidak menambah stok. Stok dan transaksi terkait baru dibuat setelah konfirmasi.</p>
            </div>

            @if ($duplicateInvoice)
                <div role="alert" class="mb-5 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-semibold">Peringatan nomor faktur duplikat</p>
                    <p class="mt-1">{{ session('duplicate_invoice_warning', 'Nomor faktur ini sudah tercatat untuk supplier yang sama. Pastikan ini bukan input ganda sebelum melanjutkan.') }}</p>
                    <p class="mt-2">Penerimaan duplikat tetap dapat dibuat jika faktur memang berbeda; konfirmasi eksplisit diperlukan.</p>
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <label>
                    <span class="mb-1.5 block text-xs font-semibold text-slate-600">Supplier</span>
                    <select name="supplier_id" required data-supplier-select class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                        <option value="">Pilih supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" data-payment-term-days="{{ $supplier->payment_term_days ?? '' }}" @selected((string) old('supplier_id', $receipt?->supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id')<span class="mt-1 block text-xs text-rose-700">{{ $message }}</span>@enderror
                </label>
                <label>
                    <span class="mb-1.5 block text-xs font-semibold text-slate-600">Nomor faktur</span>
                    <input name="invoice_no" type="text" maxlength="50" value="{{ old('invoice_no', $receipt?->invoice_no) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                    @error('invoice_no')<span class="mt-1 block text-xs text-rose-700">{{ $message }}</span>@enderror
                </label>
                <label>
                    <span class="mb-1.5 block text-xs font-semibold text-slate-600">Tanggal faktur</span>
                    <input name="invoice_date" type="date" value="{{ old('invoice_date', $receipt?->invoice_date?->toDateString() ?? today()->toDateString()) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                    @error('invoice_date')<span class="mt-1 block text-xs text-rose-700">{{ $message }}</span>@enderror
                </label>
                <label>
                    <span class="mb-1.5 block text-xs font-semibold text-slate-600">Metode pembayaran</span>
                    <select name="payment_method" data-payment-method required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                        <option value="tunai" @selected(old('payment_method', $receipt?->payment_method ?? 'tunai') === 'tunai')>Tunai</option>
                        <option value="kredit" @selected(old('payment_method', $receipt?->payment_method) === 'kredit')>Kredit</option>
                    </select>
                    @error('payment_method')<span class="mt-1 block text-xs text-rose-700">{{ $message }}</span>@enderror
                </label>
                <label>
                    <span class="mb-1.5 block text-xs font-semibold text-slate-600">Jatuh tempo (kredit)</span>
                    <input name="due_date" type="date" data-due-date value="{{ old('due_date', $receipt?->due_date?->toDateString()) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                    <span class="mt-1 block text-xs text-slate-500">Otomatis memakai tanggal faktur + tenor supplier bila tersedia.</span>
                    @error('due_date')<span class="mt-1 block text-xs text-rose-700">{{ $message }}</span>@enderror
                </label>
                <label>
                    <span class="mb-1.5 block text-xs font-semibold text-slate-600">Diskon faktur</span>
                    <input name="discount_amount" type="number" min="0" step="0.01" value="{{ old('discount_amount', $receipt?->discount_amount ?? '0.00') }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                    @error('discount_amount')<span class="mt-1 block text-xs text-rose-700">{{ $message }}</span>@enderror
                </label>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="font-bold text-slate-900">Barang yang diterima</h2>
                    <p class="mt-1 text-sm text-slate-500">Harga dan diskon dimasukkan sebelum PPN; jumlah dikonversi ke satuan dasar saat disimpan.</p>
                </div>
                <button type="button" data-add-line class="rounded-xl border border-emerald-200 px-4 py-2.5 text-sm font-semibold text-emerald-800 hover:bg-emerald-50">Tambah baris</button>
            </div>
            @error('lines')<p class="px-5 pt-4 text-sm text-rose-700 sm:px-6">{{ $message }}</p>@enderror
            <div class="overflow-x-auto">
                <table class="w-full min-w-275 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Barang</th>
                            <th class="px-4 py-3 font-semibold">Satuan</th>
                            <th class="px-4 py-3 font-semibold">Jumlah</th>
                            <th class="px-4 py-3 font-semibold">Nomor batch</th>
                            <th class="px-4 py-3 font-semibold">Kedaluwarsa</th>
                            <th class="px-4 py-3 font-semibold">Harga beli</th>
                            <th class="px-4 py-3 font-semibold">Diskon baris</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody data-lines>
                        @foreach ($lines as $index => $line)
                            @include('inventory.receipts._line', ['index' => $index, 'line' => $line])
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <a href="{{ $receipt ? route('inventory.receipts.show', $receipt) : route('inventory.receipts.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
            @if ($duplicateInvoice)
                <button name="confirm_duplicate" value="1" class="rounded-xl bg-amber-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-800">Lanjutkan, faktur berbeda</button>
            @else
                <button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan draft</button>
            @endif
        </div>
    </form>

    <template data-line-template>
        @include('inventory.receipts._line', ['index' => '__INDEX__', 'line' => []])
    </template>

    <script>
        (() => {
            const tbody = document.querySelector('[data-lines]');
            const template = document.querySelector('[data-line-template]');
            const supplier = document.querySelector('[data-supplier-select]');
            const paymentMethod = document.querySelector('[data-payment-method]');
            const dueDate = document.querySelector('[data-due-date]');

            const updateUnits = (row) => {
                const itemSelect = row.querySelector('[data-item-select]');
                const unitSelect = row.querySelector('[data-unit-select]');
                const selectedUnit = unitSelect.value;
                const itemId = itemSelect.value;
                let hasSelectedUnit = false;

                Array.from(unitSelect.options).forEach((option) => {
                    const visible = !option.value || option.dataset.itemId === itemId;
                    option.hidden = !visible;
                    option.disabled = !visible;
                    if (visible && option.value === selectedUnit) {
                        hasSelectedUnit = true;
                    }
                });

                if (!hasSelectedUnit) {
                    unitSelect.value = '';
                }
            };

            const updateDueDate = () => {
                const option = supplier.options[supplier.selectedIndex];
                dueDate.required = paymentMethod.value === 'kredit' && !option?.dataset.paymentTermDays;
            };

            tbody.querySelectorAll('[data-receipt-line]').forEach(updateUnits);
            updateDueDate();

            document.querySelector('[data-add-line]').addEventListener('click', () => {
                const indices = Array.from(tbody.querySelectorAll('[data-item-select]'))
                    .map((select) => Number(select.name.match(/lines\[(\d+)\]/)?.[1] ?? -1));
                const nextIndex = Math.max(-1, ...indices) + 1;
                tbody.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(nextIndex)));
                updateUnits(tbody.lastElementChild);
            });

            tbody.addEventListener('change', (event) => {
                if (event.target.matches('[data-item-select]')) {
                    updateUnits(event.target.closest('[data-receipt-line]'));
                }
            });

            tbody.addEventListener('click', (event) => {
                if (event.target.matches('[data-remove-line]')) {
                    const rows = tbody.querySelectorAll('[data-receipt-line]');
                    if (rows.length > 1) {
                        event.target.closest('[data-receipt-line]').remove();
                    }
                }
            });

            supplier.addEventListener('change', updateDueDate);
            paymentMethod.addEventListener('change', updateDueDate);
        })();
    </script>
@endsection
