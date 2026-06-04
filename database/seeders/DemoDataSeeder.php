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

        $clientesGuardados = $this->crearClientesDemo();

        $this->crearHistorialDemo($usuario, $cortes, $clientesGuardados);

        $this->crearCitasDeHoy($usuario, $cortes, $clientesGuardados);
    }

    private function crearClientesDemo(): array
    {
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

        return $clientesGuardados;
    }

    private function crearHistorialDemo(User $usuario, $cortes, array $clientesGuardados): void
    {
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
            $estado = $this->estadoDemo($estados, $esRapida);
            $numeroDia = $this->valorPonderado($diasConPeso);
            $hora = $this->horaDemo($numeroDia, $horasConPeso, $horasPicoPorDia);
            $fecha = $this->fechaDemo($numeroDia, $estado);

            $this->crearCitaDemo($usuario, $cortes, $clientesGuardados, $fecha, $hora, $estado, $esRapida, null);
        }
    }

    private function estadoDemo(array $estados, bool $esRapida): string
    {
        if ($esRapida) {
            return 'completed';
        }

        return $estados[array_rand($estados)];
    }

    private function horaDemo(int $numeroDia, array $horasConPeso, array $horasPicoPorDia): string
    {
        $hora = $this->valorPonderado($horasConPeso);

        if (isset($horasPicoPorDia[$numeroDia]) && rand(1, 100) <= 70) {
            $hora = $horasPicoPorDia[$numeroDia][array_rand($horasPicoPorDia[$numeroDia])];
        }

        return $hora;
    }

    private function fechaDemo(int $numeroDia, string $estado): string
    {
        if ($estado === 'pending' || $estado === 'confirmed') {
            return $this->fechaConDiaSemana($numeroDia, true);
        }

        return $this->fechaConDiaSemana($numeroDia, false);
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

    private function crearCitasDeHoy(User $usuario, $cortes, array $clientesGuardados): void
    {
        $citasDeHoy = [
            ['09:00', 'completed', false],
            ['10:00', 'completed', true],
            ['11:00', 'confirmed', false],
            ['12:00', 'completed', true],
            ['14:00', 'pending', false],
            ['15:00', 'confirmed', true],
            ['15:30', 'pending', false],
            ['16:00', 'confirmed', true],
            ['16:30', 'pending', false],
        ];

        foreach ($citasDeHoy as [$hora, $estado, $esRapida]) {
            if ($esRapida) {
                $estado = 'completed';
            }

            $this->crearCitaDemo($usuario, $cortes, $clientesGuardados, now()->toDateString(), $hora, $estado, $esRapida, 'Servicio demo de hoy');
        }
    }

    private function crearCitaDemo(User $usuario, $cortes, array $clientesGuardados, string $fecha, string $hora, string $estado, bool $esRapida, ?string $nota): void
    {
        Appointment::query()->create([
            'shared_id' => (string) Str::uuid(),
            'user_shared_id' => $usuario->shared_id,
            'client_shared_id' => $esRapida ? null : $clientesGuardados[array_rand($clientesGuardados)],
            'haircut_style_shared_id' => $cortes->random()->shared_id,
            'appointment_type' => $esRapida ? 'quick' : 'scheduled',
            'appointment_date' => $fecha,
            'start_time' => $hora,
            'duration_minutes' => $esRapida ? null : rand(20, 60),
            'final_price' => rand(100, 300),
            'status' => $estado,
            'notes' => $nota,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
