<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ControllerBasicTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuario para autenticación
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);
        
        // Obtener token de autenticación
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        $this->token = $response->json('access_token');
    }

    protected function authenticatedHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }

    public function test_ping_endpoint_works()
    {
        $response = $this->getJson('/api/ping');
        
        $response->assertStatus(200)
                ->assertJson(['pong' => true]);
    }

    public function test_test_endpoint_works()
    {
        $response = $this->postJson('/api/test', ['test' => 'data']);
        
        $response->assertStatus(200)
                ->assertJson(['ok' => true]);
    }

    public function test_tickets_index_requires_auth()
    {
        $response = $this->getJson('/api/tickets');
        
        $response->assertStatus(401);
    }

    public function test_tickets_index_works_with_auth()
    {
        $response = $this->getJson('/api/tickets', $this->authenticatedHeaders());
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);
    }

    public function test_usuarios_index_works_with_auth()
    {
        $response = $this->getJson('/api/usuarios', $this->authenticatedHeaders());
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);
    }

    public function test_estacionamientos_index_works_with_auth()
    {
        $response = $this->getJson('/api/estacionamientos', $this->authenticatedHeaders());
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);
    }

    public function test_vehiculos_index_works_with_auth()
    {
        $response = $this->getJson('/api/vehiculos', $this->authenticatedHeaders());
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);
    }

    public function test_pagos_index_works_with_auth()
    {
        $response = $this->getJson('/api/pagos', $this->authenticatedHeaders());
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);
    }

    public function test_penalizaciones_index_works_with_auth()
    {
        $response = $this->getJson('/api/penalizaciones', $this->authenticatedHeaders());
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);
    }

    public function test_create_usuario_with_validation()
    {
        $userData = [
            'nombre' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'documento' => '12345678'
        ];

        $response = $this->postJson('/api/usuarios', $userData, $this->authenticatedHeaders());
        
        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Usuario creado exitosamente'
                ]);
    }

    public function test_create_usuario_validation_fails()
    {
        $userData = [
            'nombre' => '', // Campo requerido vacío
            'email' => 'not-an-email'
        ];

        $response = $this->postJson('/api/usuarios', $userData, $this->authenticatedHeaders());
        
        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ])
                ->assertJson([
                    'success' => false
                ]);
    }

    public function test_get_active_tickets_endpoint()
    {
        $response = $this->getJson('/api/tickets/active/list', $this->authenticatedHeaders());
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);
    }

    public function test_get_available_estacionamientos_endpoint()
    {
        $response = $this->getJson('/api/estacionamientos/available/spaces', $this->authenticatedHeaders());
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);
    }
}
