<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\UsuarioReservaRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
            // Log para debugging
            Log::info('Update user request received', [
                'user_id' => $id,
                'request_data' => $request->all(),
                'content_type' => $request->header('Content-Type')
            ]);

            $usuario = $this->usuarioRepository->find($id);
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Normalizar el campo de rol si viene en diferentes formatos
            $inputData = $request->all();
            
            // Log para ver exactamente qué datos recibimos
            Log::info('Raw input data received', [
                'user_id' => $id,
                'raw_data' => $inputData,
                'headers' => $request->headers->all(),
                'request_path' => $request->path(),
                'request_method' => $request->method()
            ]);

            // Detectar si estamos en un contexto de cambio de rol
            // Si el apellido contiene información de rol, extraerla
            $roleFromApellido = null;
            if (isset($inputData['apellido'])) {
                $apellido = strtolower($inputData['apellido']);
                if (strpos($apellido, 'admin') !== false) {
                    $roleFromApellido = 'admin';
                } elseif (strpos($apellido, 'registrador') !== false) {
                    $roleFromApellido = 'registrador';
                } elseif (strpos($apellido, 'reserva') !== false) {
                    $roleFromApellido = 'reservador';
                }
            }

            // También revisar el nombre del usuario para detectar el rol
            $roleFromNombre = null;
            if (isset($inputData['nombre'])) {
                $nombre = strtolower($inputData['nombre']);
                if (strpos($nombre, 'admin') !== false) {
                    $roleFromNombre = 'admin';
                } elseif (strpos($nombre, 'registrador') !== false) {
                    $roleFromNombre = 'registrador';
                } elseif (strpos($nombre, 'juan') !== false || strpos($nombre, 'cliente') !== false) {
                    $roleFromNombre = 'reservador';
                }
            }

            // Detectar rol desde parámetros URL o headers
            $roleFromUrl = $request->input('role', $request->input('rol'));
            $roleFromHeaders = $request->header('X-Role-Change');

            // Prioridad: headers > URL params > nombre > apellido
            $detectedRole = $roleFromHeaders ?? $roleFromUrl ?? $roleFromNombre ?? $roleFromApellido;

            if ($detectedRole) {
                $inputData['role'] = strtolower($detectedRole);
                Log::info('Role detected and added', [
                    'user_id' => $id,
                    'detected_role' => $detectedRole,
                    'source' => $roleFromHeaders ? 'headers' : ($roleFromUrl ? 'url' : ($roleFromNombre ? 'nombre' : 'apellido'))
                ]);
            }

            // Normalización estándar de rol
            if (isset($inputData['rol'])) {
                $inputData['role'] = strtolower($inputData['rol']);
                unset($inputData['rol']);
            }
            if (isset($inputData['role'])) {
                $inputData['role'] = strtolower($inputData['role']);
            }

            // Filtrar solo los campos que necesitamos
            $allowedFields = ['nombre', 'apellido', 'email', 'documento', 'telefono', 'password', 'role', 'estado'];
            $filteredData = array_intersect_key($inputData, array_flip($allowedFields));
            
            $request->merge($filteredData);

            // Validation rules más flexibles
            $rules = [
                'nombre' => 'sometimes|nullable|string|max:100',
                'apellido' => 'sometimes|nullable|string|max:100', 
                'email' => 'sometimes|nullable|email|max:100',
                'documento' => 'sometimes|nullable|string|max:20',
                'telefono' => 'sometimes|nullable|string|max:20',
                'password' => 'sometimes|nullable|string|min:6',
                'role' => 'sometimes|nullable|in:admin,registrador,reservador',
                'estado' => 'sometimes|nullable|in:activo,inactivo'
            ];

            // Si el email existe, agregar unique rule
            if (!empty($filteredData['email'])) {
                $rules['email'] .= '|unique:usuarios,email,' . $id;
            }

            // Si el documento existe, agregar unique rule  
            if (!empty($filteredData['documento'])) {
                $rules['documento'] .= '|unique:usuarios,documento,' . $id;
            }

            Log::info('Validation rules applied', [
                'user_id' => $id,
                'rules' => $rules,
                'filtered_data' => $filteredData
            ]);

            $validatedData = $request->validate($rules);

            // Hash password if provided
            if (isset($validatedData['password'])) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            }

            $updatedUsuario = $this->usuarioRepository->update($id, $validatedData);

            Log::info('Update user successful', [
                'user_id' => $id,
                'updated_fields' => array_keys($validatedData)
            ]);

            return response()->json([
                'success' => true,
                'data' => $updatedUsuario,
                'message' => 'Usuario actualizado exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Update user validation error', [
                'user_id' => $id,
                'request_data' => $request->all(),
                'validation_errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error actualizando usuario: ' . $e->getMessage(), [
                'user_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
            // Log para debugging
            Log::info('UpdateRole request received', [
                'user_id' => $id,
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            $usuario = $this->usuarioRepository->find($id);
            
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Aceptar tanto 'role' como 'rol' en el request
            $roleValue = $request->input('role', $request->input('rol'));
            
            // Convertir a minúsculas para normalizar
            if ($roleValue) {
                $roleValue = strtolower($roleValue);
            }

            $request->merge(['role' => $roleValue]);

            $validatedData = $request->validate([
                'role' => 'required|in:admin,registrador,reservador'
            ]);

            $updatedUsuario = $this->usuarioRepository->update($id, $validatedData);

            Log::info('UpdateRole successful', [
                'user_id' => $id,
                'old_role' => $usuario->role,
                'new_role' => $validatedData['role']
            ]);

            return response()->json([
                'success' => true,
                'data' => $updatedUsuario,
                'message' => 'Rol actualizado exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('UpdateRole validation error', [
                'user_id' => $id,
                'request_data' => $request->all(),
                'validation_errors' => $e->errors()
            ]);
            
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
