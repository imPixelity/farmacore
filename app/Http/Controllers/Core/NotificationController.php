<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Modules\Core\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, string $notification, NotificationService $notifications): RedirectResponse
    {
        $notifications->markAsRead($request->user(), $notification);

        return back()->with('status', 'Notifikasi ditandai telah dibaca.');
    }
}
