@extends('layouts.app')

@section('title', 'Ubah layanan')
@section('breadcrumb', 'Data master / Layanan / Ubah')
@section('page-heading', 'Ubah layanan')

@section('content')
    <form method="POST" action="{{ route('md.health-services.update', $healthService) }}" class="space-y-6">@csrf @method('PUT')
        @include('md.health-services._form')
        <div class="flex justify-end gap-3"><a href="{{ route('md.health-services.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Kembali</a><button class="rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white">Simpan perubahan</button></div>
    </form>
@endsection
