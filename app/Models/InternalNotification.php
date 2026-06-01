<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shared_id',
    'appointment_shared_id',
    'title',
    'message',
    'generated_at',
])]
class InternalNotification extends Model
{
    public function cita(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_shared_id', 'shared_id');
    }

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }
}
