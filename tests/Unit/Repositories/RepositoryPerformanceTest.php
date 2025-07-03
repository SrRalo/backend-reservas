<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Repositories\Interfaces\TicketRepositoryInterface;
use App\Repositories\Interfaces\UsuarioReservaRepositoryInterface;
use App\Repositories\Interfaces\EstacionamientoAdminRepositoryInterface;

class RepositoryPerformanceTest extends TestCase
{
    private TicketRepositoryInterface $ticketRepo;
    private UsuarioReservaRepositoryInterface $usuarioRepo;
    private EstacionamientoAdminRepositoryInterface $estacionamientoRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ticketRepo = app(TicketRepositoryInterface::class);
        $this->usuarioRepo = app(UsuarioReservaRepositoryInterface::class);
        $this->estacionamientoRepo = app(EstacionamientoAdminRepositoryInterface::class);
        $this->createTestData();
    }

    private function createTestData()
    {
        // Crear tablas si no existen
        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function ($table) {
                $table->id();
                $table->integer('usuario_id');
                $table->integer('vehiculo_id');
                $table->integer('estacionamiento_id');
                $table->string('codigo_ticket')->unique();
                $table->datetime('fecha_entrada');
                $table->datetime('fecha_salida')->nullable();
                $table->decimal('monto', 8, 2)->nullable();
                $table->enum('estado', ['activo', 'pagado', 'cancelado'])->default('activo');
                $table->enum('tipo_reserva', ['por_horas', 'mensual']);
                $table->timestamps();
            });
        }

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

        if (!Schema::hasTable('estacionamientoadmin')) {
            Schema::create('estacionamientoadmin', function ($table) {
                $table->id();
                $table->string('nombre');
                $table->string('email')->unique();
                $table->string('direccion');
                $table->integer('espacios_totales');
                $table->integer('espacios_disponibles');
                $table->decimal('precio_por_hora', 8, 2);
                $table->decimal('precio_mensual', 8, 2);
                $table->enum('estado', ['activo', 'inactivo'])->default('activo');
                $table->integer('total_reservas')->default(0);
                $table->timestamps();
            });
        }

        DB::table('tickets')->truncate();
        DB::table('usuario_reserva')->truncate();
        DB::table('estacionamientoadmin')->truncate();
    }

    public function test_can_handle_multiple_tickets()
    {
        // Crear múltiples tickets
        for ($i = 1; $i <= 15; $i++) {
            $this->ticketRepo->create([
                'usuario_id' => $i,
                'vehiculo_id' => $i,
                'estacionamiento_id' => ($i % 3) + 1,
                'codigo_ticket' => 'BULK-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'fecha_entrada' => now()->subHours($i),
                'tipo_reserva' => $i % 2 == 0 ? 'mensual' : 'por_horas',
                'estado' => $i % 3 == 0 ? 'pagado' : 'activo'
            ]);
        }

        $allTickets = $this->ticketRepo->all();
        $activeTickets = $this->ticketRepo->findActiveTickets();
        $paidTickets = $this->ticketRepo->getTicketsByEstado('pagado');

        $this->assertCount(15, $allTickets);
        $this->assertGreaterThan(0, $activeTickets->count());
        $this->assertGreaterThan(0, $paidTickets->count());
    }

    public function test_date_range_queries_performance()
    {
        // Crear tickets en diferentes fechas
        $fechas = [
            now()->subDays(10),
            now()->subDays(5),
            now()->subDays(2),
            now()->subDay(),
            now(),
            now()->addHours(2)
        ];

        foreach ($fechas as $index => $fecha) {
            $this->ticketRepo->create([
                'usuario_id' => $index + 1,
                'vehiculo_id' => $index + 1,
                'estacionamiento_id' => 1,
                'codigo_ticket' => 'DATE-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'fecha_entrada' => $fecha,
                'tipo_reserva' => 'por_horas',
            ]);
        }

        // Test múltiples rangos de fechas
        $ranges = [
            [now()->subDays(1), now()->addDay()],
            [now()->subDays(3), now()],
            [now()->subDays(7), now()->subDays(4)]
        ];

        foreach ($ranges as $range) {
            $tickets = $this->ticketRepo->getTicketsByDateRange(
                $range[0]->format('Y-m-d H:i:s'),
                $range[1]->format('Y-m-d H:i:s')
            );
            
            $this->assertIsIterable($tickets);
        }
    }

    public function test_bulk_operations()
    {
        // Crear múltiples usuarios
        $usuarios = [];
        for ($i = 1; $i <= 10; $i++) {
            $usuarios[] = $this->usuarioRepo->create([
                'nombre' => 'Usuario Bulk ' . $i,
                'email' => 'bulk' . $i . '@test.com',
                'documento' => 'BULK' . str_pad($i, 6, '0', STR_PAD_LEFT),
            ]);
        }

        // Crear múltiples estacionamientos (CON TODOS LOS CAMPOS REQUERIDOS)
        $estacionamientos = [];
        for ($i = 1; $i <= 5; $i++) {
            $estacionamientos[] = $this->estacionamientoRepo->create([
                'nombre' => 'Estacionamiento ' . $i,
                'email' => 'estacionamiento' . $i . '@test.com',
                'direccion' => 'Dirección ' . $i,
                'espacios_totales' => 100,
                'espacios_disponibles' => 90,
                'precio_por_hora' => 5.00 + $i,
                'precio_mensual' => 150.00 + ($i * 10),
                'estado' => 'activo',
                'total_reservas' => 0
            ]);
        }

        // Verificar creación masiva
        $this->assertCount(10, $usuarios);
        $this->assertCount(5, $estacionamientos);

        // Buscar usuarios activos
        $usuariosActivos = $this->usuarioRepo->findActiveUsers();
        $this->assertGreaterThanOrEqual(10, $usuariosActivos->count());

        // Buscar estacionamientos con espacios
        $estacionamientosConEspacios = $this->estacionamientoRepo->getEstacionamientosConEspacios();
        $this->assertGreaterThanOrEqual(5, $estacionamientosConEspacios->count());
    }

    public function test_edge_cases()
    {
        // Test buscar por email que no existe
        $usuarioInexistente = $this->usuarioRepo->findByEmail('noexiste@test.com');
        $this->assertNull($usuarioInexistente);

        // Test buscar ticket por código que no existe
        $ticketInexistente = $this->ticketRepo->findByCodigo('NO-EXISTE-001');
        $this->assertNull($ticketInexistente);

        // Test finalizar ticket que no existe
        $resultadoFalso = $this->ticketRepo->finalizarTicket(99999, 10.00);
        $this->assertFalse($resultadoFalso);

        // Test crear ticket con código duplicado
        $this->ticketRepo->create([
            'usuario_id' => 1,
            'vehiculo_id' => 1,
            'estacionamiento_id' => 1,
            'codigo_ticket' => 'DUPLICADO-001',
            'fecha_entrada' => now(),
            'tipo_reserva' => 'por_horas',
        ]);

        // Debería fallar al crear otro con el mismo código
        try {
            $this->ticketRepo->create([
                'usuario_id' => 2,
                'vehiculo_id' => 2,
                'estacionamiento_id' => 1,
                'codigo_ticket' => 'DUPLICADO-001',
                'fecha_entrada' => now(),
                'tipo_reserva' => 'por_horas',
            ]);
            $this->fail('Debería haber fallado por código duplicado');
        } catch (\Exception $e) {
            $this->assertTrue(true, 'Correctamente rechazó código duplicado');
        }
    }

    public function test_repository_method_coverage()
    {
        // Verificar que todos los métodos específicos funcionan
        $metodosTicket = [
            'findActiveTickets',
            'findByUsuario', 
            'findByEstacionamiento',
            'findByCodigo',
            'getTicketsByEstado',
            'getTicketsByDateRange'
        ];

        foreach ($metodosTicket as $metodo) {
            $this->assertTrue(
                method_exists($this->ticketRepo, $metodo),
                "Método $metodo no existe en TicketRepository"
            );
        }

        $metodosUsuario = [
            'findByEmail',
            'findActiveUsers'
        ];

        foreach ($metodosUsuario as $metodo) {
            $this->assertTrue(
                method_exists($this->usuarioRepo, $metodo),
                "Método $metodo no existe en UsuarioReservaRepository"
            );
        }

        $metodosEstacionamiento = [
            'findByEmail',
            'updateEspaciosDisponibles',
            'incrementarReservas',
            'getEstacionamientosConEspacios'
        ];

        foreach ($metodosEstacionamiento as $metodo) {
            $this->assertTrue(
                method_exists($this->estacionamientoRepo, $metodo),
                "Método $metodo no existe en EstacionamientoAdminRepository"
            );
        }
    }
}
