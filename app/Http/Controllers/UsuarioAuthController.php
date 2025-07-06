<?php

namespace App\Http\Controllers;

use App\Models\UsuarioReserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsuarioAuthController extends Controller
{
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:usuario_reserva',
            'documento' => 'required|string|max:20|unique:usuario_reserva',
            'telefono' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|string|same:password',
        ]);

        $user = UsuarioReserva::create([
            'nombre' => $validatedData['nombre'],
            'apellido' => $validatedData['apellido'] ?? null,
            'email' => $validatedData['email'],
            'documento' => $validatedData['documento'],
            'telefono' => $validatedData['telefono'] ?? null,
            'password' => Hash::make($validatedData['password']),
            'estado' => 'activo',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

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
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = UsuarioReserva::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        if ($user->estado !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'Usuario inactivo'
            ], 401);
        }

        // Actualizar último acceso
        $user->ultimo_acceso = now();
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

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
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
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
                ]
            ]
        ]);
    }
}
