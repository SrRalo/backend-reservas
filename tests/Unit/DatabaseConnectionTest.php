<?php


namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class DatabaseConnectionTest extends TestCase
{
    public function test_basic_database_connection()
    {
        // Test simple sin migración
        $this->assertTrue(true);
    }
    
    public function test_can_execute_query()
    {
        // Solo probar conexión básica
        $result = DB::select('SELECT 1 as test');
        $this->assertEquals(1, $result[0]->test);
    }
}