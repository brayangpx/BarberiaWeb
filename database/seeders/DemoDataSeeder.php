<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\HaircutStyle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = User::query()->first();
        $cortes = HaircutStyle::query()->get();

        if (!$usuario || $cortes->isEmpty()) {
            return;
        }

        $clientesGuardados = [];

        for ($i = 1; $i <= 120; $i++) {
            $sharedId = (string) Str::uuid();

            Client::query()->create([
                'shared_id' => $sharedId,
                'name' => 'Cliente Demo ' . $i,
                'phone' => '71510' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $clientesGuardados[] = $sharedId;
        }

        $estados = ['completed', 'completed', 'completed', 'confirmed', 'pending', 'cancelled'];
        $horas = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'];

        for ($i = 1; $i <= 3000; $i++) {
            $esRapida = rand(1, 100) <= 40;
            $estado = $estados[array_rand($estados)];

            $fecha = now()
                ->subDays(rand(0, 120))
                ->toDateString();

            if ($estado === 'pending' || $estado === 'confirmed') {
                $fecha = now()
                    ->addDays(rand(0, 20))
                    ->toDateString();
            }

            Appointment::query()->create([
                'shared_id' => (string) Str::uuid(),
                'user_shared_id' => $usuario->shared_id,
                'client_shared_id' => $esRapida ? null : $clientesGuardados[array_rand($clientesGuardados)],
                'haircut_style_shared_id' => $cortes->random()->shared_id,
                'appointment_type' => $esRapida ? 'quick' : 'scheduled',
                'appointment_date' => $fecha,
                'start_time' => $horas[array_rand($horas)],
                'duration_minutes' => rand(20, 60),
                'final_price' => rand(100, 300),
                'status' => $estado,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
