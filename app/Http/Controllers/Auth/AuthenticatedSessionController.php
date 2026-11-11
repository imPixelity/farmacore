<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Modules\Core\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request, AuthService $authService): RedirectResponse
    {
        $user = $authService->attempt(
            $request->string('username')->toString(),
            $request->string('password')->toString(),
            $request->ip(),
        );

        if ($user === null) {
            return redirect()->route('login')
                ->withErrors(['username' => 'Nama pengguna atau kata sandi salah, atau akun tidak aktif/terkunci.'])
                ->onlyInput('username');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('last_activity_at', now()->timestamp);

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah keluar.');
    }
}
