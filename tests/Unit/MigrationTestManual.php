<?php


namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrationTestManual extends TestCase
{
    public function test_can_create_tables_manually()
    {
        // Crear solo la tabla que necesitamos para tests
        Schema::create('usuario_reserva_test', function ($table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('email')->unique();
            $table->string('documento')->unique();
            $table->string('telefono')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamp('ultimo_acceso')->nullable();
            $table->timestamps();
        });

        $this->assertTrue(Schema::hasTable('usuario_reserva_test'));
        
        // Limpiar después del test
        Schema::dropIfExists('usuario_reserva_test');
    }
    
    public function test_can_insert_and_retrieve_data()
    {
        // Crear tabla temporal
        Schema::create('test_usuarios', function ($table) {
            $table->id();
            $table->string('nombre');
            $table->string('email');
            $table->timestamps();
        });

        // Insertar datos
        DB::table('test_usuarios')->insert([
            'nombre' => 'Test User',
            'email' => 'test@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verificar datos
        $user = DB::table('test_usuarios')->where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->nombre);

        // Limpiar
        Schema::dropIfExists('test_usuarios');
    }
}