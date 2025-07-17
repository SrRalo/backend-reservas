<?php

namespace App\Services;

use App\Repositories\Interfaces\UsuarioReservaRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AuthService
{
    private UsuarioReservaRepositoryInterface $usuarioRepository;

    public function __construct(UsuarioReservaRepositoryInterface $usuarioRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
    }

    /**
     * Registrar un nuevo usuario
     */
    public function registerUser(array $userData): array
    {
        try {
            // Procesar datos del usuario
            $processedData = $this->processUserData($userData);

            // Crear usuario
            $user = $this->usuarioRepository->create($processedData);

            // Generar token
            $token = $this->generateUserToken($user);

            return [
                'success' => true,
                'data' => [
                    'user' => $this->formatUserResponse($user),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error en registerUser: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Iniciar sesión
     */
    public function loginUser(array $credentials): array
    {
        try {
            // Buscar usuario por email
            $user = $this->usuarioRepository->findByEmail($credentials['email']);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ];
            }

            // Verificar contraseña
            if (!Hash::check($credentials['password'], $user->password)) {
                return [
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ];
            }

            // Verificar estado del usuario
            if (!$this->isUserActive($user)) {
                return [
                    'success' => false,
                    'message' => 'Usuario inactivo'
                ];
            }

            // Actualizar último acceso
            $this->updateLastAccess($user);

            // Generar token
            $token = $this->generateUserToken($user);

            return [
                'success' => true,
                'data' => [
                    'user' => $this->formatUserResponse($user),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error en loginUser: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Procesar datos del usuario para registro
     */
    private function processUserData(array $userData): array
    {
        return [
            'nombre' => $userData['nombre'],
            'apellido' => $userData['apellido'] ?? null,
            'email' => $userData['email'],
            'documento' => $userData['documento'],
            'telefono' => $userData['telefono'] ?? null,
            'password' => Hash::make($userData['password']),
            'role' => $userData['role'] ?? 'reservador', // Rol por defecto
            'estado' => 'activo',
        ];
    }

    /**
     * Verificar si el usuario está activo
     */
    private function isUserActive($user): bool
    {
        return $user->estado === 'activo';
    }

    /**
     * Actualizar último acceso del usuario
     */
    private function updateLastAccess($user): void
    {
        $this->usuarioRepository->update($user->id, [
            'ultimo_acceso' => Carbon::now()
        ]);
    }

    /**
     * Generar token para el usuario
     */
    private function generateUserToken($user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }

    /**
     * Formatear respuesta del usuario
     */
    private function formatUserResponse($user): array
    {
        return [
            'id' => $user->id,
            'nombre' => $user->nombre,
            'apellido' => $user->apellido,
            'email' => $user->email,
            'documento' => $user->documento,
            'telefono' => $user->telefono,
            'role' => $user->role,
            'estado' => $user->estado,
        ];
    }
}
