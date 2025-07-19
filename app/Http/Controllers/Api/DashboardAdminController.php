<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardAdminService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Dashboard Admin",
 *     description="Operaciones del dashboard de administrador"
 * )
 */
class DashboardAdminController extends Controller
{
    private DashboardAdminService $dashboardService;

    public function __construct(DashboardAdminService $dashboardService)
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin');
        $this->dashboardService = $dashboardService;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/dashboard/stats",
     *     summary="Obtener estadísticas del dashboard de administrador",
     *     tags={"Dashboard Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="periodo",
     *         in="query",
     *         description="Período para las estadísticas",
     *         required=false,
     *         @OA\Schema(type="string", enum={"hoy", "semana", "mes", "año"}, example="semana")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estadísticas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Estadísticas obtenidas exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="metricas_principales",
     *                     type="object",
     *                     @OA\Property(property="total_ingresos", type="number", example=1250.75),
     *                     @OA\Property(property="total_reservas", type="integer", example=45),
     *                     @OA\Property(property="plazas_totales", type="integer", example=100),
     *                     @OA\Property(property="tasa_ocupacion", type="number", example=85.5)
     *                 ),
     *                 @OA\Property(
     *                     property="ingresos_periodo",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="fecha", type="string", example="2025-01-15"),
     *                         @OA\Property(property="ingresos", type="number", example=125.50)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="estado_reservas",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="estado", type="string", example="finalizado"),
     *                         @OA\Property(property="cantidad", type="integer", example=25),
     *                         @OA\Property(property="porcentaje", type="number", example=55.6)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="estado_plazas",
     *                     type="object",
     *                     @OA\Property(property="disponibles", type="integer", example=25),
     *                     @OA\Property(property="ocupadas", type="integer", example=70),
     *                     @OA\Property(property="en_mantenimiento", type="integer", example=5),
     *                     @OA\Property(property="total", type="integer", example=100)
     *                 ),
     *                 @OA\Property(
     *                     property="metricas_rendimiento",
     *                     type="object",
     *                     @OA\Property(property="ingreso_promedio", type="number", example=27.79),
     *                     @OA\Property(property="tasa_finalizacion", type="number", example=88.9),
     *                     @OA\Property(property="reservas_activas", type="integer", example=12)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No autorizado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado - Solo administradores",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Acceso denegado")
     *         )
     *     )
     * )
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $periodo = $request->get('periodo', 'semana');

            // Validar período
            if (!in_array($periodo, ['hoy', 'semana', 'mes', 'año'])) {
                return $this->errorResponse('Período inválido. Debe ser: hoy, semana, mes o año', 400);
            }

            $stats = $this->dashboardService->getDashboardStats($periodo);

            if (!$stats['success']) {
                return $this->errorResponse($stats['message'], 500);
            }

            return $this->successResponse($stats['data'], $stats['message']);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas del dashboard: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/dashboard/estacionamientos",
     *     summary="Obtener estadísticas por estacionamiento",
     *     tags={"Dashboard Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estadísticas por estacionamiento obtenidas exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Estadísticas obtenidas exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nombre", type="string", example="Estacionamiento Centro"),
     *                     @OA\Property(property="direccion", type="string", example="Av. Principal 123"),
     *                     @OA\Property(property="espacios_totales", type="integer", example=50),
     *                     @OA\Property(property="espacios_disponibles", type="integer", example=12),
     *                     @OA\Property(property="espacios_ocupados", type="integer", example=38),
     *                     @OA\Property(property="porcentaje_ocupacion", type="number", example=76.0),
     *                     @OA\Property(property="precio_por_hora", type="number", example=5.00),
     *                     @OA\Property(property="precio_mensual", type="number", example=120.00),
     *                     @OA\Property(property="total_reservas", type="integer", example=245),
     *                     @OA\Property(property="ingresos_totales", type="number", example=3250.75)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getEstacionamientosStats(): JsonResponse
    {
        try {
            $stats = $this->dashboardService->getEstadisticasEstacionamientos();

            if (!$stats['success']) {
                return $this->errorResponse($stats['message'], 500);
            }

            return $this->successResponse($stats['data'], $stats['message']);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas de estacionamientos: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/dashboard/resumen",
     *     summary="Obtener resumen rápido para widgets del dashboard",
     *     tags={"Dashboard Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Resumen obtenido exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Resumen obtenido exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_ingresos", type="number", example=1250.75),
     *                 @OA\Property(property="total_reservas", type="integer", example=45),
     *                 @OA\Property(property="plazas_totales", type="integer", example=100),
     *                 @OA\Property(property="tasa_ocupacion", type="number", example=85.5),
     *                 @OA\Property(property="reservas_activas", type="integer", example=12),
     *                 @OA\Property(property="ingresos_hoy", type="number", example=175.25)
     *             )
     *         )
     *     )
     * )
     */
    public function getResumen(): JsonResponse
    {
        try {
            // Obtener métricas de hoy y de la semana para comparación
            $statsHoy = $this->dashboardService->getDashboardStats('hoy');
            $statsSemana = $this->dashboardService->getDashboardStats('semana');

            if (!$statsHoy['success'] || !$statsSemana['success']) {
                return $this->errorResponse('Error obteniendo estadísticas', 500);
            }

            $resumen = [
                'total_ingresos' => $statsSemana['data']['metricas_principales']['total_ingresos'],
                'total_reservas' => $statsSemana['data']['metricas_principales']['total_reservas'],
                'plazas_totales' => $statsSemana['data']['metricas_principales']['plazas_totales'],
                'tasa_ocupacion' => $statsSemana['data']['metricas_principales']['tasa_ocupacion'],
                'reservas_activas' => $statsSemana['data']['metricas_rendimiento']['reservas_activas'],
                'ingresos_hoy' => $statsHoy['data']['metricas_principales']['total_ingresos']
            ];

            return $this->successResponse($resumen, 'Resumen obtenido exitosamente');

        } catch (\Exception $e) {
            Log::error('Error obteniendo resumen del dashboard: ' . $e->getMessage());
            return $this->handleException($e);
        }
    }

    /**
     * Return a success response
     */
    private function successResponse($data = null, string $message = 'Operación exitosa', int $status = 200): JsonResponse
    {
        $response = ['success' => true, 'message' => $message];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return response()->json($response, $status);
    }

    /**
     * Return an error response
     */
    private function errorResponse(string $message = 'Error en la operación', int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $status);
    }

    /**
     * Handle exceptions and return appropriate response
     */
    private function handleException(\Exception $e): JsonResponse
    {
        Log::error('API Exception: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Registro no encontrado', 404);
        }

        return $this->errorResponse('Error interno del servidor: ' . $e->getMessage(), 500);
    }
}
