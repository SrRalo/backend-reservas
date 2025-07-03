<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\UsuarioReserva;
use App\Models\EstacionamientoAdmin;
use App\Models\Vehiculo;
use App\Models\Ticket;
use App\Models\Pago;
use Laravel\Sanctum\Sanctum;

class BusinessLogicApiTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers;

    private UsuarioReserva $user;
    private EstacionamientoAdmin $estacionamiento;
    private Vehiculo $vehiculo;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = $this->createAuthenticatedUser();

        $this->estacionamiento = $this->createTestEstacionamiento([
            'nombre' => 'Estacionamiento Central'
        ]);

        $this->vehiculo = $this->createTestVehiculo($this->user->id, [
            'placa' => 'ABC123'
        ]);
    }

    // ==================== CÁLCULO DE PRECIOS ====================

    public function test_can_calculate_hourly_price(): void
    {
        $requestData = [
            'estacionamiento_id' => $this->estacionamiento->id,
            'tipo_reserva' => 'por_horas',
            'horas_estimadas' => 3
        ];

        $response = $this->postJson('/api/business/calcular-precio', $requestData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'precio_estimado',
                        'tipo_reserva',
                        'duracion',
                        'detalles'
                    ]
                ]);

        $data = $response->json('data');
        $expectedPrice = 3 * $this->estacionamiento->precio_por_hora;
        $this->assertEquals($expectedPrice, $data['precio_estimado']);
        $this->assertEquals('por_horas', $data['tipo_reserva']);
    }

    public function test_can_calculate_monthly_price(): void
    {
        $requestData = [
            'estacionamiento_id' => $this->estacionamiento->id,
            'tipo_reserva' => 'mensual',
            'dias_estimados' => 30
        ];

        $response = $this->postJson('/api/business/calcular-precio', $requestData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'precio_estimado',
                        'tipo_reserva',
                        'duracion'
                    ]
                ]);

        $data = $response->json('data');
        $this->assertEquals($this->estacionamiento->precio_mensual, $data['precio_estimado']);
        $this->assertEquals('mensual', $data['tipo_reserva']);
    }

    public function test_price_calculation_requires_valid_estacionamiento(): void
    {
        $requestData = [
            'estacionamiento_id' => 99999,
            'tipo_reserva' => 'por_horas',
            'horas_estimadas' => 2
        ];

        $response = $this->postJson('/api/business/calcular-precio', $requestData);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false
                ]);
    }

    // ==================== BÚSQUEDA DE ESTACIONAMIENTOS ====================

    public function test_can_search_available_estacionamientos(): void
    {
        // Crear varios estacionamientos
        EstacionamientoAdmin::create([
            'nombre' => 'Estacionamiento Norte',
            'email' => 'norte@estac.com',
            'direccion' => 'Norte de la ciudad',
            'espacios_totales' => 50,
            'espacios_disponibles' => 50,
            'precio_por_hora' => 2500,
            'precio_mensual' => 70000,
            'estado' => 'activo'
        ]);

        EstacionamientoAdmin::create([
            'nombre' => 'Estacionamiento Sur',
            'email' => 'sur@estac.com',
            'direccion' => 'Sur de la ciudad',
            'espacios_totales' => 75,
            'espacios_disponibles' => 75,
            'precio_por_hora' => 3500,
            'precio_mensual' => 90000,
            'estado' => 'activo'
        ]);

        $response = $this->getJson('/api/business/estacionamientos/disponibles?fecha=' . now()->format('Y-m-d') . '&hora=14:00');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'estacionamientos_disponibles' => [
                            '*' => [
                                'id',
                                'nombre',
                                'ubicacion',
                                'plazas_totales',
                                'precio_hora',
                                'precio_mes'
                            ]
                        ]
                    ]
                ]);

        $estacionamientos = $response->json('data.estacionamientos_disponibles');
        $this->assertGreaterThanOrEqual(3, count($estacionamientos)); // Al menos los 3 creados
    }

    public function test_can_search_estacionamientos_with_filters(): void
    {
        $response = $this->getJson('/api/business/estacionamientos/disponibles?precio_max=3000&tipo_reserva=por_horas');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'estacionamientos_disponibles'
                    ]
                ]);
    }

    // ==================== CREACIÓN DE RESERVAS ====================

    public function test_can_create_hourly_reservation(): void
    {
        $reservaData = [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'tipo_reserva' => 'por_horas',
            'horas_estimadas' => 4
        ];

        $response = $this->postJson('/api/business/reservas', $reservaData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'ticket',
                        'precio_total',
                        'fecha_entrada',
                        'hora_entrada'
                    ]
                ]);

        // Verificar que se creó el ticket
        $this->assertDatabaseHas('tickets', [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'estado' => 'activo'
        ]);
    }

    public function test_can_create_monthly_reservation(): void
    {
        $reservaData = [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'tipo_reserva' => 'mensual',
            'dias_estimados' => 30
        ];

        $response = $this->postJson('/api/business/reservas', $reservaData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'ticket',
                        'precio_total',
                        'fecha_entrada',
                        'hora_entrada'
                    ]
                ]);
    }

    public function test_cannot_create_reservation_with_invalid_data(): void
    {
        $invalidData = [
            'usuario_id' => 99999, // Usuario inexistente
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'tipo_reserva' => 'por_horas'
        ];

        $response = $this->postJson('/api/business/reservas', $invalidData);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false
                ]);
    }

    public function test_cannot_create_duplicate_active_reservation(): void
    {
        // Crear primera reserva
        $reservaData = [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'tipo_reserva' => 'por_horas',
            'horas_estimadas' => 2
        ];

        $firstResponse = $this->postJson('/api/business/reservas', $reservaData);
        $firstResponse->assertStatus(201);

        // Intentar crear segunda reserva con el mismo vehículo
        $secondResponse = $this->postJson('/api/business/reservas', $reservaData);
        $secondResponse->assertStatus(400)
                      ->assertJson([
                          'success' => false,
                          'code' => 'ACTIVE_RESERVATION_EXISTS'
                      ]);
    }

    // ==================== FINALIZACIÓN DE RESERVAS ====================

    public function test_can_finalize_reservation_with_card_payment(): void
    {
        // Crear ticket activo
        $ticket = Ticket::create([
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'codigo_ticket' => 'TEST001',
            'fecha_entrada' => now()->subHours(2),
            'estado' => 'activo',
            'tipo_reserva' => 'por_horas',
            'precio_total' => 6000
        ]);

        $finalizacionData = [
            'metodo_pago' => 'tarjeta',
            'datos_pago' => [
                'numero_tarjeta' => '4111111111111111',
                'cvv' => '123',
                'mes_expiracion' => '12',
                'anio_expiracion' => '2025'
            ]
        ];

        $response = $this->postJson("/api/business/reservas/{$ticket->id}/finalizar", $finalizacionData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'ticket',
                        'pago',
                        'tiempo_total',
                        'costo_total'
                    ]
                ]);

        // Verificar que el ticket fue actualizado
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'estado' => 'pagado'
        ]);
    }

    public function test_can_process_manual_payment(): void
    {
        $ticket = Ticket::create([
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'codigo_ticket' => 'MANUAL001',
            'fecha_entrada' => now()->subHours(1),
            'estado' => 'activo',
            'tipo_reserva' => 'por_horas',
            'costo_estimado' => 3000
        ]);

        $pagoData = [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => 3500,
            'referencia' => 'EFECTIVO001',
            'notas' => 'Pago en efectivo con cambio'
        ];

        $response = $this->postJson("/api/business/tickets/{$ticket->id}/pago-manual", $pagoData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'pago',
                        'monto_total',
                        'codigo_transaccion'
                    ]
                ]);
    }

    // ==================== CANCELACIÓN DE RESERVAS ====================

    public function test_can_cancel_reservation(): void
    {
        $ticket = Ticket::create([
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'codigo_ticket' => 'CANCEL001',
            'fecha_entrada' => now(),
            'estado' => 'activo',
            'tipo_reserva' => 'por_horas'
        ]);

        $cancelData = [
            'motivo' => 'Cambio de planes del usuario'
        ];

        $response = $this->postJson("/api/business/reservas/{$ticket->id}/cancelar", $cancelData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'ticket_cancelado',
                        'motivo_cancelacion'
                    ]
                ]);

        // Verificar que el ticket fue cancelado
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'estado' => 'cancelado'
        ]);
    }

    // ==================== PENALIZACIONES ====================

    public function test_can_apply_penalty(): void
    {
        $ticket = Ticket::create([
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'codigo_ticket' => 'PENALTY001',
            'fecha_entrada' => now()->subHours(5),
            'estado' => 'activo',
            'tipo_reserva' => 'por_horas',
            'precio_total' => 3000 // Cambiado de costo_estimado
        ]);

        $penaltyData = [
            'ticket_id' => $ticket->id,
            'tipo' => 'tiempo_excedido',
            'monto' => 5000,
            'descripcion' => 'Exceso de tiempo de estacionamiento'
        ];

        $response = $this->postJson('/api/business/penalizaciones/aplicar', $penaltyData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'penalizacion',
                        'ticket_actualizado'
                    ]
                ]);
    }

    // ==================== HISTORIAL Y REPORTES ====================

    public function test_can_get_user_payment_history(): void
    {
        // Crear algunos pagos para el usuario
        $ticket1 = Ticket::create([
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'codigo_ticket' => 'HIST001',
            'fecha_entrada' => now()->subDays(2),
            'fecha_salida' => now()->subDays(2)->addHours(3),
            'estado' => 'finalizado'
        ]);

        $response = $this->getJson("/api/business/usuarios/{$this->user->id}/pagos");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'pagos',
                        'total_pagos',
                        'monto_total'
                    ]
                ]);
    }

    public function test_can_get_user_summary(): void
    {
        $response = $this->getJson("/api/business/usuarios/{$this->user->id}/resumen");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'reservas',
                        'penalizaciones'
                    ]
                ]);
    }

    public function test_can_get_estacionamiento_report(): void
    {
        $response = $this->getJson("/api/business/estacionamientos/{$this->estacionamiento->id}/reporte");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'estacionamiento',
                        'ocupacion_actual',
                        'reservas_activas',
                        'ingresos_estimados'
                    ]
                ]);
    }

    // ==================== REEMBOLSOS ====================

    public function test_can_process_refund(): void
    {
        // Crear un pago exitoso
        $ticket = Ticket::create([
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'codigo_ticket' => 'REFUND001',
            'fecha_entrada' => now()->subDays(1),
            'estado' => 'pagado'
        ]);

        $pago = Pago::create([
            'ticket_id' => $ticket->id,
            'usuario_id' => $this->user->id,
            'monto' => 10000,
            'metodo_pago' => 'tarjeta',
            'estado' => 'exitoso',
            'codigo_transaccion' => 'TXN123456',
            'fecha_pago' => now()->subDays(1)
        ]);

        $refundData = [
            'motivo' => 'Solicitud del cliente por servicio deficiente'
        ];

        $response = $this->postJson("/api/business/pagos/{$pago->id}/reembolsar", $refundData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'reembolso',
                        'pago_actualizado'
                    ]
                ]);

        // Verificar que el pago fue marcado como reembolsado
        $this->assertDatabaseHas('pagos', [
            'id' => $pago->id,
            'estado' => 'reembolsado'
        ]);
    }

    // ==================== VALIDACIONES DE BUSINESS LOGIC ====================

    public function test_business_logic_requires_authentication(): void
    {
        // Remover autenticación
        $this->app['auth']->forgetUser();

        $response = $this->postJson('/api/business/reservas', [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $this->vehiculo->placa,
            'estacionamiento_id' => $this->estacionamiento->id,
            'tipo_reserva' => 'por_horas'
        ]);

        $response->assertStatus(401);
    }

    public function test_business_logic_validates_input_data(): void
    {
        $invalidData = [
            'usuario_id' => 'invalid',
            'vehiculo_id' => '',
            'estacionamiento_id' => -1,
            'tipo_reserva' => 'invalid_type'
        ];

        $response = $this->postJson('/api/business/reservas', $invalidData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ]);
    }
}
