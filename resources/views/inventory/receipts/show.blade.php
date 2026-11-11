@extends('layouts.app')

@section('title', 'Rincian penerimaan')
@section('breadcrumb', 'Persediaan / Penerimaan / Rincian')
@section('page-heading', 'Rincian penerimaan')

@section('content')
    <div class="grid gap-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <a href="{{ route('inventory.receipts.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">← Kembali ke penerimaan</a>
                    <h2 class="mt-3 text-xl font-bold text-slate-900">{{ $receipt->receipt_no }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Faktur {{ $receipt->invoice_no }} · {{ $receipt->supplier->name }}</p>
                </div>
                <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $receipt->status === 'confirmed' ? 'bg-emerald-50 text-emerald-700' : ($receipt->status === 'cancelled' ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-800') }}">{{ ucfirst($receipt->status) }}</span>
            </div>

            <dl class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal faktur</dt><dd class="mt-1 font-bold text-slate-900">{{ $receipt->invoice_date->format('d-m-Y') }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pembayaran</dt><dd class="mt-1 font-bold capitalize text-slate-900">{{ $receipt->payment_method }}{{ $receipt->due_date ? ' · tempo '.$receipt->due_date->format('d-m-Y') : '' }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status pencocokan</dt><dd class="mt-1 font-bold text-slate-900">Tanpa PO</dd></div>
                <div class="rounded-xl bg-emerald-50 p-4"><dt class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Dikonfirmasi</dt><dd class="mt-1 font-bold text-emerald-900">{{ $receipt->confirmed_at?->format('d-m-Y H:i') ?? 'Belum dikonfirmasi' }}{{ $receipt->confirmer ? ' · '.$receipt->confirmer->name : '' }}</dd></div>
            </dl>
        </section>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                <p class="font-semibold">Penerimaan belum dapat dikonfirmasi.</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="font-bold text-slate-900">Rincian barang</h2>
                <p class="mt-1 text-sm text-slate-500">Stok ditampilkan dalam satuan yang ditagihkan dan satuan dasar barang.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-190 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Barang / batch</th>
                            <th class="px-6 py-3 font-semibold">Kedaluwarsa</th>
                            <th class="px-6 py-3 text-right font-semibold">Jumlah diterima</th>
                            <th class="px-6 py-3 text-right font-semibold">Harga / diskon</th>
                            <th class="px-6 py-3 text-right font-semibold">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($receipt->items as $line)
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-900">
                                    {{ $line->item->name }}
                                    <span class="mt-1 block text-xs font-normal text-slate-500">{{ $line->batch_no }} · {{ $line->item->code }}</span>
                                    @if ($line->approved_by)
                                        <span class="mt-1 block text-xs text-amber-800">Pengecualian ED disetujui {{ $line->approver?->name ?? 'apoteker' }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $line->expiry_date->format('d-m-Y') }}</td>
                                <td class="px-6 py-4 text-right text-slate-700">{{ $line->qty_received }} {{ $line->unit->name }}<span class="mt-1 block text-xs text-slate-500">{{ $line->qty_base }} {{ $line->item->baseUnit->name }}</span></td>
                                <td class="px-6 py-4 text-right text-slate-700">{{ $line->unit_price }}<span class="mt-1 block text-xs text-slate-500">Diskon {{ $line->discount_amount }}</span></td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-900">{{ $line->line_total }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="grid gap-2 border-t border-slate-100 p-5 text-sm sm:ml-auto sm:w-96 sm:px-6">
                <div class="flex justify-between gap-4 text-slate-600"><span>Diskon faktur</span><span>{{ $receipt->discount_amount }}</span></div>
                <div class="flex justify-between gap-4 text-slate-600"><span>Total sebelum PPN</span><span>{{ $receipt->total_before_tax }}</span></div>
                <div class="flex justify-between gap-4 text-slate-600"><span>PPN masukan</span><span>{{ $receipt->tax_amount }}</span></div>
                <div class="flex justify-between gap-4 border-t border-slate-200 pt-3 font-bold text-slate-900"><span>Total tagihan</span><span>{{ $receipt->total_after_tax }}</span></div>
            </div>
        </section>

        @if ($receipt->payable)
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:p-6">
                <h2 class="font-bold text-amber-950">Utang supplier terbentuk</h2>
                <p class="mt-1 text-sm text-amber-900">{{ $receipt->payable->invoice_no }} · jatuh tempo {{ $receipt->payable->due_date->format('d-m-Y') }} · {{ $receipt->payable->total_amount }} · {{ str_replace('_', ' ', $receipt->payable->status) }}</p>
                <p class="mt-1 text-xs text-amber-800">Pencatatan pembayaran utang tersedia pada phase berikutnya.</p>
            </section>
        @endif

        <div class="flex flex-wrap items-center justify-end gap-3">
            @if ($receipt->status === 'draft')
                @can('inventory.receipt.manage')
                    <a href="{{ route('inventory.receipts.edit', $receipt) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ubah draft</a>
                    <form method="POST" action="{{ route('inventory.receipts.confirm', $receipt) }}" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-3">
                        @csrf
                        @if ($receipt->payment_method === 'tunai')
                            <label>
                                <span class="mb-1 block text-xs font-semibold text-slate-600">Akun kas/bank</span>
                                <select name="cash_bank_account_id" required class="min-w-52 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                                    <option value="">Pilih akun aktif</option>
                                    @foreach ($cashBankAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->type }}) · saldo {{ $account->current_balance }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        <button class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Konfirmasi penerimaan</button>
                    </form>
                @endcan
            @else
                <p class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-600">Penerimaan terkonfirmasi bersifat immutable; koreksi melalui proses retur.</p>
            @endif
        </div>
    </div>
@endsection
