<?php

namespace App\Console\Commands;

use App\Services\SyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sync:databases')]
#[Description('Sincroniza registros pendientes entre las dos bases de datos')]
class SyncDatabasesCommand extends Command
{
    public function handle(SyncService $service): int
    {
        $total = $service->sincronizarPendientes();

        $this->info("Registros sincronizados: {$total}");

        return self::SUCCESS;
    }
}