@extends('layouts.app')

@section('title', 'Kas & bank')
@section('breadcrumb', 'Keuangan / Kas & bank')
@section('page-heading', 'Kas & bank')

@section('content')
    <div class="grid gap-5">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @forelse ($accounts as $account)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2">
                    <div class="flex items-start justify-between gap-3">
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $account->type === 'kas' ? 'Kas' : 'Bank' }}</p><h2 class="mt-1 font-bold text-slate-900">{{ $account->name }}</h2></div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $account->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>
                    @if ($account->account_no)<p class="mt-2 text-xs text-slate-500">{{ $account->account_no }}</p>@endif
                    <p class="mt-5 text-xs text-slate-500">Saldo berjalan</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">Rp {{ number_format((float) $account->current_balance, 2, ',', '.') }}</p>
                    <p class="mt-2 text-xs text-slate-500">Saldo awal Rp {{ number_format((float) $account->opening_balance, 2, ',', '.') }}</p>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500 sm:col-span-2 xl:col-span-4">Belum ada akun kas atau bank.</p>
            @endforelse
        </section>

        @can('accounting.cash.manage')
            <div class="grid gap-5 xl:grid-cols-3">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
                    <h2 class="font-bold text-slate-900">Tambah akun kas/bank</h2>
                    <p class="mt-1 text-sm text-slate-500">Saldo awal ditetapkan saat pembuatan; saldo berjalan berikutnya hanya berubah melalui posting.</p>
                    <form method="POST" action="{{ route('accounting.accounts.store') }}" class="mt-5 grid gap-4" x-data="{ type: @js(old('type', 'kas')) }">
                        @csrf
                        <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Nama akun<input name="name" value="{{ old('name') }}" maxlength="100" required class="rounded-xl border-slate-300 font-normal">@error('name')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror</label>
                        <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Jenis akun<select name="type" x-model="type" required class="rounded-xl border-slate-300 bg-white font-normal"><option value="kas">Kas</option><option value="bank">Bank</option></select>@error('type')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror</label>
                        <label x-show="type === 'bank'" class="grid gap-1.5 text-sm font-semibold text-slate-700">Nomor rekening<input name="account_no" value="{{ old('account_no') }}" x-bind:disabled="type !== 'bank'" maxlength="50" class="rounded-xl border-slate-300 font-normal">@error('account_no')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror</label>
                        <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Saldo awal (Rp)<input type="number" name="opening_balance" min="0" step="0.01" value="{{ old('opening_balance', '0.00') }}" required class="rounded-xl border-slate-300 font-normal">@error('opening_balance')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror</label>
                        <button class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white">Simpan akun</button>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
                    <h2 class="font-bold text-slate-900">Tambah kategori</h2>
                    <p class="mt-1 text-sm text-slate-500">Kategori sistem digunakan untuk posting otomatis dan tidak dapat dihapus.</p>
                    <form method="POST" action="{{ route('accounting.categories.store') }}" class="mt-5 grid gap-4">
                        @csrf
                        <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Nama kategori<input name="name" value="{{ old('name') }}" maxlength="100" required class="rounded-xl border-slate-300 font-normal">@error('name')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror</label>
                        <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Arah<select name="direction" required class="rounded-xl border-slate-300 bg-white font-normal"><option value="out" @selected(old('direction', 'out') === 'out')>Pengeluaran</option><option value="in" @selected(old('direction') === 'in')>Pemasukan</option></select>@error('direction')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror</label>
                        <button class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white">Simpan kategori</button>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
                    <h2 class="font-bold text-slate-900">Catat pengeluaran</h2>
                    <form method="POST" action="{{ route('accounting.expenses.store') }}" class="mt-5 grid gap-4">
                        @csrf
                        <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Akun<select name="cash_bank_account_id" required class="rounded-xl border-slate-300 bg-white font-normal"><option value="">Pilih akun</option>@foreach ($activeAccounts as $account)<option value="{{ $account->id }}" @selected((string) old('cash_bank_account_id') === (string) $account->id)>{{ $account->name }} — Rp {{ number_format((float) $account->current_balance, 2, ',', '.') }}</option>@endforeach</select>@error('cash_bank_account_id')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror</label>
                        <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Kategori<select name="category_id" required class="rounded-xl border-slate-300 bg-white font-normal"><option value="">Pilih kategori</option>@foreach ($expenseCategories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>@error('category_id')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror</label>
                        <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Tanggal<input type="date" name="trx_date" value="{{ old('trx_date', today()->toDateString()) }}" required class="rounded-xl border-slate-300 font-normal">@error('trx_date')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror</label>
                        <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Jumlah (Rp)<input type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" required class="rounded-xl border-slate-300 font-normal">@error('amount')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror</label>
                        <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Keterangan<textarea name="description" rows="2" maxlength="255" required class="rounded-xl border-slate-300 font-normal">{{ old('description') }}</textarea>@error('description')<span class="font-normal text-rose-700">{{ $message }}</span>@enderror</label>
                        <button class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white">Posting pengeluaran</button>
                    </form>
                </section>
            </div>
        @endcan

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-900">Kategori transaksi</h2></div>
            <div class="overflow-x-auto"><table class="w-full min-w-120 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Kategori</th><th class="px-5 py-3">Arah</th><th class="px-5 py-3">Jenis</th><th class="px-5 py-3">Status</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        <tr><td class="px-5 py-4 font-medium text-slate-900">{{ $category->name }}</td><td class="px-5 py-4">{{ $category->direction === 'in' ? 'Pemasukan' : 'Pengeluaran' }}</td><td class="px-5 py-4">{{ $category->is_system ? 'Sistem' : 'Manual' }}</td><td class="px-5 py-4">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-slate-500">Belum ada kategori transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-900">Buku kas/bank</h2><p class="mt-1 text-sm text-slate-500">Transaksi yang sudah diposting tidak dapat diubah atau dihapus. Koreksi dicatat sebagai transaksi lawan.</p></div>
            <div class="overflow-x-auto"><table class="w-full min-w-240 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">No. transaksi</th><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Akun / kategori</th><th class="px-5 py-3">Arah</th><th class="px-5 py-3">Jumlah</th><th class="px-5 py-3">Keterangan</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Koreksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td class="px-5 py-4 font-medium text-slate-900">{{ $transaction->trx_no }}</td>
                            <td class="px-5 py-4">{{ $transaction->trx_date->format('d-m-Y') }}</td>
                            <td class="px-5 py-4">{{ $transaction->account->name }}<span class="mt-1 block text-xs text-slate-500">{{ $transaction->category->name }}</span></td>
                            <td class="px-5 py-4">{{ $transaction->direction === 'in' ? 'Masuk' : 'Keluar' }}</td>
                            <td class="px-5 py-4 font-semibold">Rp {{ number_format((float) $transaction->amount, 2, ',', '.') }}</td>
                            <td class="px-5 py-4">{{ $transaction->description }}</td>
                            <td class="px-5 py-4">{{ $transaction->status === 'reversed' ? 'Dikoreksi' : ($transaction->reverses_id ? 'Transaksi lawan' : 'Tercatat') }}</td>
                            <td class="px-5 py-4">
                                @can('accounting.cash.manage')
                                    @if ($transaction->status === 'posted' && $transaction->reverses_id === null && ! $transaction->reversal)
                                        <form method="POST" action="{{ route('accounting.transactions.reverse', $transaction) }}" class="flex min-w-52 gap-2">
                                            @csrf @method('PATCH')
                                            <input name="description" required maxlength="200" placeholder="Alasan koreksi" class="min-w-0 rounded-lg border-slate-300 text-xs">
                                            <button class="rounded-lg border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-700">Balik</button>
                                        </form>
                                    @elseif ($transaction->reversal)
                                        <span class="text-xs text-slate-500">Dibalik: {{ $transaction->reversal->trx_no }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                    @error('description')<span class="mt-1 block text-xs text-rose-700">{{ $message }}</span>@enderror
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">Belum ada transaksi keuangan.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            @if ($transactions->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $transactions->links() }}</div>@endif
        </section>
    </div>
@endsection
