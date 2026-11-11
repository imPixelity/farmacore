@extends('layouts.app')

@section('title', 'Tambah pasien')
@section('breadcrumb', 'Data master / Pasien / Tambah')
@section('page-heading', 'Tambah pasien')

@section('content')
    <form method="POST" action="{{ route('md.patients.store') }}" class="space-y-6">@csrf
        @include('md.patients._form')
        <div class="flex justify-end gap-3"><a href="{{ route('md.patients.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</a><button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white">Simpan pasien</button></div>
    </form>
@endsection
