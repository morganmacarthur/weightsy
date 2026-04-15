<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BelongsToRelation;

#[Fillable([
    'user_id',
    'contact_point_id',
    'metric_type',
    'value_decimal',
    'systolic',
    'diastolic',
    'occurred_on',
    'received_at',
    'source_type',
    'raw_input',
    'notes',
])]
class Checkin extends Model
{
    protected function casts(): array
    {
        return [
            'value_decimal' => 'decimal:2',
            'occurred_on' => 'date',
            'received_at' => 'datetime',
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

    public function displayValue(): string
    {
        return match ($this->metric_type) {
            'blood_pressure' => trim(sprintf('%s/%s', $this->systolic, $this->diastolic), '/'),
            'body_fat' => rtrim(rtrim((string) $this->value_decimal, '0'), '.').'%',
            default => rtrim(rtrim((string) $this->value_decimal, '0'), '.'),
        };
    }

    public function editableInput(): string
    {
        return $this->displayValue();
    }
}
