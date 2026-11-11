<?php

namespace App\Http\Middleware;

use App\Modules\Core\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSession
{
    public function __construct(private SettingsService $settings) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $lastActivityAt = $request->session()->get('last_activity_at');

        $sessionTimeoutMinutes = (int) $this->settings->get('session_timeout_minutes', 30);

        if (! $user->is_active || ($lastActivityAt !== null && now()->timestamp - (int) $lastActivityAt >= $sessionTimeoutMinutes * 60)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Sesi berakhir. Silakan masuk kembali.');
        }

        $request->session()->put('last_activity_at', now()->timestamp);

        return $next($request);
    }
}
