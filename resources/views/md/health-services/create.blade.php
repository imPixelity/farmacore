@extends('layouts.app')

@section('title', 'Tambah layanan')
@section('breadcrumb', 'Data master / Layanan / Tambah')
@section('page-heading', 'Tambah layanan')

@section('content')
    <form method="POST" action="{{ route('md.health-services.store') }}" class="space-y-6">@csrf
        @include('md.health-services._form')
        <div class="flex justify-end gap-3"><a href="{{ route('md.health-services.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</a><button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white">Simpan layanan</button></div>
    </form>
@endsection
