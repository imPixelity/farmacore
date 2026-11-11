<?php

namespace App\Modules\Core\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function attempt(string $username, string $password, ?string $ipAddress): ?User
    {
        return DB::transaction(function () use ($username, $password, $ipAddress): ?User {
            $user = User::query()
                ->where('username', $username)
                ->lockForUpdate()
                ->first();

            if ($user === null || ! $user->is_active || ($user->locked_until?->isFuture() ?? false)) {
                $this->recordFailure($username, $ipAddress, $user?->id);

                return null;
            }

            if ($user->locked_until !== null) {
                $user->failed_attempts = 0;
                $user->locked_until = null;
            }

            if (! Hash::check($password, $user->password)) {
                $user->failed_attempts++;

                if ($user->failed_attempts >= 5) {
                    $user->locked_until = now()->addMinutes(15);
                }

                $user->save();
                $this->recordFailure($username, $ipAddress, $user->id);

                return null;
            }

            $user->forceFill([
                'failed_attempts' => 0,
                'locked_until' => null,
                'last_login_at' => now(),
            ])->save();

            return $user;
        });
    }

    private function recordFailure(string $username, ?string $ipAddress, ?int $userId): void
    {
        Log::notice('login_failed', [
            'username' => $username,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
        ]);
    }
}
