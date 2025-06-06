<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function login(Request $request)
    {
        try {
            Log::info('Login request received', $request->except('password'));

            $validatedData = $request->validate([
                'email' => 'required|email|exists:users,email',
                'password' => 'required|string'
            ]);

            Log::info('Validation passed');

            $user = User::where('email', $validatedData['email'])->first();
            
            if (!$user) {
                Log::warning('User not found');
                return response()->json(['message' => 'Credenciales incorrectas'], 401);
            }

            if (!Hash::check($validatedData['password'], $user->password)) {
                Log::warning('Invalid password');
                return response()->json(['message' => 'Credenciales incorrectas'], 401);
            }

            // Revocar tokens anteriores
            $user->tokens()->delete();

            $token = $user->createToken('api-token')->plainTextToken;
            Log::info('Login successful for user: ' . $user->email);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al intentar iniciar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();

        return response()->json([
            'message' => 'Has cerrado sesión exitosamente'
        ]);
    }

    public function me()
    {
        return response()->json(auth()->user());
    }
}