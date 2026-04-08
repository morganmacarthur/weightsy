<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BelongsToRelation;

#[Fillable([
    'user_id',
    'contact_point_id',
    'direction',
    'channel',
    'provider',
    'external_id',
    'in_reply_to',
    'subject',
    'body_text',
    'parsed_status',
    'received_at',
    'sent_at',
    'processed_at',
    'metadata',
])]
class Message extends Model
{
    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'sent_at' => 'datetime',
            'processed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    #[BelongsTo(User::class)]
    public function user(): BelongsToRelation
    {
        return $this->belongsTo(User::class);
    }

    #[BelongsTo(ContactPoint::class)]
    public function contactPoint(): BelongsToRelation
    {
        return $this->belongsTo(ContactPoint::class);
    }
}
