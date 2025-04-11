<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    // Add to fillable
    protected $fillable = [
        'name',
        'email',
        'password',
        'shopify_id',
        'shopify_metadata',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'shopify_metadata' => 'json',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the impersonation logs for this user.
     */
    public function impersonationLogs()
    {
        return $this->hasMany(ImpersonationLog::class, 'impersonated_user_id');
    }

    /**
     * Get the impersonation logs initiated by this user.
     */
    public function initiatedImpersonations()
    {
        return $this->hasMany(ImpersonationLog::class, 'impersonator_id');
    }

    /**
     * Get the refresh tokens for this user.
     */
    public function refreshTokens()
    {
        return $this->hasMany(RefreshToken::class);
    }
}
