<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['shared_id', 'name', 'phone', 'notes'])]
class Client extends Model
{
    public function citas(): HasMany
    {
        return $this->hasMany(Appointment::class, 'client_shared_id', 'shared_id');
    }
}
