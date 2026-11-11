@extends('layouts.app')

@section('title', 'Pengguna')
@section('breadcrumb', 'Pengguna')
@section('page-heading', 'Pengelolaan pengguna')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="font-bold text-slate-900">Daftar pengguna</h2>
                <p class="mt-1 text-sm text-slate-500">Kelola akun dan peran akses aplikasi.</p>
            </div>
            @can('create', App\Models\User::class)
                <a href="{{ route('users.create') }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Tambah pengguna</a>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-170 text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Nama</th>
                        <th class="px-6 py-3 font-semibold">Nama pengguna</th>
                        <th class="px-6 py-3 font-semibold">Peran</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                {{ $user->name }}
                                @if ($user->email)
                                    <span class="mt-1 block text-xs font-normal text-slate-500">{{ $user->email }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $user->username }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $user->roles->pluck('name')->join(', ') ?: 'Belum ditetapkan' }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @can('update', $user)
                                    <a href="{{ route('users.edit', $user) }}" class="font-semibold text-emerald-700 hover:text-emerald-900">Ubah</a>
                                @endcan
                                @can('deactivate', $user)
                                    <form method="POST" action="{{ route('users.status', $user) }}" class="ml-3 inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                                        <button type="submit" class="font-semibold text-rose-700 hover:text-rose-900">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">Belum ada pengguna.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">{{ $users->links() }}</div>
        @endif
    </section>
@endsection
