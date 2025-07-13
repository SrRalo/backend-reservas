<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\ReportesService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReportesBusinessController extends Controller
{
    private ReportesService $reportesService;

    public function __construct(ReportesService $reportesService)
    {
        $this->reportesService = $reportesService;
    }

    /**
     * Obtiene el reporte de ingresos para un usuario/registrador específico
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getIncomeReport(Request $request): JsonResponse
    {
        try {
            // Validar parámetros de entrada
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:usuario_reserva,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'group_by' => 'in:day,week,month|nullable', // opcional: agrupar por día, semana o mes
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Parámetros inválidos',
                    'details' => $validator->errors()
                ], 400);
            }

            $userId = $request->input('user_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $groupBy = $request->input('group_by', 'day'); // Por defecto agrupar por día

            Log::info("Generando reporte de ingresos", [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'group_by' => $groupBy
            ]);

            // Obtener el reporte desde el servicio
            $reportData = $this->reportesService->getIncomeReport($userId, $startDate, $endDate, $groupBy);

            // Calcular totales
            $totalIncome = collect($reportData)->sum('amount');
            $totalReservations = collect($reportData)->sum('reservation_count');
            $averageIncome = $totalReservations > 0 ? $totalIncome / $totalReservations : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'report_data' => $reportData,
                    'summary' => [
                        'total_income' => round($totalIncome, 2),
                        'total_reservations' => $totalReservations,
                        'average_income' => round($averageIncome, 2),
                        'period' => [
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'group_by' => $groupBy
                        ]
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error al generar reporte de ingresos: " . $e->getMessage(), [
                'user_id' => $request->input('user_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor al generar el reporte',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas generales de reservas para un usuario
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getReservationStats(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:usuario_reserva,id',
                'period' => 'in:today,week,month,year|nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Parámetros inválidos',
                    'details' => $validator->errors()
                ], 400);
            }

            $userId = $request->input('user_id');
            $period = $request->input('period', 'month');

            $stats = $this->reportesService->getReservationStats($userId, $period);

            return response()->json([
                'success' => true,
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error al obtener estadísticas de reservas: " . $e->getMessage());

            return response()->json([
                'error' => 'Error interno del servidor al obtener estadísticas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene el reporte de reservas por estado para un usuario
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getReservationsByStatus(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:usuario_reserva,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Parámetros inválidos',
                    'details' => $validator->errors()
                ], 400);
            }

            $userId = $request->input('user_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $statusReport = $this->reportesService->getReservationsByStatus($userId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $statusReport
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error al obtener reporte por estado: " . $e->getMessage());

            return response()->json([
                'error' => 'Error interno del servidor al obtener reporte por estado',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
