<?php

namespace Tests\Unit;

use App\Services\SharedIdService;
use PHPUnit\Framework\TestCase;

class SharedIdServiceTest extends TestCase
{
    public function test_crea_un_identificador_con_prefijo(): void
    {
        $servicio = new SharedIdService();

        $sharedId = $servicio->crear('client');

        $this->assertStringStartsWith('client-', $sharedId);
    }
}
