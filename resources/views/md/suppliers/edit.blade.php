@extends('layouts.app')

@section('title', 'Ubah supplier')
@section('breadcrumb', 'Data master / Supplier / Ubah')
@section('page-heading', 'Ubah supplier')

@section('content')
    <form method="POST" action="{{ route('md.suppliers.update', $supplier) }}" class="space-y-6">@csrf @method('PUT')
        @include('md.suppliers._form')
        <div class="flex justify-end gap-3"><a href="{{ route('md.suppliers.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Kembali</a><button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white">Simpan perubahan</button></div>
    </form>
@endsection
