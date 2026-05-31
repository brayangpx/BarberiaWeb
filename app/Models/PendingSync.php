<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'shared_id',
    'target_connection',
    'table_name',
    'operation',
    'record_shared_id',
    'payload',
    'status',
    'attempts',
    'error_message',
])]
class PendingSync extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
