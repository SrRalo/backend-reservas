<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\UsuarioAuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UsuarioReservaController;
use App\Http\Controllers\EstacionamientoAdminController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PenalizacionController;
use App\Http\Controllers\Business\ReservaBusinessController;
use App\Http\Controllers\Business\ReportesBusinessController;
use App\Http\Controllers\Api\DashboardAdminController;
use App\Http\Controllers\Api\SystemMonitorController;

// Rutas públicas de autenticación
Route::post('/register', [UsuarioAuthController::class, 'register']);
Route::post('/login', [UsuarioAuthController::class, 'login']);

// Rutas de prueba
Route::get('/ping', function () {
    return response()->json(['pong' => true]);
});

Route::post('/test', function (Request $request) {
    return response()->json(['ok' => true, 'data' => $request->all()]);
});

// Endpoint temporal para probar tickets (sin autenticación)
Route::get('/test-tickets', function () {
    try {
        $tickets = App\Models\Ticket::with(['usuario', 'vehiculo', 'estacionamiento'])->get();
        return response()->json([
            'success' => true,
            'count' => $tickets->count(),
            'data' => $tickets
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// Endpoint temporal para finalizar tickets (sin autenticación)
Route::post('/test-tickets/{id}/finalize', function ($id) {
    try {
        $ticket = App\Models\Ticket::find($id);
        
        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket no encontrado'
            ], 404);
        }

        if ($ticket->estado !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden finalizar tickets activos'
            ], 400);
        }

        $ticket->update([
            'estado' => 'finalizado',
            'fecha_salida' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => $ticket,
            'message' => 'Ticket finalizado exitosamente'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// Endpoint temporal para reportar tickets (sin autenticación)
Route::post('/test-tickets/{id}/report', function ($id, Request $request) {
    try {
        $ticket = App\Models\Ticket::find($id);
        
        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket no encontrado'
            ], 404);
        }

        $reason = $request->input('reason', 'Sin motivo especificado');

        $ticket->update([
            'estado' => 'cancelado',
            'observaciones' => 'REPORTADO: ' . $reason,
            'fecha_salida' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => $ticket,
            'message' => 'Ticket reportado exitosamente'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// Test endpoints para debugging
Route::middleware('auth:sanctum')->get('/test-auth', function (Request $request) {
    $user = $request->user();
    return response()->json([
        'success' => true,
        'message' => 'Autenticado correctamente',
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'estado' => $user->estado
        ]
    ]);
});

Route::middleware(['auth:sanctum', 'role:admin'])->get('/test-admin', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'Acceso de admin confirmado',
        'user_role' => $request->user()->role
    ]);
});

// Test específico para el problema de cambio de rol
Route::middleware(['auth:sanctum', 'role:admin'])->put('/test-role/{id}', function (Request $request, $id) {
    Log::info('Test role endpoint hit', [
        'user_id' => $id,
        'request_all' => $request->all(),
        'request_json' => $request->json()->all(),
        'content_type' => $request->header('Content-Type'),
        'method' => $request->method()
    ]);
    
    return response()->json([
        'success' => true,
        'message' => 'Test endpoint funcionando',
        'received_data' => $request->all(),
        'user_id' => $id
    ]);
});

// Test simple para tickets
Route::middleware(['auth:sanctum'])->get('/test-tickets-simple', function () {
    try {
        $tickets = \App\Models\Ticket::limit(5)->get();
        return response()->json([
            'success' => true,
            'count' => $tickets->count(),
            'message' => 'Tickets obtenidos exitosamente'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

// Endpoint específico para cambiar rol - más simple
Route::middleware(['auth:sanctum', 'role:admin'])->patch('/usuarios/{id}/change-role', function (Request $request, $id) {
    try {
        Log::info('Change role endpoint called', [
            'user_id' => $id,
            'request_data' => $request->all()
        ]);

        $usuario = \App\Models\UsuarioReserva::find($id);
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // Obtener el rol del request (acepta 'rol' o 'role')
        $newRole = $request->input('role', $request->input('rol'));
        
        if ($newRole) {
            $newRole = strtolower($newRole);
        }

        if (!in_array($newRole, ['admin', 'registrador', 'reservador'])) {
            return response()->json([
                'success' => false,
                'message' => 'Rol inválido. Debe ser: admin, registrador o reservador'
            ], 422);
        }

        $usuario->update(['role' => $newRole]);

        return response()->json([
            'success' => true,
            'data' => $usuario->fresh(),
            'message' => 'Rol actualizado exitosamente'
        ]);

    } catch (Exception $e) {
        Log::error('Error changing role', [
            'user_id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Error interno del servidor'
        ], 500);
    }
});

// Endpoint de TEST para demostrar cómo debe funcionar el cambio de rol
Route::middleware(['auth:sanctum', 'role:admin'])->post('/test-cambiar-rol', function (Request $request) {
    try {
        $userId = $request->input('user_id', 1);
        $newRole = $request->input('role', 'admin');
        
        $usuario = \App\Models\UsuarioReserva::find($userId);
        
        if (!$usuario) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }
        
        $oldRole = $usuario->role;
        $usuario->update(['role' => $newRole]);
        
        return response()->json([
            'success' => true,
            'message' => 'Rol cambiado exitosamente',
            'user_id' => $userId,
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'updated_user' => $usuario->fresh()
        ]);
        
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

    // Rutas protegidas por autenticación
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [UsuarioAuthController::class, 'logout']);
    Route::get('/me', [UsuarioAuthController::class, 'me']);
    
    // Dashboard de Administrador (Solo admin)
    Route::prefix('admin/dashboard')->middleware('role:admin')->group(function () {
        Route::get('/stats', [DashboardAdminController::class, 'getStats']);
        Route::get('/estacionamientos', [DashboardAdminController::class, 'getEstacionamientosStats']);
        Route::get('/resumen', [DashboardAdminController::class, 'getResumen']);
    });
    
    // Business Logic Routes (Nuevas rutas de lógica de negocio)
    Route::prefix('business')->group(function () {
        // Reservas con lógica de negocio
        Route::post('/reservas', [ReservaBusinessController::class, 'crearReserva']);
        Route::post('/reservas/{ticketId}/finalizar', [ReservaBusinessController::class, 'finalizarReserva']);
        Route::post('/reservas/{ticketId}/cancelar', [ReservaBusinessController::class, 'cancelarReserva']);
        
        // Cálculos y estimaciones
        Route::post('/calcular-precio', [ReservaBusinessController::class, 'calcularPrecioEstimado']);
        Route::get('/estacionamientos/disponibles', [ReservaBusinessController::class, 'buscarEstacionamientosDisponibles']);
        
        // Penalizaciones
        Route::post('/penalizaciones/aplicar', [ReservaBusinessController::class, 'aplicarPenalizacion']);
        
        // Pagos y reembolsos
        Route::get('/usuarios/{usuarioId}/pagos', [ReservaBusinessController::class, 'getHistorialPagos']);
        Route::post('/pagos/{pagoId}/reembolsar', [ReservaBusinessController::class, 'reembolsarPago']);
        Route::post('/tickets/{ticketId}/pago-manual', [ReservaBusinessController::class, 'procesarPagoManual']);
        
        // Reportes y resúmenes
        Route::get('/usuarios/{usuarioId}/resumen', [ReservaBusinessController::class, 'getResumenUsuario']);
        Route::get('/estacionamientos/{estacionamientoId}/reporte', [ReservaBusinessController::class, 'getReporteOcupacion']);
        
        // Nuevos endpoints de reportes avanzados
        Route::get('/reportes/ingresos', [ReportesBusinessController::class, 'getIncomeReport']);
        Route::get('/reportes/estadisticas', [ReportesBusinessController::class, 'getReservationStats']);
        Route::get('/reportes/por-estado', [ReportesBusinessController::class, 'getReservationsByStatus']);
    });
    
    // Tickets routes (CRUD básico)
    Route::get('/tickets', [TicketController::class, 'index'])->middleware('role:admin');
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    Route::put('/tickets/{id}', [TicketController::class, 'update']);
    Route::delete('/tickets/{id}', [TicketController::class, 'destroy']);
    Route::get('/tickets/active/list', [TicketController::class, 'getActiveTickets']);
    Route::get('/tickets/user/{userId}', [TicketController::class, 'getTicketsByUser']);
    Route::get('/tickets/code/{codigo}', [TicketController::class, 'getTicketByCode']);
    Route::post('/tickets/{id}/finalize', [TicketController::class, 'finalizeTicket']);
    
    // Usuarios routes
    Route::get('/usuarios', [UsuarioReservaController::class, 'index'])->middleware('role:admin');
    Route::post('/usuarios', [UsuarioReservaController::class, 'store'])->middleware('role:admin');
    Route::get('/usuarios/{id}', [UsuarioReservaController::class, 'show'])->middleware('role:admin,registrador');
    Route::put('/usuarios/{id}', [UsuarioReservaController::class, 'update'])->middleware('role:admin');
    Route::delete('/usuarios/{id}', [UsuarioReservaController::class, 'destroy'])->middleware('role:admin');
    Route::get('/usuarios/email/{email}', [UsuarioReservaController::class, 'getUserByEmail'])->middleware('role:admin');
    Route::get('/usuarios/active/list', [UsuarioReservaController::class, 'getActiveUsers'])->middleware('role:admin,registrador');
    Route::put('/usuarios/{id}/role', [UsuarioReservaController::class, 'updateRole'])->middleware('role:admin');
    
    // Nuevas rutas para gestión de roles
    Route::get('/usuarios/role/{role}', [UsuarioReservaController::class, 'getUsersByRole'])->middleware('role:admin');
    Route::put('/usuarios/{id}/role', [UsuarioReservaController::class, 'updateRole'])->middleware('role:admin');
    Route::get('/usuarios/stats/roles', [UsuarioReservaController::class, 'getRoleStats'])->middleware('role:admin');
    
    // System Monitor Routes
    Route::get('/system/stats', [SystemMonitorController::class, 'getSystemStats'])->middleware('role:admin');
    Route::post('/system/test-websocket', [SystemMonitorController::class, 'testWebSocket']);
    
    // Estacionamientos routes
    Route::get('/estacionamientos', [EstacionamientoAdminController::class, 'index'])->middleware('auth:sanctum');
    Route::post('/estacionamientos', [EstacionamientoAdminController::class, 'store'])->middleware('auth:sanctum')->middleware('role:admin,registrador');
    Route::get('/estacionamientos/{id}', [EstacionamientoAdminController::class, 'show'])->middleware('auth:sanctum');
    Route::put('/estacionamientos/{id}', [EstacionamientoAdminController::class, 'update'])->middleware('auth:sanctum')->middleware('role:admin,registrador');
    Route::delete('/estacionamientos/{id}', [EstacionamientoAdminController::class, 'destroy'])->middleware('auth:sanctum')->middleware('role:admin,registrador');
    Route::get('/estacionamientos/email/{email}', [EstacionamientoAdminController::class, 'getEstacionamientoByEmail'])->middleware('auth:sanctum');
    Route::get('/estacionamientos/available/spaces', [EstacionamientoAdminController::class, 'getEstacionamientosConEspacios'])->middleware('auth:sanctum');
    Route::post('/estacionamientos/{id}/update-spaces', [EstacionamientoAdminController::class, 'updateEspaciosDisponibles'])->middleware('auth:sanctum')->middleware('role:admin,registrador');
    Route::post('/estacionamientos/{id}/increment-reservations', [EstacionamientoAdminController::class, 'incrementarReservas'])->middleware('auth:sanctum')->middleware('role:admin,registrador');
    
    // Vehiculos routes
    Route::get('/vehiculos', [VehiculoController::class, 'index']);
    Route::post('/vehiculos', [VehiculoController::class, 'store']);
    Route::get('/vehiculos/{placa}', [VehiculoController::class, 'show']);
    Route::put('/vehiculos/{placa}', [VehiculoController::class, 'update']);
    Route::delete('/vehiculos/{placa}', [VehiculoController::class, 'destroy']);
    Route::get('/vehiculos/placa/{placa}', [VehiculoController::class, 'getVehicleByPlaca']);
    Route::get('/vehiculos/user/{userId}', [VehiculoController::class, 'getVehiclesByUser']);
    
    // Pagos routes
    Route::get('/pagos', [PagoController::class, 'index']);
    Route::post('/pagos', [PagoController::class, 'store']);
    Route::get('/pagos/{id}', [PagoController::class, 'show']);
    Route::put('/pagos/{id}', [PagoController::class, 'update']);
    Route::delete('/pagos/{id}', [PagoController::class, 'destroy']);
    Route::post('/pagos/process', [PagoController::class, 'processPayment']);
    Route::get('/pagos/ticket/{ticketId}/history', [PagoController::class, 'getPaymentHistory']);
    
    // Penalizaciones routes
    Route::get('/penalizaciones', [PenalizacionController::class, 'index']);
    Route::post('/penalizaciones', [PenalizacionController::class, 'store']);
    Route::get('/penalizaciones/{id}', [PenalizacionController::class, 'show']);
    Route::put('/penalizaciones/{id}', [PenalizacionController::class, 'update']);
    Route::delete('/penalizaciones/{id}', [PenalizacionController::class, 'destroy']);
    Route::get('/penalizaciones/user/{userId}', [PenalizacionController::class, 'getPenalizationsByUser']);
    Route::post('/penalizaciones/apply', [PenalizacionController::class, 'applyPenalty']);
    Route::post('/penalizaciones/{id}/pay', [PenalizacionController::class, 'payPenalty']);

    // System Monitor WebSocket routes
    Route::get('/system/status', [SystemMonitorController::class, 'getStatus']);
    Route::get('/system/health', [SystemMonitorController::class, 'healthCheck']);
    Route::post('/system/broadcast', [SystemMonitorController::class, 'broadcastTestEvent']);
    Route::get('/system/stats', [SystemMonitorController::class, 'getSystemStats']);
});

// Manteniendo compatibilidad con rutas existentes
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});