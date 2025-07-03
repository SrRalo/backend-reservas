<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_connection()
    {
        $result = DB::select('SELECT 1 as test');
        $this->assertEquals(1, $result[0]->test);
    }
    
    public function test_migrations_run()
    {
        // Verificar que las tablas principales existen
        $this->assertTrue(\Schema::hasTable('usuario_reserva'));
        $this->assertTrue(\Schema::hasTable('estacionamientoadmin'));
        $this->assertTrue(\Schema::hasTable('vehiculos'));
        $this->assertTrue(\Schema::hasTable('tickets'));
        $this->assertTrue(\Schema::hasTable('pagos'));
        $this->assertTrue(\Schema::hasTable('penalizaciones'));
    }
}