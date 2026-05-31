<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'shared_id',
    'user_shared_id',
    'client_shared_id',
    'haircut_style_shared_id',
    'appointment_type',
    'appointment_date',
    'start_time',
    'duration_minutes',
    'final_price',
    'status',
    'notes',
])]
class Appointment extends Model
{
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_shared_id', 'shared_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_shared_id', 'shared_id');
    }

    public function corte(): BelongsTo
    {
        return $this->belongsTo(HaircutStyle::class, 'haircut_style_shared_id', 'shared_id');
    }

    public function previsualizacion(): HasOne
    {
        return $this->hasOne(HaircutPreview::class, 'appointment_shared_id', 'shared_id');
    }

    public function notificacion(): HasOne
    {
        return $this->hasOne(InternalNotification::class, 'appointment_shared_id', 'shared_id');
    }

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'final_price' => 'decimal:2',
        ];
    }
}
