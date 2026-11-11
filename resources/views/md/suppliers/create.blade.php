@extends('layouts.app')

@section('title', 'Tambah supplier')
@section('breadcrumb', 'Data master / Supplier / Tambah')
@section('page-heading', 'Tambah supplier')

@section('content')
    <form method="POST" action="{{ route('md.suppliers.store') }}" class="space-y-6">@csrf
        @include('md.suppliers._form')
        <div class="flex justify-end gap-3"><a href="{{ route('md.suppliers.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</a><button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white">Simpan supplier</button></div>
    </form>
@endsection
