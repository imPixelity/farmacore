@extends('layouts.app')

@section('title', 'Audit log')
@section('breadcrumb', 'Audit log')
@section('page-heading', 'Audit log')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="grid gap-4 border-b border-slate-100 p-5 sm:grid-cols-2 lg:grid-cols-5">
            <label class="grid gap-1 text-sm font-medium text-slate-700">
                Event
                <select name="event" class="rounded-xl border-slate-300 text-sm">
                    <option value="">Semua event</option>
                    @foreach (['created' => 'Dibuat', 'updated' => 'Diperbarui', 'deleted' => 'Dihapus'] as $event => $label)
                        <option value="{{ $event }}" @selected(($filters['event'] ?? '') === $event)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-sm font-medium text-slate-700">
                Aktor
                <select name="user_id" class="rounded-xl border-slate-300 text-sm">
                    <option value="">Semua pengguna</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-sm font-medium text-slate-700">
                Jenis data
                <select name="model" class="rounded-xl border-slate-300 text-sm">
                    <option value="">Semua jenis</option>
                    @foreach ($models as $class => $label)
                        <option value="{{ $class }}" @selected(($filters['model'] ?? '') === $class)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-sm font-medium text-slate-700">
                Dari tanggal
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-xl border-slate-300 text-sm">
            </label>
            <label class="grid gap-1 text-sm font-medium text-slate-700">
                Sampai tanggal
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-xl border-slate-300 text-sm">
            </label>
            <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-5">
                <button type="submit" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Terapkan filter</button>
                <a href="{{ route('audit-logs.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-240 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Waktu</th>
                        <th class="px-5 py-3 font-semibold">Aktor</th>
                        <th class="px-5 py-3 font-semibold">Aktivitas</th>
                        <th class="px-5 py-3 font-semibold">Data</th>
                        <th class="px-5 py-3 font-semibold">Perubahan</th>
                        <th class="px-5 py-3 font-semibold">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($auditLogs as $auditLog)
                        <tr class="align-top">
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $auditLog->created_at->format('d-m-Y H:i:s') }}</td>
                            <td class="px-5 py-4 font-medium text-slate-800">{{ $auditLog->actor?->name ?? 'Sistem' }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ ucfirst($auditLog->event) }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ class_basename($auditLog->auditable_type) }} #{{ $auditLog->auditable_id }}</td>
                            <td class="max-w-96 px-5 py-4">
                                <details>
                                    <summary class="cursor-pointer font-semibold text-emerald-700">Lihat detail</summary>
                                    <div class="mt-3 grid gap-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nilai lama</p>
                                            <pre class="mt-1 overflow-x-auto rounded-lg bg-slate-50 p-3 text-xs text-slate-700">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '—' }}</pre>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nilai baru</p>
                                            <pre class="mt-1 overflow-x-auto rounded-lg bg-slate-50 p-3 text-xs text-slate-700">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '—' }}</pre>
                                        </div>
                                    </div>
                                </details>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $auditLog->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Belum ada aktivitas yang tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($auditLogs->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $auditLogs->links() }}</div>
        @endif
    </section>
@endsection
