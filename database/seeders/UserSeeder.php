<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['phone' => '7151234567'],
            [
                'shared_id' => (string) Str::uuid(),
                'name' => 'Barbero principal',
                'password' => 'password',
                'role' => 'barber',
            ]
        );
    }
}
