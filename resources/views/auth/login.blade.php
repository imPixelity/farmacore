<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Masuk · FarmaCore</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="grid min-h-screen place-items-center bg-slate-50 px-4 py-10 font-sans text-slate-900 antialiased">
        <main class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-7 shadow-xl shadow-slate-900/4 sm:p-9">
            <a href="{{ route('login') }}" class="flex items-center gap-3">
                <span class="grid size-11 place-items-center rounded-xl bg-emerald-700 text-lg font-bold text-white">F</span>
                <span>
                    <span class="block text-lg font-bold tracking-tight">FarmaCore</span>
                    <span class="block text-xs text-slate-500">Sistem Informasi Apotek</span>
                </span>
            </a>

            <div class="mt-8">
                <h1 class="text-2xl font-bold tracking-tight">Masuk ke akun</h1>
                <p class="mt-2 text-sm text-slate-500">Gunakan nama pengguna dan kata sandi Anda.</p>
            </div>

            @if (session('status'))
                <p class="mt-5 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800" role="status">{{ session('status') }}</p>
            @endif

            @if ($errors->any())
                <p class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ $errors->first() }}</p>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="username" class="mb-2 block text-sm font-semibold text-slate-700">Nama pengguna</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus autocomplete="username" maxlength="50" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Kata sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                </div>
                <button type="submit" class="w-full rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-200">Masuk</button>
            </form>
        </main>
    </body>
</html>
