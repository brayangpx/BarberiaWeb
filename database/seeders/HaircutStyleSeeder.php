<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HaircutStyleSeeder extends Seeder
{
    public function run(): void
    {
        $cortes = [
            ['name' => 'Corte clásico', 'description' => 'Corte tradicional limpio y ordenado.'],
            ['name' => 'Fade', 'description' => 'Degradado moderno en laterales.'],
            ['name' => 'Taper fade', 'description' => 'Degradado suave en patillas y nuca.'],
            ['name' => 'Corte con barba', 'description' => 'Servicio de corte acompañado de arreglo de barba.'],
            ['name' => 'Texturizado', 'description' => 'Corte con acabado moderno y volumen.'],
        ];

        foreach ($cortes as $corte) {
            DB::table('haircut_styles')->updateOrInsert(
                ['name' => $corte['name']],
                [
                    'shared_id' => (string) Str::uuid(),
                    'description' => $corte['description'],
                    'image_url' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
