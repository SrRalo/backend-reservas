<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\UsuarioReserva;
use App\Models\EstacionamientoAdmin;
use App\Models\Vehiculo;
use App\Models\Ticket;
use Laravel\Sanctum\Sanctum;

class IntegrationFlowsApiTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers;

    private UsuarioReserva $user;
    private EstacionamientoAdmin $estacionamiento;
    private Vehiculo $vehiculo;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = $this->createAuthenticatedUser([
            'nombre' => 'Integration User',
            'email' => 'integration@example.com',
            'documento' => '12345678'
        ]);

        $this->estacionamiento = $this->createTestEstacionamiento([
            'nombre' => 'Estacionamiento Integration'
        ]);

        $this->vehiculo = $this->createTestVehiculo($this->user->id, [
            'placa' => 'INT123'
        ]);
    }

    /**
     * Test completo: Usuario se registra, busca estacionamiento, calcula precio, hace reserva, y paga
     */
    public function test_complete_parking_reservation_flow(): void
    {
        // 1. Usuario busca estacionamientos disponibles
        $searchResponse = $this->getJson('/api/business/estacionamientos/disponibles?fecha=' . now()->format('Y-m-d'));
        
        $searchResponse->assertStatus(200);
        $estacionamientos = $searchResponse->json('data.estacionamientos_disponibles');
        $this->assertNotEmpty($estacionamientos);
        
        $selectedEstacionamiento = collect($estacionamientos)->first();

        // 2. Usuario calcula precio estimado
        $priceRequest = [
            'estacionamiento_id' => $selectedEstacionamiento['id'],
            'tipo_reserva' => 'por_horas',
            'horas_estimadas' => 3
        ];

        $priceResponse = $this->postJson('/api/business/calcular-precio', $priceRequest);
        
        $priceResponse->assertStatus(200);
        $precioEstimado = $priceResponse->json('data.precio_estimado');
        $this->assertGreaterThan(0, $precioEstimado);

        // 3. Usuario crea la reserva
        $reservaRequest = [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $selectedEstacionamiento['id'],
            'tipo_reserva' => 'por_horas',
            'horas_estimadas' => 3
        ];

        $reservaResponse = $this->postJson('/api/business/reservas', $reservaRequest);
        
        $reservaResponse->assertStatus(201);
        $ticket = $reservaResponse->json('data.ticket');
        $this->assertNotNull($ticket['id']);

        // 4. Usuario finaliza la reserva con pago
        $pagoRequest = [
            'metodo_pago' => 'tarjeta',
            'datos_pago' => [
                'numero_tarjeta' => '4111111111111111',
                'cvv' => '123',
                'mes_expiracion' => '12',
                'anio_expiracion' => '2025'
            ]
        ];

        $pagoResponse = $this->postJson("/api/business/reservas/{$ticket['id']}/finalizar", $pagoRequest);
        
        $pagoResponse->assertStatus(200);
        $pagoData = $pagoResponse->json('data');
        $this->assertArrayHasKey('pago', $pagoData);
        $this->assertArrayHasKey('costo_total', $pagoData);

        // 5. Verificar que todo se guardó correctamente en la base de datos
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket['id'],
            'usuario_id' => $this->user->id,
            'estado' => 'pagado'
        ]);

        $this->assertDatabaseHas('pagos', [
            'ticket_id' => $ticket['id'],
            'usuario_id' => $this->user->id,
            'estado' => 'exitoso'
        ]);
    }

    /**
     * Test: Flujo de reserva mensual completo
     */
    public function test_monthly_reservation_complete_flow(): void
    {
        // 1. Calcular precio mensual
        $priceRequest = [
            'estacionamiento_id' => $this->estacionamiento->id,
            'tipo_reserva' => 'mensual',
            'dias_estimados' => 30
        ];

        $priceResponse = $this->postJson('/api/business/calcular-precio', $priceRequest);
        $priceResponse->assertStatus(200);
        
        $precioMensual = $priceResponse->json('data.precio_estimado');
        $this->assertEquals($this->estacionamiento->precio_mensual, $precioMensual);

        // 2. Crear reserva mensual
        $reservaRequest = [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'tipo_reserva' => 'mensual',
            'dias_estimados' => 30
        ];

        $reservaResponse = $this->postJson('/api/business/reservas', $reservaRequest);
        $reservaResponse->assertStatus(201);
        
        $ticket = $reservaResponse->json('data.ticket');

        // 3. Pagar con transferencia
        $pagoRequest = [
            'metodo_pago' => 'transferencia',
            'monto_recibido' => $precioMensual,
            'referencia' => 'TRANSFER123456',
            'notas' => 'Transferencia bancaria mensual'
        ];

        $pagoResponse = $this->postJson("/api/business/tickets/{$ticket['id']}/pago-manual", $pagoRequest);
        $pagoResponse->assertStatus(200);

        // Verificar reserva mensual creada correctamente
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket['id'],
            'tipo_reserva' => 'mensual'
        ]);
    }

    /**
     * Test: Flujo de penalización por tiempo excedido
     */
    public function test_penalty_for_exceeded_time_flow(): void
    {
        // 1. Crear reserva que excede el tiempo
        $ticket = Ticket::create([
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'codigo_ticket' => 'PENALTY_TEST',
            'fecha_entrada' => now()->subHours(6), // 6 horas atrás
            'estado' => 'activo',
            'tipo_reserva' => 'por_horas',
            'horas_estimadas' => 2, // Solo estimó 2 horas, pero lleva 6
            'costo_estimado' => 5000
        ]);

        // 2. Aplicar penalización por tiempo excedido
        $penaltyRequest = [
            'ticket_id' => $ticket->id,
            'tipo' => 'tiempo_excedido',
            'monto' => 8000,
            'descripcion' => 'Exceso de 4 horas en el estacionamiento'
        ];

        $penaltyResponse = $this->postJson('/api/business/penalizaciones/aplicar', $penaltyRequest);
        $penaltyResponse->assertStatus(200);

        // 3. Finalizar con pago incluyendo la penalización
        $pagoRequest = [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 15000 // Costo original + penalización + extra
        ];

        $pagoResponse = $this->postJson("/api/business/tickets/{$ticket->id}/pago-manual", $pagoRequest);
        $pagoResponse->assertStatus(200);

        // Verificar que se aplicó la penalización
        $this->assertDatabaseHas('penalizaciones', [
            'ticket_id' => $ticket->id,
            'tipo' => 'tiempo_excedido',
            'monto' => 8000
        ]);
    }

    /**
     * Test: Flujo de cancelación y reembolso
     */
    public function test_cancellation_and_refund_flow(): void
    {
        // 1. Crear una reserva (sin pagar aún)
        $reservaRequest = [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'tipo_reserva' => 'por_horas',
            'horas_estimadas' => 4
        ];

        $reservaResponse = $this->postJson('/api/business/reservas', $reservaRequest);
        $ticket = $reservaResponse->json('data.ticket');

        // 2. Cancelar la reserva mientras está activa
        $cancelRequest = [
            'motivo' => 'Cliente cambió de planes por emergencia familiar'
        ];

        $cancelResponse = $this->postJson("/api/business/reservas/{$ticket['id']}/cancelar", $cancelRequest);
        $cancelResponse->assertStatus(200);

        // Verificar estados finales
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket['id'],
            'estado' => 'cancelado'
        ]);

        // 3. Test de reembolso por separado - crear y pagar otra reserva
        $reservaRequest2 = [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'tipo_reserva' => 'por_horas',
            'horas_estimadas' => 2
        ];

        $reservaResponse2 = $this->postJson('/api/business/reservas', $reservaRequest2);
        $ticket2 = $reservaResponse2->json('data.ticket');

        $pagoRequest = [
            'metodo_pago' => 'tarjeta',
            'datos_pago' => [
                'numero_tarjeta' => '4111111111111111',
                'cvv' => '123',
                'mes_expiracion' => '12',
                'anio_expiracion' => '2025'
            ]
        ];

        $pagoResponse = $this->postJson("/api/business/reservas/{$ticket2['id']}/finalizar", $pagoRequest);
        $pagoData = $pagoResponse->json('data');
        $pagoId = $pagoData['pago']['id'];

        // 4. Procesar reembolso del pago realizado
        $refundRequest = [
            'motivo' => 'Cancelación solicitada por el cliente'
        ];

        $refundResponse = $this->postJson("/api/business/pagos/{$pagoId}/reembolsar", $refundRequest);
        $refundResponse->assertStatus(200);

        $this->assertDatabaseHas('pagos', [
            'id' => $pagoId,
            'estado' => 'reembolsado'
        ]);
    }

    /**
     * Test: Flujo de múltiples vehículos por usuario
     */
    public function test_multiple_vehicles_per_user_flow(): void
    {
        // 1. Crear vehículo adicional
        $vehiculo2 = Vehiculo::create([
            'placa' => 'INT456',
            'usuario_id' => $this->user->id,
            'modelo' => 'Honda CB250',
            'color' => 'Negro'
        ]);

        // 2. Crear reserva con primer vehículo
        $reserva1 = [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'tipo_reserva' => 'por_horas',
            'horas_estimadas' => 2
        ];

        $response1 = $this->postJson('/api/business/reservas', $reserva1);
        $response1->assertStatus(201);

        // 3. Crear reserva con segundo vehículo (debería permitirse)
        $reserva2 = [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $vehiculo2->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'tipo_reserva' => 'por_horas',
            'horas_estimadas' => 3
        ];

        $response2 = $this->postJson('/api/business/reservas', $reserva2);
        $response2->assertStatus(201);

        // 4. Intentar crear otra reserva con el primer vehículo (debería fallar)
        $reserva3 = [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'tipo_reserva' => 'por_horas',
            'horas_estimadas' => 1
        ];

        $response3 = $this->postJson('/api/business/reservas', $reserva3);
        $response3->assertStatus(400)
                  ->assertJson([
                      'success' => false,
                      'code' => 'ACTIVE_RESERVATION_EXISTS'
                  ]);

        // Verificar que se crearon ambas reservas válidas
        $this->assertDatabaseHas('tickets', [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estado' => 'activo'
        ]);

        $this->assertDatabaseHas('tickets', [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $vehiculo2->placa,
            'estado' => 'activo'
        ]);
    }

    /**
     * Test: Flujo de reportes y estadísticas
     */
    public function test_reports_and_analytics_flow(): void
    {
        // 1. Crear varios tickets con diferentes estados
        $tickets = [];
        
        // Ticket activo
        $tickets[] = Ticket::create([
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'codigo_ticket' => 'REPORT_ACTIVE',
            'fecha_entrada' => now(),
            'estado' => 'activo',
            'tipo_reserva' => 'por_horas'
        ]);

        // Ticket finalizado
        $tickets[] = Ticket::create([
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'codigo_ticket' => 'REPORT_FINISHED',
            'fecha_entrada' => now()->subHours(3),
            'fecha_salida' => now()->subMinutes(30),
            'estado' => 'finalizado',
            'tipo_reserva' => 'por_horas',
            'costo_total' => 7500
        ]);

        // Ticket cancelado
        $tickets[] = Ticket::create([
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'codigo_ticket' => 'REPORT_CANCELLED',
            'fecha_entrada' => now()->subDays(1),
            'estado' => 'cancelado',
            'tipo_reserva' => 'mensual'
        ]);

        // 2. Obtener resumen del usuario
        $userSummaryResponse = $this->getJson("/api/business/usuarios/{$this->user->id}/resumen");
        $userSummaryResponse->assertStatus(200);
        
        $userSummary = $userSummaryResponse->json('data');
        $this->assertArrayHasKey('reservas', $userSummary);
        $this->assertArrayHasKey('penalizaciones', $userSummary);

        // 3. Obtener reporte del estacionamiento
        $estacReportResponse = $this->getJson("/api/business/estacionamientos/{$this->estacionamiento->id}/reporte");
        $estacReportResponse->assertStatus(200);
        
        $estacReport = $estacReportResponse->json('data');
        $this->assertArrayHasKey('estacionamiento', $estacReport);
        $this->assertArrayHasKey('ocupacion_actual', $estacReport);
        $this->assertArrayHasKey('reservas_activas', $estacReport);

        // 4. Obtener historial de pagos del usuario
        $paymentHistoryResponse = $this->getJson("/api/business/usuarios/{$this->user->id}/pagos");
        $paymentHistoryResponse->assertStatus(200);
        
        $paymentHistory = $paymentHistoryResponse->json('data');
        $this->assertArrayHasKey('pagos', $paymentHistory);
        $this->assertArrayHasKey('total_pagos', $paymentHistory);
        $this->assertArrayHasKey('monto_total', $paymentHistory);
    }

    /**
     * Test: Validación de flujo con datos incorrectos
     */
    public function test_error_handling_throughout_flow(): void
    {
        // 1. Intentar calcular precio con estacionamiento inexistente
        $invalidPriceRequest = [
            'estacionamiento_id' => 99999,
            'tipo_reserva' => 'por_horas',
            'horas_estimadas' => 2
        ];

        $priceResponse = $this->postJson('/api/business/calcular-precio', $invalidPriceRequest);
        $priceResponse->assertStatus(400);

        // 2. Intentar crear reserva con vehículo inexistente
        $invalidReservaRequest = [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => 'NOEXISTE',
            'estacionamiento_id' => $this->estacionamiento->id,
            'tipo_reserva' => 'por_horas'
        ];

        $reservaResponse = $this->postJson('/api/business/reservas', $invalidReservaRequest);
        $reservaResponse->assertStatus(422);

        // 3. Intentar finalizar ticket inexistente
        $invalidFinalizarRequest = [
            'metodo_pago' => 'tarjeta',
            'datos_pago' => [
                'numero_tarjeta' => '4111111111111111',
                'cvv' => '123',
                'mes_expiracion' => '12',
                'anio_expiracion' => '2025'
            ]
        ];

        $finalizarResponse = $this->postJson('/api/business/reservas/99999/finalizar', $invalidFinalizarRequest);
        $finalizarResponse->assertStatus(400);

        // 4. Intentar acceder a reporte de estacionamiento inexistente
        $reportResponse = $this->getJson('/api/business/estacionamientos/99999/reporte');
        $reportResponse->assertStatus(404);
    }

    /**
     * Test: Performance con múltiples operaciones concurrentes
     */
    public function test_performance_with_multiple_operations(): void
    {
        $startTime = microtime(true);

        // Crear múltiples estacionamientos
        for ($i = 1; $i <= 5; $i++) {
            EstacionamientoAdmin::create([
                'nombre' => "Estacionamiento Perf {$i}",
                'email' => "perf{$i}@example.com",
                'direccion' => "Ubicación {$i}",
                'espacios_totales' => 50 + ($i * 10),
                'espacios_disponibles' => 50 + ($i * 10),
                'precio_por_hora' => 2000 + ($i * 500),
                'precio_mensual' => 60000 + ($i * 10000),
                'estado' => 'activo'
            ]);
        }

        // Buscar estacionamientos disponibles
        $searchResponse = $this->getJson('/api/business/estacionamientos/disponibles');
        $searchResponse->assertStatus(200);

        // Calcular precios para varios estacionamientos
        $estacionamientos = $searchResponse->json('data.estacionamientos_disponibles');
        foreach (array_slice($estacionamientos, 0, 3) as $estac) {
            $priceResponse = $this->postJson('/api/business/calcular-precio', [
                'estacionamiento_id' => $estac['id'],
                'tipo_reserva' => 'por_horas',
                'horas_estimadas' => 2
            ]);
            $priceResponse->assertStatus(200);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // La operación completa no debería tomar más de 5 segundos
        $this->assertLessThan(5.0, $executionTime, 'Las operaciones múltiples tardaron demasiado');
    }
}
