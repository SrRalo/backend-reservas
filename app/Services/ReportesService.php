<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReportesService
{
    /**
     * Obtiene el reporte de ingresos para un usuario específico
     * 
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @param string $groupBy
     * @return array
     */
    public function getIncomeReport(int $userId, string $startDate, string $endDate, string $groupBy = 'day'): array
    {
        try {
            Log::info("Ejecutando consulta de reporte de ingresos", [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'group_by' => $groupBy
            ]);

            // Determinar el formato de agrupación según el parámetro
            $dateFormat = $this->getDateFormat($groupBy);
            $dateColumn = $this->getDateColumn($groupBy);

            // Consulta principal para obtener ingresos agrupados
            $query = DB::table('tickets')
                ->select([
                    DB::raw("DATE({$dateColumn}) as date"),
                    DB::raw('SUM(precio_total) as amount'),
                    DB::raw('COUNT(*) as reservation_count')
                ])
                ->where('usuario_id', $userId)
                ->whereIn('estado', ['finalizado', 'pagado'])
                ->whereNotNull('precio_total')
                ->where('precio_total', '>', 0)
                ->whereBetween(DB::raw("DATE({$dateColumn})"), [$startDate, $endDate])
                ->groupBy(DB::raw("DATE({$dateColumn})"))
                ->orderBy(DB::raw("DATE({$dateColumn})"), 'asc');

            $results = $query->get();

            Log::info("Resultados de consulta de reportes", [
                'count' => $results->count(),
                'sql' => $query->toSql()
            ]);

            // Transformar los resultados al formato esperado
            $reportData = [];
            foreach ($results as $result) {
                $reportData[] = [
                    'date' => $result->date,
                    'amount' => (float) $result->amount,
                    'reservation_count' => (int) $result->reservation_count
                ];
            }

            // Si no hay datos, llenar con días vacíos para el período solicitado
            if (empty($reportData)) {
                $reportData = $this->fillEmptyPeriod($startDate, $endDate, $groupBy);
            }

            return $reportData;

        } catch (\Exception $e) {
            Log::error("Error en getIncomeReport: " . $e->getMessage(), [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Obtiene estadísticas generales de reservas para un usuario
     * 
     * @param int $userId
     * @param string $period
     * @return array
     */
    public function getReservationStats(int $userId, string $period = 'month'): array
    {
        try {
            $dateRange = $this->getPeriodDateRange($period);
            
            $stats = DB::table('tickets')
                ->select([
                    DB::raw('COUNT(*) as total_reservations'),
                    DB::raw('SUM(CASE WHEN estado = "activo" THEN 1 ELSE 0 END) as active_reservations'),
                    DB::raw('SUM(CASE WHEN estado IN ("finalizado", "pagado") THEN 1 ELSE 0 END) as completed_reservations'),
                    DB::raw('SUM(CASE WHEN estado = "cancelado" THEN 1 ELSE 0 END) as cancelled_reservations'),
                    DB::raw('SUM(CASE WHEN estado IN ("finalizado", "pagado") THEN precio_total ELSE 0 END) as total_income'),
                    DB::raw('AVG(CASE WHEN estado IN ("finalizado", "pagado") AND precio_total > 0 THEN precio_total ELSE NULL END) as average_income')
                ])
                ->where('usuario_id', $userId)
                ->whereBetween('fecha_entrada', [$dateRange['start'], $dateRange['end']])
                ->first();

            return [
                'period' => $period,
                'date_range' => $dateRange,
                'total_reservations' => (int) $stats->total_reservations,
                'active_reservations' => (int) $stats->active_reservations,
                'completed_reservations' => (int) $stats->completed_reservations,
                'cancelled_reservations' => (int) $stats->cancelled_reservations,
                'total_income' => round((float) $stats->total_income, 2),
                'average_income' => round((float) $stats->average_income ?? 0, 2)
            ];

        } catch (\Exception $e) {
            Log::error("Error en getReservationStats: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene el reporte de reservas agrupadas por estado
     * 
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getReservationsByStatus(int $userId, string $startDate, string $endDate): array
    {
        try {
            $results = DB::table('tickets')
                ->select([
                    'estado as status',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(CASE WHEN precio_total > 0 THEN precio_total ELSE 0 END) as total_amount')
                ])
                ->where('usuario_id', $userId)
                ->whereBetween(DB::raw('DATE(fecha_entrada)'), [$startDate, $endDate])
                ->groupBy('estado')
                ->get();

            $statusReport = [];
            foreach ($results as $result) {
                $statusReport[] = [
                    'status' => $result->status,
                    'count' => (int) $result->count,
                    'total_amount' => round((float) $result->total_amount, 2)
                ];
            }

            return $statusReport;

        } catch (\Exception $e) {
            Log::error("Error en getReservationsByStatus: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene el formato de fecha según el tipo de agrupación
     */
    private function getDateFormat(string $groupBy): string
    {
        return match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };
    }

    /**
     * Obtiene la columna de fecha a usar según el tipo de agrupación
     */
    private function getDateColumn(string $groupBy): string
    {
        return match($groupBy) {
            'day', 'week', 'month' => 'fecha_entrada',
            default => 'fecha_entrada'
        };
    }

    /**
     * Obtiene el rango de fechas para un período específico
     */
    private function getPeriodDateRange(string $period): array
    {
        $end = Carbon::now();
        
        $start = match($period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonth()
        };

        return [
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Llena un período vacío con datos de cero
     */
    private function fillEmptyPeriod(string $startDate, string $endDate, string $groupBy): array
    {
        $data = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            $data[] = [
                'date' => $current->format('Y-m-d'),
                'amount' => 0.0,
                'reservation_count' => 0
            ];
            
            $current = match($groupBy) {
                'day' => $current->addDay(),
                'week' => $current->addWeek(),
                'month' => $current->addMonth(),
                default => $current->addDay()
            };
        }

        return $data;
    }
}
