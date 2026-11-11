@extends('layouts.app')

@section('title', 'Ubah pasien')
@section('breadcrumb', 'Data master / Pasien / Ubah')
@section('page-heading', 'Ubah pasien')

@section('content')
    <form method="POST" action="{{ route('md.patients.update', $patient) }}" class="space-y-6">@csrf @method('PUT')
        @include('md.patients._form')
        <div class="flex justify-end gap-3"><a href="{{ route('md.patients.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Kembali</a><button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white">Simpan perubahan</button></div>
    </form>
@endsection
