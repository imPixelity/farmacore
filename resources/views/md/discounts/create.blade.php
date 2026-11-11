@extends('layouts.app')

@section('title', 'Tambah aturan diskon')
@section('breadcrumb', 'Data master / Harga / Diskon / Tambah')
@section('page-heading', 'Tambah aturan diskon')

@section('content')
    <form method="POST" action="{{ route('md.discounts.store') }}" class="grid gap-5">
        @csrf
        @include('md.discounts._form')
        <div class="flex flex-wrap justify-end gap-3"><a href="{{ route('md.discounts.index') }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700">Batal</a><button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white">Simpan aturan</button></div>
    </form>
@endsection
