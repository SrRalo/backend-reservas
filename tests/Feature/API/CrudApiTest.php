<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\UsuarioReserva;
use App\Models\EstacionamientoAdmin;
use App\Models\Vehiculo;
use App\Models\Ticket;
use Laravel\Sanctum\Sanctum;

class CrudApiTest extends TestCase
{
    use RefreshDatabase, ApiTestHelpers;

    private UsuarioReserva $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = $this->createAuthenticatedUser();
    }

    // ==================== USUARIOS TESTS ====================

    public function test_can_list_usuarios(): void
    {
        // Crear algunos usuarios adicionales
        $this->createTestUser([
            'nombre' => 'Usuario 2',
            'email' => 'user2@example.com',
            'documento' => '87654321'
        ]);

        $response = $this->getJson('/api/usuarios');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'nombre',
                            'email',
                            'documento'
                        ]
                    ]
                ]);

        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_can_create_usuario(): void
    {
        $userData = [
            'nombre' => 'Nuevo Usuario',
            'apellido' => 'Apellido Test',
            'email' => 'nuevo@example.com',
            'documento' => '99887766',
            'telefono' => '123456789',
            'password' => 'password123',
            'estado' => 'activo'
        ];

        $response = $this->postJson('/api/usuarios', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'nombre',
                        'apellido',
                        'email',
                        'documento',
                        'telefono'
                    ]
                ]);

        $this->assertDatabaseHas('usuarios', [
            'email' => 'nuevo@example.com',
            'nombre' => 'Nuevo Usuario'
        ]);
    }

    public function test_can_show_usuario(): void
    {
        $response = $this->getJson("/api/usuarios/{$this->user->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'nombre',
                        'email',
                        'documento'
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'id' => $this->user->id,
                        'email' => $this->user->email
                    ]
                ]);
    }

    public function test_can_update_usuario(): void
    {
        $updateData = [
            'nombre' => 'Nombre Actualizado',
            'documento' => '11223344'
        ];

        $response = $this->putJson("/api/usuarios/{$this->user->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'nombre',
                        'email'
                    ]
                ]);

        $this->assertDatabaseHas('usuarios', [
            'id' => $this->user->id,
            'nombre' => 'Nombre Actualizado',
            'documento' => '11223344'
        ]);
    }

    public function test_can_delete_usuario(): void
    {
        $newUser = $this->createTestUser([
            'nombre' => 'Usuario a eliminar',
            'email' => 'eliminar@example.com',
            'documento' => '55667788'
        ]);

        $response = $this->deleteJson("/api/usuarios/{$newUser->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $this->assertDatabaseMissing('usuarios', [
            'id' => $newUser->id
        ]);
    }

    // ==================== ESTACIONAMIENTOS TESTS ====================

    public function test_can_list_estacionamientos(): void
    {
        $this->createTestEstacionamiento([
            'nombre' => 'Estacionamiento 1',
            'correo' => 'estac1@example.com'
        ]);

        $response = $this->getJson('/api/estacionamientos');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'nombre',
                            'email',
                            'espacios_totales'
                        ]
                    ]
                ]);
    }

    public function test_can_create_estacionamiento(): void
    {
        $estacionamientoData = [
            'nombre' => 'Nuevo Estacionamiento',
            'email' => 'nuevo@estacionamiento.com',
            'direccion' => 'Nueva Ubicación',
            'espacios_totales' => 150,
            'espacios_disponibles' => 150,
            'precio_por_hora' => 6000,
            'precio_mensual' => 140000,
            'estado' => 'activo'
        ];

        $response = $this->postJson('/api/estacionamientos', $estacionamientoData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'nombre',
                        'email',
                        'espacios_totales'
                    ]
                ]);

        $this->assertDatabaseHas('estacionamientoadmin', [
            'nombre' => 'Nuevo Estacionamiento',
            'email' => 'nuevo@estacionamiento.com'
        ]);
    }

    // ==================== VEHÍCULOS TESTS ====================

    public function test_can_list_vehiculos(): void
    {
        $this->createTestVehiculo($this->user->id, [
            'placa' => 'ABC123'
        ]);

        $response = $this->getJson('/api/vehiculos');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'placa',
                            'usuario_id',
                            'modelo'
                        ]
                    ]
                ]);
    }

    public function test_can_create_vehiculo(): void
    {
        $vehiculoData = [
            'placa' => 'XYZ789',
            'modelo' => 'Honda CB250',
            'color' => 'Negro',
            'usuario_id' => $this->user->id,
            'estado' => 'activo'
        ];

        $response = $this->postJson('/api/vehiculos', $vehiculoData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'placa',
                        'usuario_id',
                        'modelo'
                    ]
                ]);

        $this->assertDatabaseHas('vehiculos', [
            'placa' => 'XYZ789',
            'usuario_id' => $this->user->id
        ]);
    }

    public function test_can_get_vehiculos_by_user(): void
    {
        $this->createTestVehiculo($this->user->id, [
            'placa' => 'USER123'
        ]);

        $this->createTestVehiculo($this->user->id, [
            'placa' => 'USER456',
            'modelo' => 'Honda CB250'
        ]);

        $response = $this->getJson("/api/vehiculos/user/{$this->user->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'placa',
                            'usuario_id',
                            'modelo'
                        ]
                    ]
                ]);

        $vehiculos = $response->json('data');
        $this->assertEquals(2, count($vehiculos));
        $this->assertEquals($this->user->id, $vehiculos[0]['usuario_id']);
    }

    // ==================== TICKETS TESTS ====================

    public function test_can_list_tickets(): void
    {
        $estacionamiento = $this->createTestEstacionamiento([
            'nombre' => 'Test Estacionamiento',
            'correo' => 'test@estac.com'
        ]);

        $vehiculo = $this->createTestVehiculo($this->user->id, [
            'placa' => 'TEST123'
        ]);

        $this->createTestTicket($this->user->id, $vehiculo->placa, $estacionamiento->id, [
            'codigo_ticket' => 'TICKET001'
        ]);

        $response = $this->getJson('/api/tickets');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'usuario_id',
                            'vehiculo_id',
                            'estacionamiento_id',
                            'codigo_ticket',
                            'estado'
                        ]
                    ]
                ]);
    }

    public function test_can_create_ticket(): void
    {
        $estacionamiento = $this->createTestEstacionamiento([
            'nombre' => 'Test Estacionamiento',
            'correo' => 'test@estac.com'
        ]);

        $vehiculo = $this->createTestVehiculo($this->user->id, [
            'placa' => 'NEW123'
        ]);

        $ticketData = [
            'usuario_id' => $this->user->id,
            'vehiculo_id' => $vehiculo->placa,
            'estacionamiento_id' => $estacionamiento->id,
            'codigo_ticket' => 'NEWTICKET001',
            'fecha_entrada' => now()->format('Y-m-d H:i:s'),
            'estado' => 'activo',
            'tipo_reserva' => 'por_horas'
        ];

        $response = $this->postJson('/api/tickets', $ticketData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'usuario_id',
                        'vehiculo_id',
                        'codigo_ticket'
                    ]
                ]);

        $this->assertDatabaseHas('tickets', [
            'codigo_ticket' => 'NEWTICKET001',
            'usuario_id' => $this->user->id
        ]);
    }

    public function test_can_get_active_tickets(): void
    {
        $estacionamiento = $this->createTestEstacionamiento([
            'nombre' => 'Test Estacionamiento',
            'correo' => 'test@estac.com'
        ]);

        $vehiculo = $this->createTestVehiculo($this->user->id, [
            'placa' => 'ACTIVE123'
        ]);

        // Crear ticket activo
        $this->createTestTicket($this->user->id, $vehiculo->placa, $estacionamiento->id, [
            'codigo_ticket' => 'ACTIVE001',
            'estado' => 'activo'
        ]);

        // Crear ticket finalizado
        $this->createTestTicket($this->user->id, $vehiculo->placa, $estacionamiento->id, [
            'codigo_ticket' => 'FINISHED001',
            'fecha_entrada' => now()->subHours(2),
            'fecha_salida' => now(),
            'estado' => 'finalizado'
        ]);

        $response = $this->getJson('/api/tickets/active/list');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data'
                ]);

        $tickets = $response->json('data');
        $this->assertCount(1, $tickets); // Solo el ticket activo
        $this->assertEquals('activo', $tickets[0]['estado']);
    }

    // ==================== VALIDATION TESTS ====================

    public function test_validation_errors_are_properly_formatted(): void
    {
        $invalidUsuarioData = [
            'nombre' => '', // Required
            'email' => 'invalid-email', // Invalid format
            'documento' => '' // Required
        ];

        $response = $this->postJson('/api/usuarios', $invalidUsuarioData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors' => [
                        'nombre',
                        'email',
                        'documento'
                    ]
                ])
                ->assertJson([
                    'success' => false
                ]);
    }

    public function test_not_found_responses_are_properly_formatted(): void
    {
        $response = $this->getJson('/api/usuarios/99999');

        $response->assertStatus(404)
                ->assertJsonStructure([
                    'success',
                    'message'
                ])
                ->assertJson([
                    'success' => false
                ]);
    }
}
