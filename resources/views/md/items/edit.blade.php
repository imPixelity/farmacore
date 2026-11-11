@extends('layouts.app')

@section('title', 'Ubah barang')
@section('breadcrumb', 'Data master / Barang / Ubah')
@section('page-heading', 'Ubah barang')

@section('content')
    <form method="POST" action="{{ route('md.items.update', $item) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('md.items._form')
        <div class="flex justify-end gap-3"><a href="{{ route('md.items.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali</a><button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan perubahan</button></div>
    </form>
@endsection
