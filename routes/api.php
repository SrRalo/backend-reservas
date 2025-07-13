<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioAuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UsuarioReservaController;
use App\Http\Controllers\EstacionamientoAdminController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PenalizacionController;
use App\Http\Controllers\Business\ReservaBusinessController;
use App\Http\Controllers\Business\ReportesBusinessController;

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

// Rutas protegidas por autenticación
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [UsuarioAuthController::class, 'logout']);
    Route::get('/me', [UsuarioAuthController::class, 'me']);
    
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
    Route::get('/tickets', [TicketController::class, 'index']);
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
    
    // Nuevas rutas para gestión de roles
    Route::get('/usuarios/role/{role}', [UsuarioReservaController::class, 'getUsersByRole'])->middleware('role:admin');
    Route::put('/usuarios/{id}/role', [UsuarioReservaController::class, 'updateRole'])->middleware('role:admin');
    Route::get('/usuarios/stats/roles', [UsuarioReservaController::class, 'getRoleStats'])->middleware('role:admin');
    
    // Estacionamientos routes
    Route::get('/estacionamientos', [EstacionamientoAdminController::class, 'index']);
    Route::post('/estacionamientos', [EstacionamientoAdminController::class, 'store']);
    Route::get('/estacionamientos/{id}', [EstacionamientoAdminController::class, 'show']);
    Route::put('/estacionamientos/{id}', [EstacionamientoAdminController::class, 'update']);
    Route::delete('/estacionamientos/{id}', [EstacionamientoAdminController::class, 'destroy']);
    Route::get('/estacionamientos/email/{email}', [EstacionamientoAdminController::class, 'getEstacionamientoByEmail']);
    Route::get('/estacionamientos/available/spaces', [EstacionamientoAdminController::class, 'getEstacionamientosConEspacios']);
    Route::post('/estacionamientos/{id}/update-spaces', [EstacionamientoAdminController::class, 'updateEspaciosDisponibles']);
    Route::post('/estacionamientos/{id}/increment-reservations', [EstacionamientoAdminController::class, 'incrementarReservas']);
    
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
});

// Manteniendo compatibilidad con rutas existentes
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});