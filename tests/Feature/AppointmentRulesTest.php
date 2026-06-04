<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AppointmentRulesTest extends TestCase
{
    private string $basePrincipal;
    private string $baseSecundaria;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Se necesita pdo_sqlite para probar registro de citas con bases temporales.');
        }

        $this->basePrincipal = storage_path('framework/testing-appointments-primary.sqlite');
        $this->baseSecundaria = storage_path('framework/testing-appointments-secondary.sqlite');

        foreach ([$this->basePrincipal, $this->baseSecundaria] as $ruta) {
            if (file_exists($ruta)) {
                unlink($ruta);
            }

            touch($ruta);
        }

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => [
                'driver' => 'sqlite',
                'database' => $this->basePrincipal,
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'database.connections.mysql_secondary' => [
                'driver' => 'sqlite',
                'database' => $this->baseSecundaria,
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        DB::purge('mysql');
        DB::purge('mysql_secondary');

        $this->crearTablas('mysql');
        $this->crearTablas('mysql_secondary');
    }

    protected function tearDown(): void
    {
        DB::purge('mysql');
        DB::purge('mysql_secondary');

        foreach ([$this->basePrincipal ?? null, $this->baseSecundaria ?? null] as $ruta) {
            if ($ruta && file_exists($ruta)) {
                unlink($ruta);
            }
        }

        parent::tearDown();
    }

    public function test_la_cita_rapida_puede_usar_un_horario_ocupado(): void
    {
        $this->actingAs($this->crearUsuario());
        $this->crearCorte();

        DB::connection('mysql')->table('appointments')->insert($this->citaProgramada([
            'shared_id' => 'appt_ocupada',
            'appointment_date' => now()->toDateString(),
            'start_time' => '10:00',
            'duration_minutes' => 60,
        ]));

        $respuesta = $this->post(route('citas.store'), [
            'final_price' => 150,
            'appointment_date' => now()->toDateString(),
            'start_time' => '10:00',
            'haircut_style_shared_id' => 'cut_demo',
            'status' => 'completed',
        ]);

        $respuesta->assertRedirect(route('agenda'));

        $this->assertDatabaseHas('appointments', [
            'appointment_type' => 'quick',
            'start_time' => '10:00',
            'duration_minutes' => null,
        ], 'mysql');
    }

    public function test_la_cita_con_duracion_no_puede_usar_un_horario_ocupado(): void
    {
        $this->actingAs($this->crearUsuario());
        $this->crearCorte();

        DB::connection('mysql')->table('appointments')->insert($this->citaProgramada([
            'shared_id' => 'appt_ocupada',
            'appointment_date' => now()->toDateString(),
            'start_time' => '10:00',
            'duration_minutes' => 60,
        ]));

        $respuesta = $this->from(route('registrar-servicio'))->post(route('citas.store'), [
            'final_price' => 150,
            'duration_minutes' => 30,
            'appointment_date' => now()->toDateString(),
            'start_time' => '10:30',
            'haircut_style_shared_id' => 'cut_demo',
            'status' => 'pending',
        ]);

        $respuesta->assertRedirect(route('registrar-servicio'));
        $respuesta->assertSessionHasErrors('start_time');
    }

    private function crearUsuario(): User
    {
        return User::query()->create([
            'shared_id' => 'user_demo',
            'name' => 'Barbero Demo',
            'phone' => '7151234567',
            'password' => 'password',
            'role' => 'barber',
        ]);
    }

    private function crearCorte(): void
    {
        DB::connection('mysql')->table('haircut_styles')->insert([
            'shared_id' => 'cut_demo',
            'name' => 'Corte demo',
            'description' => null,
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function citaProgramada(array $datos): array
    {
        return array_merge([
            'user_shared_id' => 'user_demo',
            'client_shared_id' => null,
            'haircut_style_shared_id' => 'cut_demo',
            'appointment_type' => 'scheduled',
            'final_price' => 150,
            'status' => 'pending',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $datos);
    }

    private function crearTablas(string $conexion): void
    {
        Schema::connection($conexion)->create('users', function ($table) {
            $table->id();
            $table->string('shared_id')->unique();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('password');
            $table->string('role')->default('barber');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::connection($conexion)->create('clients', function ($table) {
            $table->id();
            $table->string('shared_id')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::connection($conexion)->create('haircut_styles', function ($table) {
            $table->id();
            $table->string('shared_id')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });

        Schema::connection($conexion)->create('appointments', function ($table) {
            $table->id();
            $table->string('shared_id')->unique();
            $table->string('user_shared_id')->nullable();
            $table->string('client_shared_id')->nullable();
            $table->string('haircut_style_shared_id')->nullable();
            $table->string('appointment_type', 30)->default('quick');
            $table->date('appointment_date');
            $table->time('start_time');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->decimal('final_price', 10, 2)->default(0);
            $table->string('status', 30)->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::connection($conexion)->create('haircut_previews', function ($table) {
            $table->id();
            $table->string('shared_id')->unique();
            $table->string('appointment_shared_id')->unique();
            $table->string('original_image_url');
            $table->string('generated_image_url');
            $table->text('prompt')->nullable();
            $table->string('status', 30)->default('completed');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::connection($conexion)->create('pending_syncs', function ($table) {
            $table->id();
            $table->string('shared_id')->unique();
            $table->string('target_connection');
            $table->string('table_name');
            $table->string('operation');
            $table->string('record_shared_id')->nullable()->index();
            $table->string('status', 30)->default('pending');
            $table->timestamps();
        });
    }
}
