<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BelongsToRelation;

#[Fillable([
    'user_id',
    'channel',
    'sent_to',
    'purpose',
    'token_lookup',
    'token_hash',
    'expires_at',
    'used_at',
])]
class LoginToken extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    #[BelongsTo(User::class)]
    public function user(): BelongsToRelation
    {
        return $this->belongsTo(User::class);
    }
}
