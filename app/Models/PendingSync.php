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
    'status',
])]
class PendingSync extends Model
{
}
