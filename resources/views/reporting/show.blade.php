@extends('layouts.app')

@section('title', $definition['title'])
@section('breadcrumb', 'Pelaporan / '.$definition['title'])
@section('page-heading', $definition['title'])

@section('content')
    <div class="grid gap-5">
        <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:flex-row sm:items-end sm:justify-between sm:p-6">
            <div>
                <a href="{{ route('reports.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">← Semua laporan</a>
                <p class="mt-2 max-w-2xl text-sm text-slate-500">{{ $definition['description'] }}</p>
                <p class="mt-1 text-xs font-medium text-slate-400">{{ $definition['requirement'] }}</p>
            </div>
            <form method="GET" action="{{ route('reports.show', request()->route('report')) }}" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                <label class="grid gap-1.5 text-xs font-semibold text-slate-600">Tanggal mulai
                    <input type="date" name="from" value="{{ $from }}" required class="rounded-xl border-slate-300 text-sm">
                </label>
                <label class="grid gap-1.5 text-xs font-semibold text-slate-600">Tanggal akhir
                    <input type="date" name="to" value="{{ $to }}" required class="rounded-xl border-slate-300 text-sm">
                </label>
                <button class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Tampilkan</button>
            </form>
            <div class="flex flex-wrap gap-2">
                @foreach (['pdf' => 'PDF', 'xlsx' => 'XLSX'] as $format => $label)
                    <form method="POST" action="{{ route('reports.exports.store', request()->route('report')) }}">
                        @csrf
                        <input type="hidden" name="from" value="{{ $from }}">
                        <input type="hidden" name="to" value="{{ $to }}">
                        <input type="hidden" name="format" value="{{ $format }}">
                        <button class="rounded-xl border border-emerald-700 px-4 py-2.5 text-sm font-semibold text-emerald-800 hover:bg-emerald-50">Ekspor {{ $label }}</button>
                    </form>
                @endforeach
            </div>
        </section>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <p class="font-semibold">Rentang tanggal tidak valid.</p>
                @foreach ($errors->all() as $error)
                    <p class="mt-1">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($summary as $card)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-2 text-xl font-bold text-slate-900">{{ $formatter->display($card['value'], $card['format']) }}</p>
                </article>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
            <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="font-bold text-slate-900">{{ $definition['title'] }}</h2>
                <p class="text-sm text-slate-500">Periode {{ \Illuminate\Support\Carbon::parse($from)->format('d-m-Y') }} sampai {{ \Illuminate\Support\Carbon::parse($to)->format('d-m-Y') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-180 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            @foreach ($definition['columns'] as $column)
                                <th scope="col" class="whitespace-nowrap px-5 py-3">{{ $column['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            <tr>
                                @foreach ($definition['columns'] as $column)
                                    @php($value = data_get($row, $column['key']))
                                    <td class="px-5 py-4 {{ in_array($column['format'], ['money', 'quantity', 'decimal', 'integer', 'percent'], true) ? 'whitespace-nowrap tabular-nums' : '' }}">
                                        {{ $formatter->display($value, $column['format']) }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($definition['columns']) }}" class="px-5 py-10 text-center text-slate-500">Tidak ada data laporan pada rentang tanggal ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($rows instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">{{ $rows->links() }}</div>
            @endif
        </section>

        <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
            <div>
                <h2 class="font-bold text-slate-900">Ekspor laporan</h2>
                <p class="mt-1 text-sm text-slate-500">Ekspor diproses di latar belakang. Muat ulang halaman untuk memperbarui status.</p>
            </div>
            @forelse ($exports as $export)
                <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ strtoupper($export->format) }} · Periode {{ $export->from_date->format('d-m-Y') }} sampai {{ $export->to_date->format('d-m-Y') }}</p>
                        <p class="mt-1 text-xs text-slate-500">Diminta {{ $export->created_at->format('d-m-Y H:i') }} · Status: {{ match ($export->status) {'queued' => 'Menunggu', 'processing' => 'Diproses', 'completed' => 'Siap diunduh', 'failed' => 'Gagal', default => 'Tidak diketahui'} }}</p>
                        @if ($export->status === 'failed')
                            <p class="mt-1 text-sm text-rose-700" role="alert">{{ $export->failure_message }}</p>
                        @endif
                    </div>
                    @if ($export->status === 'completed')
                        <a href="{{ route('reports.exports.download', $export) }}" class="w-fit rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Unduh</a>
                    @endif
                </div>
            @empty
                <p class="border-t border-slate-100 pt-4 text-sm text-slate-500">Belum ada ekspor untuk laporan ini.</p>
            @endforelse
        </section>
    </div>
@endsection
