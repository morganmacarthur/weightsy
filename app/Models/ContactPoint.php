<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BelongsToRelation;
use Illuminate\Database\Eloquent\Relations\HasMany as HasManyRelation;

#[Fillable([
    'user_id',
    'channel',
    'address',
    'normalized_address',
    'receives_reminders',
    'verified_at',
    'last_inbound_at',
    'last_outbound_at',
])]
class ContactPoint extends Model
{
    protected function casts(): array
    {
        return [
            'receives_reminders' => 'boolean',
            'verified_at' => 'datetime',
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
        ];
    }

    #[BelongsTo(User::class)]
    public function user(): BelongsToRelation
    {
        return $this->belongsTo(User::class);
    }

    #[HasMany(Checkin::class)]
    public function checkins(): HasManyRelation
    {
        return $this->hasMany(Checkin::class);
    }

    #[HasMany(Message::class)]
    public function messages(): HasManyRelation
    {
        return $this->hasMany(Message::class);
    }

    #[HasMany(ReminderSchedule::class)]
    public function reminderSchedules(): HasManyRelation
    {
        return $this->hasMany(ReminderSchedule::class);
    }
}
