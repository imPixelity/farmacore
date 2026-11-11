<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSession
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $lastActivityAt = $request->session()->get('last_activity_at');

        if (! $user->is_active || ($lastActivityAt !== null && now()->timestamp - (int) $lastActivityAt >= 1800)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Sesi berakhir. Silakan masuk kembali.');
        }

        $request->session()->put('last_activity_at', now()->timestamp);

        return $next($request);
    }
}
