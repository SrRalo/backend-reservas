<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Repositories\Interfaces\UsuarioReservaRepositoryInterface;
use App\Repositories\Interfaces\TicketRepositoryInterface;
use App\Repositories\Interfaces\VehiculoRepositoryInterface;

class ModelRelationshipsSimpleTest extends TestCase
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

    public function test_multiple_usuarios_multiple_vehiculos()
    {
        $usuarioRepo = app(UsuarioReservaRepositoryInterface::class);
        $vehiculoRepo = app(VehiculoRepositoryInterface::class);

        // Crear múltiples usuarios
        $usuario1 = $usuarioRepo->create([
            'nombre' => 'Usuario Uno',
            'email' => 'uno@test.com',
            'documento' => 'UNO123456',
        ]);

        $usuario2 = $usuarioRepo->create([
            'nombre' => 'Usuario Dos',
            'email' => 'dos@test.com',
            'documento' => 'DOS123456',
        ]);

        // Crear vehículos para cada usuario
        $vehiculo1 = $vehiculoRepo->create([
            'usuario_id' => $usuario1->id,
            'placa' => 'UNO001',
            'tipo' => 'automovil',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Rojo'
        ]);

        $vehiculo2 = $vehiculoRepo->create([
            'usuario_id' => $usuario1->id,
            'placa' => 'UNO002',
            'tipo' => 'motocicleta',
            'marca' => 'Honda',
            'modelo' => 'CBR',
            'color' => 'Negro'
        ]);

        $vehiculo3 = $vehiculoRepo->create([
            'usuario_id' => $usuario2->id,
            'placa' => 'DOS001',
            'tipo' => 'automovil',
            'marca' => 'Ford',
            'modelo' => 'Focus',
            'color' => 'Azul'
        ]);

        // Verificar relaciones
        $vehiculosUsuario1 = $vehiculoRepo->findByUsuario($usuario1->id);
        $vehiculosUsuario2 = $vehiculoRepo->findByUsuario($usuario2->id);

        $this->assertCount(2, $vehiculosUsuario1);
        $this->assertCount(1, $vehiculosUsuario2);

        $this->assertTrue($vehiculosUsuario1->contains('placa', 'UNO001'));
        $this->assertTrue($vehiculosUsuario1->contains('placa', 'UNO002'));
        $this->assertTrue($vehiculosUsuario2->contains('placa', 'DOS001'));
    }

    public function test_usuario_multiple_tickets_workflow()
    {
        $usuarioRepo = app(UsuarioReservaRepositoryInterface::class);
        $vehiculoRepo = app(VehiculoRepositoryInterface::class);
        $ticketRepo = app(TicketRepositoryInterface::class);

        // Crear usuario y vehículo
        $usuario = $usuarioRepo->create([
            'nombre' => 'Usuario Multi Tickets',
            'email' => 'multitickets@test.com',
            'documento' => 'MLT123456',
        ]);

        $vehiculo = $vehiculoRepo->create([
            'usuario_id' => $usuario->id,
            'placa' => 'MLT001',
            'tipo' => 'automovil',
            'marca' => 'Toyota',
            'modelo' => 'Camry',
            'color' => 'Gris'
        ]);

        // Crear múltiples tickets
        $ticket1 = $ticketRepo->create([
            'usuario_id' => $usuario->id,
            'vehiculo_id' => $vehiculo->id,
            'estacionamiento_id' => 1,
            'codigo_ticket' => 'MLT-001',
            'fecha_entrada' => now()->subHours(3),
            'tipo_reserva' => 'por_horas',
        ]);

        $ticket2 = $ticketRepo->create([
            'usuario_id' => $usuario->id,
            'vehiculo_id' => $vehiculo->id,
            'estacionamiento_id' => 1,
            'codigo_ticket' => 'MLT-002',
            'fecha_entrada' => now()->subDay(),
            'fecha_salida' => now()->subHours(2),
            'monto' => 15.00,
            'tipo_reserva' => 'por_horas',
            'estado' => 'pagado'
        ]);

        $ticket3 = $ticketRepo->create([
            'usuario_id' => $usuario->id,
            'vehiculo_id' => $vehiculo->id,
            'estacionamiento_id' => 1,
            'codigo_ticket' => 'MLT-003',
            'fecha_entrada' => now(),
            'tipo_reserva' => 'mensual',
        ]);

        // Verificar relaciones
        $ticketsDelUsuario = $ticketRepo->findByUsuario($usuario->id);
        $ticketsActivos = $ticketRepo->findActiveTickets();

        $this->assertCount(3, $ticketsDelUsuario);
        $this->assertGreaterThanOrEqual(2, $ticketsActivos->count()); // MLT-001 y MLT-003 deberían estar activos

        // Verificar estados específicos
        $this->assertTrue($ticketsDelUsuario->contains('codigo_ticket', 'MLT-001'));
        $this->assertTrue($ticketsDelUsuario->contains('codigo_ticket', 'MLT-002'));
        $this->assertTrue($ticketsDelUsuario->contains('codigo_ticket', 'MLT-003'));

        // Verificar ticket pagado
        $ticketPagado = $ticketsDelUsuario->where('codigo_ticket', 'MLT-002')->first();
        $this->assertEquals('pagado', $ticketPagado->estado);
        $this->assertEquals(15.00, $ticketPagado->monto);
    }

    public function test_repository_integration_complete()
    {
        $usuarioRepo = app(UsuarioReservaRepositoryInterface::class);
        $vehiculoRepo = app(VehiculoRepositoryInterface::class);
        $ticketRepo = app(TicketRepositoryInterface::class);

        // Crear flujo completo sin estacionamiento
        $usuario = $usuarioRepo->create([
            'nombre' => 'Usuario Integración',
            'email' => 'integracion@test.com',
            'documento' => 'INT123456',
        ]);

        $vehiculo = $vehiculoRepo->create([
            'usuario_id' => $usuario->id,
            'placa' => 'INT001',
            'tipo' => 'automovil',
            'marca' => 'Nissan',
            'modelo' => 'Sentra',
            'color' => 'Blanco'
        ]);

        $ticket = $ticketRepo->create([
            'usuario_id' => $usuario->id,
            'vehiculo_id' => $vehiculo->id,
            'estacionamiento_id' => 1,
            'codigo_ticket' => 'INT-COMPLETE-001',
            'fecha_entrada' => now(),
            'tipo_reserva' => 'por_horas',
        ]);

        // Simular finalización de ticket
        $finalized = $ticketRepo->finalizarTicket($ticket->id, 35.50);

        // Verificar todo el flujo
        $this->assertTrue($finalized);
        
        $ticket->refresh();
        $this->assertEquals('pagado', $ticket->estado);
        $this->assertEquals(35.50, $ticket->monto);
        $this->assertNotNull($ticket->fecha_salida);

        // Verificar relaciones completas
        $this->assertEquals($usuario->id, $ticket->usuario_id);
        $this->assertEquals($vehiculo->id, $ticket->vehiculo_id);
        $this->assertEquals($usuario->id, $vehiculo->usuario_id);

        // Verificar búsquedas
        $usuarioEncontrado = $usuarioRepo->findByEmail('integracion@test.com');
        $vehiculoEncontrado = $vehiculoRepo->findByPlaca('INT001');
        $ticketEncontrado = $ticketRepo->findByCodigo('INT-COMPLETE-001');

        $this->assertNotNull($usuarioEncontrado);
        $this->assertNotNull($vehiculoEncontrado);
        $this->assertNotNull($ticketEncontrado);

        $this->assertEquals($usuario->id, $usuarioEncontrado->id);
        $this->assertEquals($vehiculo->id, $vehiculoEncontrado->id);
        $this->assertEquals($ticket->id, $ticketEncontrado->id);
    }
}