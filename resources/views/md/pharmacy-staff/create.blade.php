@extends('layouts.app')

@section('title', 'Tambah tenaga farmasi')
@section('breadcrumb', 'Data master / Tenaga farmasi / Tambah')
@section('page-heading', 'Tambah tenaga farmasi')

@section('content')
    <form method="POST" action="{{ route('md.pharmacy-staff.store') }}" class="space-y-6">@csrf
        @include('md.pharmacy-staff._form')
        <div class="flex justify-end gap-3"><a href="{{ route('md.pharmacy-staff.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</a><button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white">Simpan profil</button></div>
    </form>
@endsection
