<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RefreshToken extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'token',
        'user_id',
        'access_token_id',
        'revoked',
        'expires_at',
        'scopes',
        'client_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'revoked' => 'boolean',
        'scopes' => 'json',
    ];

    /**
     * Get the user that owns the refresh token.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the token is valid (not revoked and not expired).
     */
    public function isValid(): bool
    {
        return !$this->revoked && !$this->isExpired();
    }

    /**
     * Revoke the token.
     */
    public function revoke(): bool
    {
        $this->revoked = true;
        return $this->save();
    }
}
