<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UsuarioAuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255',
                'apellido' => 'nullable|string|max:255',
                'email' => 'required|string|email|max:255|unique:usuarios',
                'documento' => 'required|string|max:20|unique:usuarios',
                'telefono' => 'nullable|string|max:20',
                'password' => 'required|string|min:8',
                'password_confirmation' => 'required|string|same:password',
                'role' => 'sometimes|in:registrador,reservador',
            ]);

            $result = $this->authService->registerUser($validatedData);

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en register: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $result = $this->authService->loginUser($credentials);

            if (!$result['success']) {
                return response()->json($result, 401);
            }

            return response()->json($result);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en login: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'nombre' => $user->nombre,
                    'apellido' => $user->apellido,
                    'email' => $user->email,
                    'documento' => $user->documento,
                    'telefono' => $user->telefono,
                    'estado' => $user->estado,
                    'role' => $user->role,
                ]
            ]
        ]);
    }
}
