<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Repositories\Interfaces\UsuarioReservaRepositoryInterface;
use App\Repositories\Interfaces\TicketRepositoryInterface;
use App\Repositories\Interfaces\VehiculoRepositoryInterface;

class SimpleRelationshipsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSimpleTables();
    }

    private function createSimpleTables()
    {
        if (!Schema::hasTable('usuario_reserva')) {
            Schema::create('usuario_reserva', function ($table) {
                $table->id();
                $table->string('nombre');
                $table->string('email')->unique();
                $table->string('documento')->unique();
                $table->enum('estado', ['activo', 'inactivo'])->default('activo');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('vehiculos')) {
            Schema::create('vehiculos', function ($table) {
                $table->id();
                $table->foreignId('usuario_id');
                $table->string('placa')->unique();
                $table->string('tipo');
                $table->string('marca');
                $table->string('modelo');
                $table->string('color');
                $table->enum('estado', ['activo', 'inactivo'])->default('activo');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function ($table) {
                $table->id();
                $table->foreignId('usuario_id');
                $table->foreignId('vehiculo_id');
                $table->foreignId('estacionamiento_id');
                $table->string('codigo_ticket')->unique();
                $table->datetime('fecha_entrada');
                $table->datetime('fecha_salida')->nullable();
                $table->decimal('monto', 8, 2)->nullable();
                $table->enum('estado', ['activo', 'pagado', 'cancelado'])->default('activo');
                $table->enum('tipo_reserva', ['por_horas', 'mensual']);
                $table->timestamps();
            });
        }

        DB::table('usuario_reserva')->truncate();
        DB::table('vehiculos')->truncate();
        DB::table('tickets')->truncate();
    }

    public function test_basic_usuario_vehiculo_relationship()
    {
        $usuarioRepo = app(UsuarioReservaRepositoryInterface::class);
        $vehiculoRepo = app(VehiculoRepositoryInterface::class);

        // Crear usuario
        $usuario = $usuarioRepo->create([
            'nombre' => 'Usuario Simple',
            'email' => 'simple@test.com',
            'documento' => 'SIM123456',
        ]);

        // Crear vehículo
        $vehiculo = $vehiculoRepo->create([
            'usuario_id' => $usuario->id,
            'placa' => 'SIM001',
            'tipo' => 'automovil',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Rojo'
        ]);

        // Verificar relación
        $vehiculosDelUsuario = $vehiculoRepo->findByUsuario($usuario->id);
        
        $this->assertCount(1, $vehiculosDelUsuario);
        $this->assertEquals('SIM001', $vehiculosDelUsuario->first()->placa);
        $this->assertEquals($usuario->id, $vehiculo->usuario_id);
    }

    public function test_basic_usuario_ticket_relationship()
    {
        $usuarioRepo = app(UsuarioReservaRepositoryInterface::class);
        $ticketRepo = app(TicketRepositoryInterface::class);

        // Crear usuario
        $usuario = $usuarioRepo->create([
            'nombre' => 'Usuario Tickets',
            'email' => 'tickets@simple.com',
            'documento' => 'TKT789456',
        ]);

        // Crear ticket
        $ticket = $ticketRepo->create([
            'usuario_id' => $usuario->id,
            'vehiculo_id' => 1,
            'estacionamiento_id' => 1,
            'codigo_ticket' => 'SIMPLE-001',
            'fecha_entrada' => now(),
            'tipo_reserva' => 'por_horas',
        ]);

        // Verificar relación
        $ticketsDelUsuario = $ticketRepo->findByUsuario($usuario->id);
        
        $this->assertCount(1, $ticketsDelUsuario);
        $this->assertEquals('SIMPLE-001', $ticketsDelUsuario->first()->codigo_ticket);
        $this->assertEquals($usuario->id, $ticket->usuario_id);
    }

    public function test_repository_methods_work()
    {
        $usuarioRepo = app(UsuarioReservaRepositoryInterface::class);
        $vehiculoRepo = app(VehiculoRepositoryInterface::class);
        $ticketRepo = app(TicketRepositoryInterface::class);

        // Verificar que los repositorios se resuelven
        $this->assertNotNull($usuarioRepo);
        $this->assertNotNull($vehiculoRepo);
        $this->assertNotNull($ticketRepo);

        // Verificar métodos base
        $this->assertTrue(method_exists($usuarioRepo, 'create'));
        $this->assertTrue(method_exists($vehiculoRepo, 'findByUsuario'));
        $this->assertTrue(method_exists($ticketRepo, 'findByUsuario'));
    }
}