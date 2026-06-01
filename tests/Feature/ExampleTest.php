<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_pagina_principal_redirige_al_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_la_pantalla_de_login_carga_correctamente(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}