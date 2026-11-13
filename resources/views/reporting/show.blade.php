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
                    <p class="mt-2 text-xl font-bold text-slate-900">
                        @if ($card['value'] === null)
                            —
                        @elseif ($card['format'] === 'money')
                            Rp {{ number_format((float) $card['value'], 2, ',', '.') }}
                        @elseif ($card['format'] === 'percent')
                            {{ number_format((float) $card['value'], 2, ',', '.') }}%
                        @elseif ($card['format'] === 'quantity')
                            {{ number_format((float) $card['value'], 3, ',', '.') }}
                        @elseif ($card['format'] === 'decimal')
                            {{ number_format((float) $card['value'], 1, ',', '.') }}
                        @else
                            {{ number_format((float) $card['value'], 0, ',', '.') }}
                        @endif
                    </p>
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
                                        @if ($value === null || $value === '')
                                            —
                                        @elseif ($column['format'] === 'money')
                                            Rp {{ number_format((float) $value, 2, ',', '.') }}
                                        @elseif ($column['format'] === 'quantity')
                                            {{ number_format((float) $value, 3, ',', '.') }}
                                        @elseif ($column['format'] === 'decimal')
                                            {{ number_format((float) $value, 1, ',', '.') }}
                                        @elseif ($column['format'] === 'integer')
                                            {{ number_format((float) $value, 0, ',', '.') }}
                                        @elseif ($column['format'] === 'percent')
                                            {{ number_format((float) $value, 2, ',', '.') }}%
                                        @elseif ($column['format'] === 'date')
                                            {{ \Illuminate\Support\Carbon::parse($value)->format('d-m-Y') }}
                                        @elseif ($column['format'] === 'datetime')
                                            {{ \Illuminate\Support\Carbon::parse($value)->format('d-m-Y H:i') }}
                                        @elseif ($column['format'] === 'direction')
                                            {{ $value === 'in' ? 'Masuk' : 'Keluar' }}
                                        @else
                                            {{ str_replace('_', ' ', (string) $value) }}
                                        @endif
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
    </div>
@endsection
