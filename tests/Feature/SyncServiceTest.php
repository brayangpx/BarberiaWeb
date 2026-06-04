<?php

namespace Tests\Feature;

use App\Services\SyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SyncServiceTest extends TestCase
{
    private string $basePrincipal;
    private string $baseSecundaria;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Se necesita pdo_sqlite para probar sincronizacion con bases temporales.');
        }

        $this->basePrincipal = storage_path('framework/testing-sync-primary.sqlite');
        $this->baseSecundaria = storage_path('framework/testing-sync-secondary.sqlite');

        foreach ([$this->basePrincipal, $this->baseSecundaria] as $ruta) {
            if (file_exists($ruta)) {
                unlink($ruta);
            }

            touch($ruta);
        }

        config([
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

    public function test_sincroniza_el_estado_actual_del_registro_pendiente(): void
    {
        DB::connection('mysql')->table('clients')->insert([
            'shared_id' => 'client_123',
            'name' => 'Cliente original',
            'phone' => '1111111111',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('mysql')->table('pending_syncs')->insert([
            'shared_id' => 'sync_123',
            'target_connection' => 'mysql_secondary',
            'table_name' => 'clients',
            'operation' => 'insert',
            'record_shared_id' => 'client_123',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sincronizados = app(SyncService::class)->sincronizarPendientes();

        $this->assertSame(1, $sincronizados);

        $this->assertDatabaseHas('clients', [
            'shared_id' => 'client_123',
            'name' => 'Cliente original',
        ], 'mysql_secondary');

        $this->assertDatabaseHas('pending_syncs', [
            'shared_id' => 'sync_123',
            'status' => 'synced',
        ], 'mysql');
    }

    public function test_si_el_registro_cambio_antes_de_sincronizar_copia_el_ultimo_estado(): void
    {
        DB::connection('mysql')->table('clients')->insert([
            'shared_id' => 'client_456',
            'name' => 'Nombre final',
            'phone' => '2222222222',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('mysql_secondary')->table('clients')->insert([
            'shared_id' => 'client_456',
            'name' => 'Nombre anterior',
            'phone' => '2222222222',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('mysql')->table('pending_syncs')->insert([
            'shared_id' => 'sync_456',
            'target_connection' => 'mysql_secondary',
            'table_name' => 'clients',
            'operation' => 'update',
            'record_shared_id' => 'client_456',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sincronizados = app(SyncService::class)->sincronizarPendientes();

        $this->assertSame(1, $sincronizados);

        $this->assertDatabaseHas('clients', [
            'shared_id' => 'client_456',
            'name' => 'Nombre final',
        ], 'mysql_secondary');
    }

    private function crearTablas(string $conexion): void
    {
        Schema::connection($conexion)->create('clients', function ($table) {
            $table->id();
            $table->string('shared_id')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
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
