<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = User::query()
            ->where('phone', '7151234567')
            ->first();

        if ($usuario) {
            $usuario->update([
                'name' => 'Barbero principal',
                'password' => '1234',
                'role' => 'barber',
            ]);

            return;
        }

        User::query()->create([
            'shared_id' => (string) Str::uuid(),
            'name' => 'Barbero principal',
            'phone' => '7151234567',
            'password' => 'password',
            'role' => 'barber',
        ]);
    }
}
