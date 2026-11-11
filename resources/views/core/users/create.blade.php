@extends('layouts.app')

@section('title', 'Tambah pengguna')
@section('breadcrumb', 'Pengguna / Tambah')
@section('page-heading', 'Tambah pengguna')

@section('content')
    <section class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/2 sm:p-8">
        <form method="POST" action="{{ route('users.store') }}" class="space-y-5">
            @csrf
            @include('core.users.form', ['user' => null])
            <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                <button type="submit" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan pengguna</button>
                <a href="{{ route('users.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
            </div>
        </form>
    </section>
@endsection
