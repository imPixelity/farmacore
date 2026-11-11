@extends('layouts.app')

@section('title', 'Tambah dokter')
@section('breadcrumb', 'Data master / Dokter / Tambah')
@section('page-heading', 'Tambah dokter')

@section('content')
    <form method="POST" action="{{ route('md.doctors.store') }}" class="space-y-6">@csrf
        @include('md.doctors._form')
        <div class="flex justify-end gap-3"><a href="{{ route('md.doctors.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</a><button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white">Simpan dokter</button></div>
    </form>
@endsection
