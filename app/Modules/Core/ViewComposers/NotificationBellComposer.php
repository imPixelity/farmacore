<?php

namespace App\Modules\Core\ViewComposers;

use App\Models\User;
use App\Modules\Core\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationBellComposer
{
    public function __construct(private NotificationService $notifications) {}

    public function compose(View $view): void
    {
        $user = Auth::user();

        $view->with('notificationSummary', $user instanceof User
            ? $this->notifications->summaryFor($user)
            : ['items' => collect(), 'unreadCount' => 0]);
    }
}
