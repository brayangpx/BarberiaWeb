<?php

namespace Tests\Feature;

use App\Http\Middleware\UseActiveDatabaseConnection;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\HaircutStyle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_deja_registrar_cita_sin_precio(): void
    {
        $this->withoutMiddleware(UseActiveDatabaseConnection::class);

        $user = User::factory()->create();
        $corte = HaircutStyle::create([
            'shared_id' => 'cut-test',
            'name' => 'Corte prueba',
            'description' => 'Corte para prueba',
            'image_url' => null,
        ]);

        $response = $this->actingAs($user)->post('/citas', [
            'haircut_style_shared_id' => $corte->shared_id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '12:00',
            'duration_minutes' => 30,
        ]);

        $response->assertSessionHasErrors('final_price');
    }

    public function test_no_deja_registrar_cliente_con_telefono_repetido(): void
    {
        $this->withoutMiddleware(UseActiveDatabaseConnection::class);

        $user = User::factory()->create();

        Client::create([
            'shared_id' => 'client-test',
            'name' => 'Cliente uno',
            'phone' => '7151234567',
            'notes' => null,
        ]);

        $response = $this->actingAs($user)->post('/clientes', [
            'name' => 'Cliente dos',
            'phone' => '7151234567',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_no_deja_registrar_cita_en_horario_ocupado(): void
    {
        $this->withoutMiddleware(UseActiveDatabaseConnection::class);

        $user = User::factory()->create();
        $corte = HaircutStyle::create([
            'shared_id' => 'cut-test',
            'name' => 'Corte prueba',
            'description' => 'Corte para prueba',
            'image_url' => null,
        ]);

        Appointment::create([
            'shared_id' => 'appt-test',
            'user_shared_id' => $user->shared_id,
            'client_shared_id' => null,
            'haircut_style_shared_id' => $corte->shared_id,
            'appointment_type' => 'scheduled',
            'appointment_date' => now()->toDateString(),
            'start_time' => '12:00',
            'duration_minutes' => 30,
            'final_price' => 120,
            'status' => 'pending',
            'notes' => null,
        ]);

        $response = $this->actingAs($user)->post('/citas', [
            'haircut_style_shared_id' => $corte->shared_id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '12:00',
            'duration_minutes' => 30,
            'final_price' => 150,
        ]);

        $response->assertSessionHasErrors('start_time');
    }
}
