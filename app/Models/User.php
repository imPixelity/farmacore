<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Core\Traits\Auditable;
use App\Modules\Md\Models\PharmacyStaff;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['username', 'name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasRoles, Notifiable;

    /** @return array<int, string> */
    protected function auditIgnoredAttributes(): array
    {
        return ['password', 'remember_token', 'failed_attempts', 'locked_until', 'last_login_at'];
    }

    /**
     * @param  array<int, string>  $oldRoles
     * @param  array<int, string>  $newRoles
     */
    public function recordRoleChange(array $oldRoles, array $newRoles): void
    {
        if ($oldRoles === $newRoles) {
            return;
        }

        $this->writeAudit('updated', ['roles' => $oldRoles], ['roles' => $newRoles]);
    }

    public function pharmacyStaff(): HasOne
    {
        return $this->hasOne(PharmacyStaff::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'failed_attempts' => 'integer',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
