<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = DB::table('users')->first();
        $cortes = DB::table('haircut_styles')->get();

        $clientes = [
            ['Juan Pérez', '7151001001'],
            ['Carlos López', '7151001002'],
            ['Miguel Torres', '7151001003'],
            ['Luis García', '7151001004'],
            ['Jorge Martínez', '7151001005'],
        ];

        $clientesGuardados = [];

        foreach ($clientes as $cliente) {
            $sharedId = (string) Str::uuid();
            DB::table('clients')->insert([
                'shared_id' => $sharedId,
                'name' => $cliente[0],
                'phone' => $cliente[1],
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $clientesGuardados[] = $sharedId;
        }

        for ($i = 0; $i < 80; $i++) {
            $fecha = now()->subDays(rand(0, 25))->toDateString();
            $hora = str_pad((string) rand(9, 16), 2, '0', STR_PAD_LEFT) . ':00';
            $esRapida = rand(1, 100) <= 40;
            $cliente = $esRapida ? null : $clientesGuardados[array_rand($clientesGuardados)];
            $corte = $cortes->isNotEmpty() ? $cortes->random()->shared_id : null;

            DB::table('appointments')->insert([
                'shared_id' => (string) Str::uuid(),
                'user_shared_id' => $usuario?->shared_id,
                'client_shared_id' => $cliente,
                'haircut_style_shared_id' => $corte,
                'appointment_type' => $esRapida ? 'quick' : 'scheduled',
                'appointment_date' => $fecha,
                'start_time' => $hora,
                'duration_minutes' => rand(20, 60),
                'final_price' => rand(100, 250),
                'status' => 'completed',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
