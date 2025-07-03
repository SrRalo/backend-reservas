<?php


namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Repositories\Interfaces\TicketRepositoryInterface;
use App\Models\Ticket;

class TicketRepositoryTest extends TestCase
{
    private TicketRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(TicketRepositoryInterface::class);
        
        $this->createTestTablesIfNotExist();
        $this->cleanTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanTestData();
        parent::tearDown();
    }

    private function createTestTablesIfNotExist()
    {
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

        // Crear tablas relacionadas
        if (!Schema::hasTable('usuario_reserva')) {
            Schema::create('usuario_reserva', function ($table) {
                $table->id();
                $table->string('nombre');
                $table->string('email')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('vehiculos')) {
            Schema::create('vehiculos', function ($table) {
                $table->id();
                $table->foreignId('usuario_id');
                $table->string('placa')->unique();
                $table->string('tipo');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('estacionamientoadmin')) {
            Schema::create('estacionamientoadmin', function ($table) {
                $table->id();
                $table->string('nombre');
                $table->string('email')->unique();
                $table->timestamps();
            });
        }
    }

    private function cleanTestData()
    {
        $tables = ['tickets', 'vehiculos', 'estacionamientoadmin', 'usuario_reserva'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
    }

    public function test_can_create_ticket()
    {
        // Crear datos relacionados
        $usuarioId = DB::table('usuario_reserva')->insertGetId([
            'nombre' => 'Test User',
            'email' => 'test@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vehiculoId = DB::table('vehiculos')->insertGetId([
            'usuario_id' => $usuarioId,
            'placa' => 'ABC123',
            'tipo' => 'automovil',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estacionamientoId = DB::table('estacionamientoadmin')->insertGetId([
            'nombre' => 'Parking Test',
            'email' => 'parking@test.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = [
            'usuario_id' => $usuarioId,
            'vehiculo_id' => $vehiculoId,
            'estacionamiento_id' => $estacionamientoId,
            'codigo_ticket' => 'TKT-001',
            'fecha_entrada' => now(),
            'tipo_reserva' => 'por_horas',
            'estado' => 'activo'
        ];

        $ticket = $this->repository->create($data);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals('TKT-001', $ticket->codigo_ticket);
        $this->assertEquals('activo', $ticket->estado);
        $this->assertEquals('por_horas', $ticket->tipo_reserva);
    }

    public function test_can_find_active_tickets()
    {
        // Crear datos de prueba
        $usuarioId = DB::table('usuario_reserva')->insertGetId([
            'nombre' => 'Test User',
            'email' => 'test@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vehiculoId = DB::table('vehiculos')->insertGetId([
            'usuario_id' => $usuarioId,
            'placa' => 'XYZ789',
            'tipo' => 'automovil',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estacionamientoId = DB::table('estacionamientoadmin')->insertGetId([
            'nombre' => 'Parking Test 2',
            'email' => 'parking2@test.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear tickets activos y pagados
        $this->repository->create([
            'usuario_id' => $usuarioId,
            'vehiculo_id' => $vehiculoId,
            'estacionamiento_id' => $estacionamientoId,
            'codigo_ticket' => 'TKT-ACTIVO-1',
            'fecha_entrada' => now(),
            'tipo_reserva' => 'por_horas',
            'estado' => 'activo'
        ]);

        $this->repository->create([
            'usuario_id' => $usuarioId,
            'vehiculo_id' => $vehiculoId,
            'estacionamiento_id' => $estacionamientoId,
            'codigo_ticket' => 'TKT-PAGADO-1',
            'fecha_entrada' => now(),
            'tipo_reserva' => 'por_horas',
            'estado' => 'pagado'
        ]);

        $activeTickets = $this->repository->findActiveTickets();

        $this->assertGreaterThanOrEqual(1, $activeTickets->count());
        $activeTickets->each(function ($ticket) {
            $this->assertEquals('activo', $ticket->estado);
        });
    }

    public function test_can_find_tickets_by_usuario()
    {
        $usuarioId = DB::table('usuario_reserva')->insertGetId([
            'nombre' => 'Test User',
            'email' => 'testuser@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vehiculoId = DB::table('vehiculos')->insertGetId([
            'usuario_id' => $usuarioId,
            'placa' => 'USER123',
            'tipo' => 'automovil',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estacionamientoId = DB::table('estacionamientoadmin')->insertGetId([
            'nombre' => 'User Parking',
            'email' => 'userparking@test.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear múltiples tickets para el mismo usuario
        $this->repository->create([
            'usuario_id' => $usuarioId,
            'vehiculo_id' => $vehiculoId,
            'estacionamiento_id' => $estacionamientoId,
            'codigo_ticket' => 'USER-TKT-1',
            'fecha_entrada' => now(),
            'tipo_reserva' => 'por_horas',
            'estado' => 'activo'
        ]);

        $this->repository->create([
            'usuario_id' => $usuarioId,
            'vehiculo_id' => $vehiculoId,
            'estacionamiento_id' => $estacionamientoId,
            'codigo_ticket' => 'USER-TKT-2',
            'fecha_entrada' => now(),
            'tipo_reserva' => 'mensual',
            'estado' => 'pagado'
        ]);

        $userTickets = $this->repository->findByUsuario($usuarioId);

        $this->assertCount(2, $userTickets);
        $userTickets->each(function ($ticket) use ($usuarioId) {
            $this->assertEquals($usuarioId, $ticket->usuario_id);
        });
    }
}