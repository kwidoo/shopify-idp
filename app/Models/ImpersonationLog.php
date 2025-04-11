<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpersonationLog extends Model
{
    protected $fillable = [
        'impersonator_id',
        'user_id',
        'token_id',
        'expires_at',
    ];
}
