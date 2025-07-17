<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\UsuarioReservaRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class UsuarioReservaController extends Controller
{
    private UsuarioReservaRepositoryInterface $usuarioRepository;
    private AuthService $authService;

    public function __construct(
        UsuarioReservaRepositoryInterface $usuarioRepository,
        AuthService $authService
    ) {
        $this->usuarioRepository = $usuarioRepository;
        $this->authService = $authService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $usuarios = $this->usuarioRepository->all();
            
            return response()->json([
                'success' => true,
                'data' => $usuarios,
                'message' => 'Usuarios obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo usuarios: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:100',
                'apellido' => 'nullable|string|max:100',
                'email' => 'required|email|max:100|unique:usuarios,email',
                'documento' => 'required|string|max:20|unique:usuarios,documento',
                'telefono' => 'nullable|string|max:20',
                'password' => 'required|string|min:8',
                'role' => 'sometimes|in:admin,registrador,reservador',
                'estado' => 'sometimes|in:activo,inactivo'
            ]);

            // Hash the password
            $validatedData['password'] = Hash::make($validatedData['password']);

            $usuario = $this->usuarioRepository->create($validatedData);

            return response()->json([
                'success' => true,
                'data' => $usuario,
                'message' => 'Usuario creado exitosamente'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creando usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $usuario = $this->usuarioRepository->find($id);
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $usuario,
                'message' => 'Usuario obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $usuario = $this->usuarioRepository->find($id);
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $validatedData = $request->validate([
                'nombre' => 'sometimes|string|max:100',
                'apellido' => 'sometimes|string|max:100',
                'email' => 'sometimes|email|max:100|unique:usuarios,email,' . $id,
                'documento' => 'sometimes|string|max:20|unique:usuarios,documento,' . $id,
                'telefono' => 'sometimes|string|max:20',
                'password' => 'sometimes|string|min:8',
                'role' => 'sometimes|in:admin,registrador,reservador',
                'estado' => 'sometimes|in:activo,inactivo'
            ]);

            // Hash password if provided
            if (isset($validatedData['password'])) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            }

            $updatedUsuario = $this->usuarioRepository->update($id, $validatedData);

            return response()->json([
                'success' => true,
                'data' => $updatedUsuario,
                'message' => 'Usuario actualizado exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error actualizando usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $usuario = $this->usuarioRepository->find($id);
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $this->usuarioRepository->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error eliminando usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Get user by email
     */
    public function getUserByEmail(string $email): JsonResponse
    {
        try {
            $usuario = $this->usuarioRepository->findByEmail($email);
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $usuario,
                'message' => 'Usuario obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo usuario por email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Get active users
     */
    public function getActiveUsers(): JsonResponse
    {
        try {
            $usuarios = $this->usuarioRepository->findActiveUsers();
            
            return response()->json([
                'success' => true,
                'data' => $usuarios,
                'message' => 'Usuarios activos obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo usuarios activos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener usuarios por rol específico
     */
    public function getUsersByRole(string $role): JsonResponse
    {
        try {
            if (!in_array($role, ['admin', 'registrador', 'reservador'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rol inválido'
                ], 400);
            }

            $usuarios = $this->usuarioRepository->getByRole($role);
            
            return response()->json([
                'success' => true,
                'data' => $usuarios,
                'message' => "Usuarios con rol '{$role}' obtenidos exitosamente"
            ]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo usuarios por rol {$role}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Actualizar rol de usuario (solo admin)
     */
    public function updateRole(Request $request, int $id): JsonResponse
    {
        try {
            $usuario = $this->usuarioRepository->find($id);
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $validatedData = $request->validate([
                'role' => 'required|in:admin,registrador,reservador'
            ]);

            $updatedUsuario = $this->usuarioRepository->update($id, $validatedData);

            return response()->json([
                'success' => true,
                'data' => $updatedUsuario,
                'message' => 'Rol actualizado exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error actualizando rol: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de usuarios por rol
     */
    public function getRoleStats(): JsonResponse
    {
        try {
            $stats = $this->usuarioRepository->getRoleStatistics();
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
}
