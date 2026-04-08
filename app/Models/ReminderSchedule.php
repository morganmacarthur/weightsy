<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BelongsToRelation;

#[Fillable([
    'user_id',
    'contact_point_id',
    'status',
    'cadence',
    'timezone',
    'remind_at_local',
    'last_sent_for_date',
    'next_run_at',
])]
class ReminderSchedule extends Model
{
    protected function casts(): array
    {
        return [
            'last_sent_for_date' => 'date',
            'next_run_at' => 'datetime',
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
