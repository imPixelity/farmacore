<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Ringkasan') · FarmaCore</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">
            <div
                x-cloak
                x-show="sidebarOpen"
                x-transition.opacity
                class="fixed inset-0 z-40 bg-slate-950/40 lg:hidden"
                aria-hidden="true"
                x-on:click="sidebarOpen = false"
            ></div>

            <aside
                class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:static lg:translate-x-0"
                x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                aria-label="Navigasi utama"
            >
                <a href="{{ route('home') }}" class="flex h-20 items-center gap-3 border-b border-slate-100 px-6">
                    <span class="grid size-10 place-items-center rounded-xl bg-emerald-700 text-lg font-bold text-white">F</span>
                    <span>
                        <span class="block text-base font-bold tracking-tight text-slate-950">FarmaCore</span>
                        <span class="block text-xs text-slate-500">Sistem Informasi Apotek</span>
                    </span>
                </a>

                <nav class="flex-1 space-y-1 px-4 py-6">
                    <p class="mb-3 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Menu utama</p>
                    <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl bg-emerald-50 px-3 py-2.5 text-sm font-semibold text-emerald-800" aria-current="page">
                        <span class="grid size-8 place-items-center rounded-lg bg-white text-emerald-700">⌂</span>
                        Ringkasan
                    </a>
                    @can('viewAny', App\Models\User::class)
                        <a href="{{ route('users.index') }}" class="mt-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950">
                            <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">U</span>
                            Pengguna
                        </a>
                    @endcan
                    @can('core.audit.view')
                        <a href="{{ route('audit-logs.index') }}" class="mt-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950">
                            <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">A</span>
                            Audit log
                        </a>
                    @endcan
                    @can('core.settings.view')
                        <a href="{{ route('settings.index') }}" class="mt-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950">
                            <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">⚙</span>
                            Pengaturan
                        </a>
                    @endcan
                    <p class="mb-3 mt-8 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Modul</p>
                    @can('md.item.view')
                        <a href="{{ route('md.items.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950">
                            <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">M</span>
                            Barang
                        </a>
                    @endcan
                    @can('md.item.manage')
                        <a href="{{ route('md.categories.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950">
                            <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">K</span>
                            Kategori
                        </a>
                        <a href="{{ route('md.units.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950">
                            <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-500">S</span>
                            Satuan
                        </a>
                    @endcan
                    @foreach ([['Penjualan', 'pos.sale.view'], ['Persediaan', 'inventory.stock.view'], ['Pembelian', 'purchasing.po.manage'], ['Resep & layanan', 'rx.prescription.manage'], ['Laporan', 'report.view.sales']] as [$module, $permission])
                        @can($permission)
                            <button type="button" disabled class="flex w-full cursor-not-allowed items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm text-slate-400" aria-disabled="true">
                                <span class="flex items-center gap-3">
                                    <span class="grid size-8 place-items-center rounded-lg bg-slate-50 text-slate-400">·</span>
                                    {{ $module }}
                                </span>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500">Segera</span>
                            </button>
                        @endcan
                    @endforeach
                </nav>

                <div class="border-t border-slate-100 p-5">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold text-slate-700">Fondasi aplikasi</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Modul operasional akan tersedia bertahap sesuai roadmap.</p>
                    </div>
                </div>
            </aside>

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-8">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="grid size-10 place-items-center rounded-xl border border-slate-200 text-slate-600 lg:hidden"
                            aria-label="Buka navigasi"
                            x-on:click="sidebarOpen = true"
                        >
                            <span aria-hidden="true">☰</span>
                        </button>
                        <div>
                            <p class="text-xs font-medium text-slate-500">FarmaCore / <span class="text-slate-700">@yield('breadcrumb', 'Ringkasan')</span></p>
                            <h1 class="mt-1 text-lg font-bold tracking-tight text-slate-950">@yield('page-heading', 'Ringkasan')</h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @auth
                            <details class="relative">
                                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" aria-label="Notifikasi">
                                    <span aria-hidden="true">🔔</span>
                                    <span class="hidden sm:inline">Notifikasi</span>
                                    @if ($notificationSummary['unreadCount'] > 0)
                                        <span class="grid min-w-5 place-items-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $notificationSummary['unreadCount'] }}</span>
                                    @endif
                                </summary>
                                <div class="absolute right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white p-3 shadow-xl">
                                    <div class="flex items-center justify-between px-2 py-1">
                                        <p class="text-sm font-bold text-slate-900">Notifikasi terbaru</p>
                                        <span class="text-xs text-slate-500">{{ $notificationSummary['unreadCount'] }} belum dibaca</span>
                                    </div>
                                    <div class="mt-2 grid gap-2">
                                        @forelse ($notificationSummary['items'] as $notification)
                                            <div class="rounded-xl {{ $notification->read_at ? 'bg-slate-50' : 'bg-emerald-50' }} p-3">
                                                <p class="text-sm text-slate-800">{{ data_get($notification->data, 'message', 'Notifikasi baru') }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $notification->created_at->diffForHumans() }}</p>
                                                @if (! $notification->read_at)
                                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="mt-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-xs font-semibold text-emerald-800 hover:text-emerald-950">Tandai dibaca</button>
                                                    </form>
                                                @endif
                                            </div>
                                        @empty
                                            <p class="px-2 py-4 text-sm text-slate-500">Belum ada notifikasi.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </details>
                            <span class="hidden text-sm font-medium text-slate-600 sm:inline">{{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Keluar</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="rounded-xl bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Masuk</a>
                        @endauth
                    </div>
                </header>

                <main class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-8 sm:py-8">
                    @if (session('success'))
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status" aria-live="polite">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800" role="status" aria-live="polite">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                            <p class="font-semibold">Ada masukan yang perlu diperiksa:</p>
                            <ul class="mt-2 list-inside list-disc space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
