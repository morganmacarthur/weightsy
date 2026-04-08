<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\HasMany;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany as HasManyRelation;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'display_name',
    'email',
    'email_verified_at',
    'password',
    'timezone',
    'reminder_time_local',
    'last_checkin_at',
    'onboarding_completed_at',
    'notification_confirmed_at',
    'unsubscribed_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_checkin_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'notification_confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    #[HasMany(ContactPoint::class)]
    public function contactPoints(): HasManyRelation
    {
        return $this->hasMany(ContactPoint::class);
    }

    #[HasMany(Checkin::class)]
    public function checkins(): HasManyRelation
    {
        return $this->hasMany(Checkin::class);
    }

    #[HasMany(LoginToken::class)]
    public function loginTokens(): HasManyRelation
    {
        return $this->hasMany(LoginToken::class);
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
