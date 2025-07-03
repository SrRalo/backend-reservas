<?php

namespace Tests\Feature\API;

use App\Models\UsuarioReserva;
use App\Models\EstacionamientoAdmin;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

trait ApiTestHelpers
{
    protected function createAuthenticatedUser(array $overrides = []): UsuarioReserva
    {
        $user = UsuarioReserva::create(array_merge([
            'nombre' => 'Test User',
            'apellido' => 'Test Apellido',
            'email' => 'test@example.com',
            'documento' => '12345678',
            'telefono' => '123456789',
            'password' => Hash::make('password123'),
            'estado' => 'activo'
        ], $overrides));

        Sanctum::actingAs($user);
        
        return $user;
    }

    protected function createTestUser(array $overrides = []): UsuarioReserva
    {
        return UsuarioReserva::create(array_merge([
            'nombre' => 'Test User',
            'apellido' => 'Test Apellido',
            'email' => 'test@example.com',
            'documento' => '12345678',
            'telefono' => '123456789',
            'password' => Hash::make('password123'),
            'estado' => 'activo'
        ], $overrides));
    }

    protected function createTestEstacionamiento(array $overrides = []): EstacionamientoAdmin
    {
        return EstacionamientoAdmin::create(array_merge([
            'nombre' => 'Test Estacionamiento',
            'email' => 'test@estacionamiento.com',
            'direccion' => 'Test Address 123',
            'espacios_totales' => 50,
            'espacios_disponibles' => 50,
            'precio_por_hora' => 2000,
            'precio_mensual' => 45000,
            'estado' => 'activo'
        ], $overrides));
    }

    protected function createTestVehiculo(int $usuarioId = null, array $overrides = []): Vehiculo
    {
        if (!$usuarioId) {
            $user = $this->createTestUser(['email' => 'vehiculo_owner@example.com', 'documento' => '99999999']);
            $usuarioId = $user->id;
        }

        return Vehiculo::create(array_merge([
            'placa' => 'ABC123',
            'usuario_id' => $usuarioId,
            'modelo' => 'Toyota Corolla',
            'color' => 'Blanco',
            'estado' => 'activo'
        ], $overrides));
    }

    protected function createTestTicket(int $usuarioId, string $vehiculoPlaca, int $estacionamientoId, array $overrides = []): \App\Models\Ticket
    {
        return \App\Models\Ticket::create(array_merge([
            'usuario_id' => $usuarioId,
            'vehiculo_id' => $vehiculoPlaca,
            'estacionamiento_id' => $estacionamientoId,
            'codigo_ticket' => 'TEST' . rand(1000, 9999),
            'fecha_entrada' => now(),
            'estado' => 'activo',
            'tipo_reserva' => 'por_horas'
        ], $overrides));
    }
}
