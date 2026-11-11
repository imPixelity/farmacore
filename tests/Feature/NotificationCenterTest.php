<?php

use App\Models\User;
use App\Modules\Core\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists notifications and deduplicates by recipient type entity and date', function () {
    $this->travelTo(now()->startOfDay());

    $recipient = User::factory()->create();
    $otherRecipient = User::factory()->create();
    $notifications = app(NotificationService::class);

    $first = $notifications->send($recipient, 'BatchNearExpiry', 'batch:42', ['message' => 'Batch hampir kedaluwarsa']);
    $duplicate = $notifications->send($recipient, 'BatchNearExpiry', 'batch:42', ['message' => 'Pesan duplikat']);
    $otherType = $notifications->send($recipient, 'PayableDueSoon', 'batch:42', ['message' => 'Utang segera jatuh tempo']);
    $otherEntity = $notifications->send($recipient, 'BatchNearExpiry', 'batch:43', ['message' => 'Batch lain']);
    $otherUser = $notifications->send($otherRecipient, 'BatchNearExpiry', 'batch:42', ['message' => 'Untuk pengguna lain']);

    expect($duplicate->id)->toBe($first->id);
    expect($recipient->notifications()->count())->toBe(3);
    expect($otherUser->notifiable_id)->toBe($otherRecipient->id);
    expect($first->data)->toHaveKey('message', 'Batch hampir kedaluwarsa');

    $this->travel(1)->days();
    $nextDay = $notifications->send($recipient, 'BatchNearExpiry', 'batch:42', ['message' => 'Peringatan harian berikutnya']);

    expect($nextDay->id)->not->toBe($first->id);
    expect($recipient->notifications()->count())->toBe(4);
});

it('marks only the authenticated user notification as read', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $notification = app(NotificationService::class)->send(
        $owner,
        'BatchNearExpiry',
        'batch:17',
        ['message' => 'Batch perlu diperiksa'],
    );

    $this->actingAs($owner)
        ->patch(route('notifications.read', $notification->id))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();

    $this->actingAs($otherUser)
        ->patch(route('notifications.read', $notification->id))
        ->assertNotFound();
});

it('shows the dashboard summary and escapes notification content', function () {
    $user = User::factory()->create();
    app(NotificationService::class)->send($user, 'SystemAlert', 'alert:1', [
        'message' => '<script>alert("x")</script> Periksa batch',
    ]);
    app(NotificationService::class)->send($user, 'SystemAlert', 'alert:2', [
        'message' => 'Pemberitahuan kedua',
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('2 belum dibaca')
        ->assertSee('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', escape: false)
        ->assertDontSee('<script>alert("x")</script>', escape: false)
        ->assertSee('Pemberitahuan kedua');
});
