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

        $diasConPeso = [
            1 => 7,
            2 => 8,
            3 => 10,
            4 => 14,
            5 => 26,
            6 => 28,
            7 => 7,
        ];

        $horasConPeso = [
            '09:00' => 6,
            '10:00' => 8,
            '11:00' => 10,
            '12:00' => 12,
            '13:00' => 7,
            '14:00' => 12,
            '15:00' => 22,
            '16:00' => 23,
        ];

        $horasPicoPorDia = [
            5 => ['15:00', '16:00'],
            6 => ['11:00', '12:00', '15:00', '16:00'],
            4 => ['14:00', '15:00'],
        ];

        for ($i = 1; $i <= 3000; $i++) {
            $esRapida = rand(1, 100) <= 40;
            $estado = $estados[array_rand($estados)];
            $numeroDia = $this->valorPonderado($diasConPeso);
            $hora = $this->valorPonderado($horasConPeso);

            if (isset($horasPicoPorDia[$numeroDia]) && rand(1, 100) <= 70) {
                $hora = $horasPicoPorDia[$numeroDia][array_rand($horasPicoPorDia[$numeroDia])];
            }

            $fecha = $this->fechaConDiaSemana($numeroDia, false);

            if ($estado === 'pending' || $estado === 'confirmed') {
                $fecha = $this->fechaConDiaSemana($numeroDia, true);
            }

            Appointment::query()->create([
                'shared_id' => (string) Str::uuid(),
                'user_shared_id' => $usuario->shared_id,
                'client_shared_id' => $esRapida ? null : $clientesGuardados[array_rand($clientesGuardados)],
                'haircut_style_shared_id' => $cortes->random()->shared_id,
                'appointment_type' => $esRapida ? 'quick' : 'scheduled',
                'appointment_date' => $fecha,
                'start_time' => $hora,
                'duration_minutes' => rand(20, 60),
                'final_price' => rand(100, 300),
                'status' => $estado,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function valorPonderado(array $opciones): int|string
    {
        $total = array_sum($opciones);
        $aleatorio = rand(1, $total);

        foreach ($opciones as $valor => $peso) {
            $aleatorio -= $peso;

            if ($aleatorio <= 0) {
                return is_numeric($valor) ? (int) $valor : $valor;
            }
        }

        return array_key_first($opciones);
    }

    private function fechaConDiaSemana(int $numeroDia, bool $futura): string
    {
        $fecha = $futura
            ? now()->addDays(rand(1, 28))
            : now()->subDays(rand(1, 120));

        while ((int) $fecha->format('N') !== $numeroDia) {
            $fecha = $futura ? $fecha->addDay() : $fecha->subDay();
        }

        return $fecha->toDateString();
    }
}
