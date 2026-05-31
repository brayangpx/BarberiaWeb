<?php

namespace App\Services;

use Illuminate\Support\Str;

class SharedIdService
{
    public function crear(string $prefijo): string
    {
        return $prefijo . '-' . (string) Str::uuid();
    }
}
