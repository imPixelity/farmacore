@extends('layouts.app')

@section('title', 'Piutang pelanggan')
@section('breadcrumb', 'Keuangan / Piutang pelanggan')
@section('page-heading', 'Piutang pelanggan')

@section('content')
    <div class="grid gap-5">
        <section class="grid gap-4 sm:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total piutang</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">Rp {{ number_format((float) $summary['total_amount'], 2, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sudah dibayar</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">Rp {{ number_format((float) $summary['paid_amount'], 2, ',', '.') }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sisa piutang</p>
                <p class="mt-2 text-2xl font-bold text-amber-700">Rp {{ number_format((float) $summary['remaining_amount'], 2, ',', '.') }}</p>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-900">Cari piutang</h2>
                    <p class="mt-1 text-sm text-slate-500">Filter berdasarkan nama/kode pasien atau status pembayaran.</p>
                </div>
                <a href="{{ route('accounting.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kas &amp; bank</a>
            </div>
            <form method="GET" action="{{ route('accounting.receivables.index') }}" class="mt-5 grid gap-3 md:grid-cols-[minmax(0,1fr)_14rem_auto] md:items-end">
                <label class="grid gap-1.5 text-xs font-semibold text-slate-600">Pasien<input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Nama atau kode pasien" class="rounded-xl border-slate-300 px-3 py-2.5 text-sm font-normal"></label>
                <label class="grid gap-1.5 text-xs font-semibold text-slate-600">Status<select name="status" class="rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm font-normal"><option value="">Semua status</option><option value="belum_dibayar" @selected($filters['status'] === 'belum_dibayar')>Belum dibayar</option><option value="sebagian" @selected($filters['status'] === 'sebagian')>Sebagian</option><option value="lunas" @selected($filters['status'] === 'lunas')>Lunas</option></select></label>
                <div class="flex gap-2"><a href="{{ route('accounting.receivables.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a><button class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Terapkan</button></div>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="font-bold text-slate-900">Piutang per pelanggan</h2><p class="mt-1 text-sm text-slate-500">Saldo dan riwayat pembayaran bersumber dari transaksi kredit.</p></div>
            <div class="overflow-x-auto"><table class="w-full min-w-280 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Pelanggan</th><th class="px-5 py-3">Penjualan</th><th class="px-5 py-3">Total</th><th class="px-5 py-3">Dibayar</th><th class="px-5 py-3">Sisa</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Bayar</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($receivables as $receivable)
                        @php($remaining = bcsub((string) $receivable->total_amount, (string) $receivable->paid_amount, 2))
                        <tr class="align-top">
                            <td class="px-5 py-4 font-semibold text-slate-900">{{ $receivable->patient->name }}<span class="mt-1 block text-xs font-normal text-slate-500">{{ $receivable->patient->code }}</span></td>
                            <td class="px-5 py-4">{{ $receivable->sale?->invoice_no ?? '—' }}<span class="mt-1 block text-xs text-slate-500">{{ $receivable->sale?->sale_date?->format('d-m-Y H:i') ?? '—' }}</span></td>
                            <td class="px-5 py-4 font-semibold">Rp {{ number_format((float) $receivable->total_amount, 2, ',', '.') }}</td>
                            <td class="px-5 py-4 text-emerald-700">Rp {{ number_format((float) $receivable->paid_amount, 2, ',', '.') }}</td>
                            <td class="px-5 py-4 font-bold text-amber-700">Rp {{ number_format((float) $remaining, 2, ',', '.') }}</td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $receivable->status === 'lunas' ? 'bg-emerald-50 text-emerald-800' : ($receivable->status === 'sebagian' ? 'bg-amber-50 text-amber-800' : 'bg-rose-50 text-rose-800') }}">{{ str_replace('_', ' ', ucfirst($receivable->status)) }}</span>
                                @if ($receivable->payments->isNotEmpty())
                                    <details class="mt-2 text-xs"><summary class="cursor-pointer font-semibold text-slate-600">{{ $receivable->payments->count() }} pembayaran</summary><div class="mt-2 space-y-1 text-slate-500">@foreach ($receivable->payments as $payment)<p>{{ $payment->payment_date->format('d-m-Y') }} · Rp {{ number_format((float) $payment->amount, 2, ',', '.') }} · {{ $payment->cashBankAccount->name }}</p>@endforeach</div></details>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @can('accounting.receivable.manage')
                                    @if ($receivable->status !== 'lunas')
                                        <form method="POST" action="{{ route('accounting.receivables.pay', $receivable) }}" class="grid min-w-64 gap-2">
                                            @csrf
                                            <input type="hidden" name="redirect_status" value="{{ $filters['status'] }}"><input type="hidden" name="redirect_search" value="{{ $filters['search'] }}">
                                            <input type="number" name="amount" min="0.01" max="{{ $remaining }}" step="0.01" placeholder="Nominal" required class="rounded-lg border-slate-300 px-2.5 py-2 text-xs">
                                            <select name="cash_bank_account_id" required class="rounded-lg border-slate-300 bg-white px-2.5 py-2 text-xs"><option value="">Akun kas/bank</option>@foreach ($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }} · Rp {{ number_format((float) $account->current_balance, 2, ',', '.') }}</option>@endforeach</select>
                                            <input type="date" name="payment_date" value="{{ today()->toDateString() }}" required class="rounded-lg border-slate-300 px-2.5 py-2 text-xs">
                                            <button class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800">Catat pembayaran</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">Sudah lunas</span>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Belum ada piutang sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            @if ($receivables->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $receivables->links() }}</div>@endif
        </section>
    </div>
@endsection
