<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="username" class="mb-2 block text-sm font-semibold text-slate-700">Nama pengguna</label>
        <input id="username" name="username" type="text" value="{{ old('username', $user?->username) }}" required maxlength="50" autocomplete="username" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
        @error('username') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Nama lengkap</label>
        <input id="name" name="name" type="text" value="{{ old('name', $user?->name) }}" required maxlength="100" autocomplete="name" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
        @error('name') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email <span class="font-normal text-slate-400">(opsional)</span></label>
        <input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" autocomplete="email" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
        @error('email') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="role" class="mb-2 block text-sm font-semibold text-slate-700">Peran</label>
        <select id="role" name="role" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
            <option value="">Pilih peran</option>
            @foreach ($roles as $role)
                <option value="{{ $role->name }}" @selected(old('role', $user?->roles->first()?->name) === $role->name)>{{ str($role->name)->replace('_', ' ')->title() }}</option>
            @endforeach
        </select>
        @error('role') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Kata sandi {{ $user ? '(opsional)' : '' }}</label>
        <input id="password" name="password" type="password" @required(! $user) minlength="12" autocomplete="new-password" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
        @if ($user) <p class="mt-1 text-xs text-slate-500">Kosongkan jika tidak ingin mengganti kata sandi.</p> @endif
        @error('password') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-700">Konfirmasi kata sandi</label>
        <input id="password_confirmation" name="password_confirmation" type="password" @required(! $user) minlength="12" autocomplete="new-password" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
    </div>
</div>
