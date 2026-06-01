<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['shared_id', 'name', 'description', 'image_url'])]
class HaircutStyle extends Model
{
    public function citas(): HasMany
    {
        return $this->hasMany(Appointment::class, 'haircut_style_shared_id', 'shared_id');
    }
}
