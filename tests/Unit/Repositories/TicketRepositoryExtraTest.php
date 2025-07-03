<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Repositories\Interfaces\TicketRepositoryInterface;

class TicketRepositoryExtraTest extends TestCase
{
    private TicketRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(TicketRepositoryInterface::class);
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

        // Limpiar datos
        DB::table('tickets')->truncate();
    }

    public function test_can_find_by_codigo()
    {
        $ticket = $this->repository->create([
            'usuario_id' => 1,
            'vehiculo_id' => 1,
            'estacionamiento_id' => 1,
            'codigo_ticket' => 'TEST-CODIGO-123',
            'fecha_entrada' => now(),
            'tipo_reserva' => 'por_horas',
        ]);

        $found = $this->repository->findByCodigo('TEST-CODIGO-123');

        $this->assertNotNull($found);
        $this->assertEquals('TEST-CODIGO-123', $found->codigo_ticket);
    }

    public function test_can_finalizar_ticket()
    {
        $ticket = $this->repository->create([
            'usuario_id' => 1,
            'vehiculo_id' => 1,
            'estacionamiento_id' => 1,
            'codigo_ticket' => 'FINALIZAR-TEST',
            'fecha_entrada' => now(),
            'tipo_reserva' => 'por_horas',
            'estado' => 'activo'
        ]);

        $finalized = $this->repository->finalizarTicket($ticket->id, 15.50);

        $this->assertTrue($finalized);
        
        $ticket->refresh();
        $this->assertEquals('pagado', $ticket->estado);
        $this->assertEquals(15.50, $ticket->monto);
        $this->assertNotNull($ticket->fecha_salida);
    }

    public function test_can_get_tickets_by_date_range()
    {
        // Crear tickets en diferentes fechas
        $this->repository->create([
            'usuario_id' => 1,
            'vehiculo_id' => 1,
            'estacionamiento_id' => 1,
            'codigo_ticket' => 'DATE-OLD',
            'fecha_entrada' => now()->subDays(5),
            'tipo_reserva' => 'por_horas',
        ]);

        $this->repository->create([
            'usuario_id' => 2,
            'vehiculo_id' => 2,
            'estacionamiento_id' => 1,
            'codigo_ticket' => 'DATE-NEW',
            'fecha_entrada' => now(),
            'tipo_reserva' => 'por_horas',
        ]);

        $recentTickets = $this->repository->getTicketsByDateRange(
            now()->subDay()->format('Y-m-d H:i:s'),
            now()->addDay()->format('Y-m-d H:i:s')
        );

        $this->assertGreaterThanOrEqual(1, $recentTickets->count());
    }

    public function test_can_get_tickets_by_estado()
    {
        $this->repository->create([
            'usuario_id' => 1,
            'vehiculo_id' => 1,
            'estacionamiento_id' => 1,
            'codigo_ticket' => 'ESTADO-ACTIVO',
            'fecha_entrada' => now(),
            'tipo_reserva' => 'por_horas',
            'estado' => 'activo'
        ]);

        $this->repository->create([
            'usuario_id' => 2,
            'vehiculo_id' => 2,
            'estacionamiento_id' => 1,
            'codigo_ticket' => 'ESTADO-PAGADO',
            'fecha_entrada' => now()->subHour(),
            'fecha_salida' => now(),
            'monto' => 20.00,
            'tipo_reserva' => 'por_horas',
            'estado' => 'pagado'
        ]);

        $activosTickets = $this->repository->getTicketsByEstado('activo');
        $pagadosTickets = $this->repository->getTicketsByEstado('pagado');

        $this->assertGreaterThanOrEqual(1, $activosTickets->count());
        $this->assertGreaterThanOrEqual(1, $pagadosTickets->count());
    }
}