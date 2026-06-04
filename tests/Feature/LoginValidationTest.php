<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginValidationTest extends TestCase
{
    public function test_el_login_requiere_telefono(): void
    {
        $response = $this->post('/login', [
            'password' => '1234',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_el_login_requiere_password(): void
    {
        $response = $this->post('/login', [
            'phone' => '7151234567',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
