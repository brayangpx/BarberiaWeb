<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProtectedRoutesTest extends TestCase
{
    public function test_agenda_pide_iniciar_sesion(): void
    {
        $response = $this->get('/agenda');

        $response->assertRedirect(route('login'));
    }

    public function test_clientes_pide_iniciar_sesion(): void
    {
        $response = $this->get('/clientes');

        $response->assertRedirect(route('login'));
    }

    public function test_mapa_de_calor_pide_iniciar_sesion(): void
    {
        $response = $this->get('/mapa-calor');

        $response->assertRedirect(route('login'));
    }

    public function test_registrar_servicio_pide_iniciar_sesion(): void
    {
        $response = $this->get('/registrar-servicio');

        $response->assertRedirect(route('login'));
    }
}
