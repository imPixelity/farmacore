@extends('layouts.app')

@section('title', 'Tambah barang')
@section('breadcrumb', 'Data master / Barang / Tambah')
@section('page-heading', 'Tambah barang')

@section('content')
    <form method="POST" action="{{ route('md.items.store') }}" class="space-y-6">
        @csrf
        @include('md.items._form')
        <div class="flex justify-end gap-3"><a href="{{ route('md.items.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a><button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan barang</button></div>
    </form>
@endsection
