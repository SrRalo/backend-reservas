<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Debe iniciar sesión.'
            ], 401);
        }

        $user = Auth::user();

        // Verificar permisos específicos según el rol
        if (!$this->hasPermission($user, $permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. No tiene permisos para esta acción.',
                'required_permission' => $permission,
                'user_role' => $user->role
            ], 403);
        }

        return $next($request);
    }

    /**
     * Verificar si el usuario tiene el permiso específico
     */
    private function hasPermission($user, string $permission): bool
    {
        $rolePermissions = [
            'admin' => [
                'view-all-penalizaciones',
                'create-penalizaciones',
                'update-penalizaciones',
                'delete-penalizaciones',
                'view-all-tickets',
                'create-tickets',
                'update-tickets',
                'delete-tickets',
                'manage-estacionamientos',
                'view-reports',
                'manage-users',
                'view-all-vehiculos',
                'create-vehiculos',
                'update-vehiculos',
                'delete-vehiculos'
            ],
            'registrador' => [
                'view-own-penalizaciones',
                'create-penalizaciones',
                'view-own-tickets',
                'create-tickets',
                'update-own-tickets',
                'manage-own-estacionamientos',
                'view-own-reports',
                'view-own-vehiculos',
                'create-vehiculos',
                'update-own-vehiculos'
            ],
            'reservador' => [
                'view-own-tickets',
                'create-reservas',
                'view-estacionamientos',
                'manage-own-vehiculos',
                'create-vehiculos',
                'update-own-vehiculos',
                'delete-own-vehiculos'
            ]
        ];

        return in_array($permission, $rolePermissions[$user->role] ?? []);
    }
}
