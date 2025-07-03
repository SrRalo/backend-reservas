<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\UsuarioReserva;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Hash;

class AuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $userData = [
            'nombre' => 'Juan Pérez',
            'apellido' => 'García',
            'email' => 'juan@example.com',
            'documento' => '12345678',
            'telefono' => '123456789',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user' => [
                            'id',
                            'nombre',
                            'apellido',
                            'email',
                            'documento',
                            'telefono',
                            'estado'
                        ],
                        'access_token',
                        'token_type'
                    ]
                ]);

        $this->assertDatabaseHas('usuario_reserva', [
            'email' => 'juan@example.com',
            'nombre' => 'Juan Pérez'
        ]);
    }

    public function test_user_cannot_register_with_invalid_data(): void
    {
        $invalidData = [
            'nombre' => '',
            'email' => 'invalid-email',
            'documento' => '123', // muy corto
            'password' => '123' // muy corto
        ];

        $response = $this->postJson('/api/register', $invalidData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);
    }

    public function test_user_cannot_register_with_existing_email(): void
    {
        // Crear usuario existente
        UsuarioReserva::create([
            'nombre' => 'Usuario Existente',
            'email' => 'existente@example.com',
            'documento' => '87654321',
            'password' => Hash::make('password123'),
            'estado' => 'activo'
        ]);

        $userData = [
            'nombre' => 'Nuevo Usuario',
            'email' => 'existente@example.com', // Email duplicado
            'documento' => '12345678',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = UsuarioReserva::create([
            'nombre' => 'Test User',
            'email' => 'test@example.com',
            'documento' => '12345678',
            'password' => Hash::make('password123'),
            'estado' => 'activo'
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user' => [
                            'id',
                            'nombre',
                            'email',
                            'documento',
                            'estado'
                        ],
                        'access_token',
                        'token_type'
                    ]
                ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = UsuarioReserva::create([
            'nombre' => 'Test User',
            'email' => 'test@example.com',
            'documento' => '12345678',
            'password' => Hash::make('password123'),
            'estado' => 'activo'
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrong-password'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ]);
    }

    public function test_user_cannot_login_if_inactive(): void
    {
        $user = UsuarioReserva::create([
            'nombre' => 'Inactive User',
            'email' => 'inactive@example.com',
            'documento' => '12345678',
            'password' => Hash::make('password123'),
            'estado' => 'inactivo'
        ]);

        $loginData = [
            'email' => 'inactive@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Usuario inactivo'
                ]);
    }

    public function test_authenticated_user_can_access_protected_routes(): void
    {
        $user = UsuarioReserva::create([
            'nombre' => 'Authenticated User',
            'email' => 'auth@example.com',
            'documento' => '12345678',
            'password' => Hash::make('password123'),
            'estado' => 'activo'
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user' => [
                            'id',
                            'nombre',
                            'email',
                            'documento',
                            'estado'
                        ]
                    ]
                ]);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $user = UsuarioReserva::create([
            'nombre' => 'Logout User',
            'email' => 'logout@example.com',
            'documento' => '12345678',
            'password' => Hash::make('password123'),
            'estado' => 'activo'
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Sesión cerrada exitosamente'
                ]);
    }

    public function test_protected_endpoints_require_authentication(): void
    {
        $protectedEndpoints = [
            ['GET', '/api/tickets'],
            ['GET', '/api/usuarios'],
            ['GET', '/api/estacionamientos'],
            ['GET', '/api/vehiculos'],
            ['GET', '/api/pagos'],
            ['GET', '/api/penalizaciones'],
            ['POST', '/api/business/reservas'],
            ['GET', '/api/business/estacionamientos/disponibles'],
        ];

        foreach ($protectedEndpoints as [$method, $endpoint]) {
            $response = $method === 'GET' 
                ? $this->getJson($endpoint)
                : $this->postJson($endpoint, []);

            $response->assertStatus(401, "Endpoint {$method} {$endpoint} should require authentication");
        }
    }

    public function test_token_invalidation_after_logout(): void
    {
        $user = UsuarioReserva::create([
            'nombre' => 'Token Test User',
            'email' => 'token@example.com',
            'documento' => '12345678',
            'password' => Hash::make('password123'),
            'estado' => 'activo'
        ]);

        // Login to get token
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'token@example.com',
            'password' => 'password123'
        ]);

        $token = $loginResponse->json('data.access_token');

        // Access protected route with token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/me');

        $response->assertStatus(200);

        // Logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/logout');
        
        $logoutResponse->assertStatus(200);

        // Verify that the token was deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user)
        ]);
    }
}
