<?php

namespace App\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardAdminService extends BaseService
{
    public function __construct()
    {
        // No repository needed for dashboard aggregations
    }

    /**
     * Obtener todas las estadísticas del dashboard de administrador
     */
    public function getDashboardStats(?string $periodo = 'semana'): array
    {
        try {
            $fechas = $this->obtenerRangoFechas($periodo);
            
            return [
                'success' => true,
                'data' => [
                    'metricas_principales' => $this->getMetricasPrincipales($fechas),
                    'ingresos_periodo' => $this->getIngresosPorDia($fechas),
                    'estado_reservas' => $this->getEstadoReservas($fechas),
                    'estado_plazas' => $this->getEstadoPlazas(),
                    'metricas_rendimiento' => $this->getMetricasRendimiento($fechas),
                    'periodo' => [
                        'tipo' => $periodo,
                        'fecha_inicio' => $fechas['inicio']->format('Y-m-d'),
                        'fecha_fin' => $fechas['fin']->format('Y-m-d')
                    ]
                ],
                'message' => 'Estadísticas del dashboard obtenidas exitosamente'
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas del dashboard: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Obtener métricas principales del dashboard
     */
    private function getMetricasPrincipales(array $fechas): array
    {
        // Total de ingresos del período
        $totalIngresos = DB::table('tickets')
            ->whereIn('estado', ['finalizado', 'pagado'])
            ->whereNotNull('precio_total')
            ->whereBetween('fecha_entrada', [$fechas['inicio'], $fechas['fin']])
            ->sum('precio_total') ?? 0;

        // Total de reservas del período
        $totalReservas = DB::table('tickets')
            ->whereBetween('fecha_entrada', [$fechas['inicio'], $fechas['fin']])
            ->count();

        // Total de plazas en el sistema
        $plazasTotales = DB::table('estacionamiento_admin')
            ->where('estado', 'activo')
            ->sum('espacios_totales') ?? 0;

        // Calcular tasa de ocupación actual
        $plazasOcupadas = DB::table('estacionamiento_admin')
            ->where('estado', 'activo')
            ->sum(DB::raw('espacios_totales - espacios_disponibles')) ?? 0;

        $tasaOcupacion = $plazasTotales > 0 ? ($plazasOcupadas / $plazasTotales) * 100 : 0;

        return [
            'total_ingresos' => round($totalIngresos, 2),
            'total_reservas' => $totalReservas,
            'plazas_totales' => $plazasTotales,
            'tasa_ocupacion' => round($tasaOcupacion, 1)
        ];
    }

    /**
     * Obtener ingresos por día para el gráfico
     */
    private function getIngresosPorDia(array $fechas): array
    {
        $ingresosPorDia = DB::table('tickets')
            ->select([
                DB::raw('DATE(fecha_entrada) as fecha'),
                DB::raw('COALESCE(SUM(precio_total), 0) as ingresos')
            ])
            ->whereIn('estado', ['finalizado', 'pagado'])
            ->whereNotNull('precio_total')
            ->whereBetween('fecha_entrada', [$fechas['inicio'], $fechas['fin']])
            ->groupBy(DB::raw('DATE(fecha_entrada)'))
            ->orderBy('fecha', 'asc')
            ->get();

        // Llenar días vacíos con 0
        $resultado = [];
        $fechaActual = $fechas['inicio']->copy();
        
        while ($fechaActual->lte($fechas['fin'])) {
            $fechaStr = $fechaActual->format('Y-m-d');
            $ingreso = $ingresosPorDia->firstWhere('fecha', $fechaStr);
            
            $resultado[] = [
                'fecha' => $fechaStr,
                'ingresos' => $ingreso ? round($ingreso->ingresos, 2) : 0
            ];
            
            $fechaActual->addDay();
        }

        return $resultado;
    }

    /**
     * Obtener estado de reservas para el gráfico circular
     */
    private function getEstadoReservas(array $fechas): array
    {
        $estados = DB::table('tickets')
            ->select(['estado', DB::raw('COUNT(*) as cantidad')])
            ->whereBetween('fecha_entrada', [$fechas['inicio'], $fechas['fin']])
            ->groupBy('estado')
            ->get();

        $resultado = [];
        foreach ($estados as $estado) {
            $resultado[] = [
                'estado' => $estado->estado,
                'cantidad' => $estado->cantidad,
                'porcentaje' => 0 // Se calculará en el frontend
            ];
        }

        // Calcular porcentajes
        $total = collect($resultado)->sum('cantidad');
        if ($total > 0) {
            foreach ($resultado as &$item) {
                $item['porcentaje'] = round(($item['cantidad'] / $total) * 100, 1);
            }
        }

        return $resultado;
    }

    /**
     * Obtener estado actual de las plazas
     */
    private function getEstadoPlazas(): array
    {
        $estacionamientos = DB::table('estacionamiento_admin')
            ->select([
                'espacios_totales',
                'espacios_disponibles',
                'estado'
            ])
            ->where('estado', 'activo')
            ->get();

        $disponibles = 0;
        $ocupadas = 0;
        $enMantenimiento = 0;
        $totales = 0;

        foreach ($estacionamientos as $est) {
            if ($est->estado === 'activo') {
                $disponibles += $est->espacios_disponibles;
                $ocupadas += ($est->espacios_totales - $est->espacios_disponibles);
                $totales += $est->espacios_totales;
            } elseif ($est->estado === 'mantenimiento') {
                $enMantenimiento += $est->espacios_totales;
                $totales += $est->espacios_totales;
            }
        }

        return [
            'disponibles' => $disponibles,
            'ocupadas' => $ocupadas,
            'en_mantenimiento' => $enMantenimiento,
            'total' => $totales
        ];
    }

    /**
     * Obtener métricas de rendimiento
     */
    private function getMetricasRendimiento(array $fechas): array
    {
        // Ingreso promedio por reserva
        $ingresoPromedio = DB::table('tickets')
            ->whereIn('estado', ['finalizado', 'pagado'])
            ->whereNotNull('precio_total')
            ->whereBetween('fecha_entrada', [$fechas['inicio'], $fechas['fin']])
            ->avg('precio_total') ?? 0;

        // Tasa de finalización (reservas completadas vs total)
        $totalReservas = DB::table('tickets')
            ->whereBetween('fecha_entrada', [$fechas['inicio'], $fechas['fin']])
            ->count();

        $reservasCompletadas = DB::table('tickets')
            ->whereIn('estado', ['finalizado', 'pagado'])
            ->whereBetween('fecha_entrada', [$fechas['inicio'], $fechas['fin']])
            ->count();

        $tasaFinalizacion = $totalReservas > 0 ? ($reservasCompletadas / $totalReservas) * 100 : 0;

        // Reservas activas actuales
        $reservasActivas = DB::table('tickets')
            ->where('estado', 'activo')
            ->count();

        return [
            'ingreso_promedio' => round($ingresoPromedio, 2),
            'tasa_finalizacion' => round($tasaFinalizacion, 1),
            'reservas_activas' => $reservasActivas
        ];
    }

    /**
     * Obtener rango de fechas según el período
     */
    private function obtenerRangoFechas(string $periodo): array
    {
        $fin = Carbon::now()->endOfDay();
        
        switch ($periodo) {
            case 'hoy':
                $inicio = Carbon::now()->startOfDay();
                break;
            case 'semana':
                $inicio = Carbon::now()->subDays(6)->startOfDay();
                break;
            case 'mes':
                $inicio = Carbon::now()->subDays(29)->startOfDay();
                break;
            case 'año':
                $inicio = Carbon::now()->subDays(364)->startOfDay();
                break;
            default:
                $inicio = Carbon::now()->subDays(6)->startOfDay();
        }

        return [
            'inicio' => $inicio,
            'fin' => $fin
        ];
    }

    /**
     * Obtener estadísticas por estacionamiento
     */
    public function getEstadisticasEstacionamientos(): array
    {
        try {
            $estacionamientos = DB::table('estacionamiento_admin as e')
                ->leftJoin('tickets as t', 'e.id', '=', 't.estacionamiento_id')
                ->select([
                    'e.id',
                    'e.nombre',
                    'e.direccion',
                    'e.espacios_totales',
                    'e.espacios_disponibles',
                    'e.precio_por_hora',
                    'e.precio_mensual',
                    DB::raw('COUNT(t.id) as total_reservas'),
                    DB::raw('SUM(CASE WHEN t.estado IN ("finalizado", "pagado") THEN COALESCE(t.precio_total, 0) ELSE 0 END) as ingresos_totales')
                ])
                ->where('e.estado', 'activo')
                ->groupBy('e.id', 'e.nombre', 'e.direccion', 'e.espacios_totales', 'e.espacios_disponibles', 'e.precio_por_hora', 'e.precio_mensual')
                ->get();

            $resultado = [];
            foreach ($estacionamientos as $est) {
                $ocupacion = $est->espacios_totales > 0 
                    ? (($est->espacios_totales - $est->espacios_disponibles) / $est->espacios_totales) * 100 
                    : 0;

                $resultado[] = [
                    'id' => $est->id,
                    'nombre' => $est->nombre,
                    'direccion' => $est->direccion,
                    'espacios_totales' => $est->espacios_totales,
                    'espacios_disponibles' => $est->espacios_disponibles,
                    'espacios_ocupados' => $est->espacios_totales - $est->espacios_disponibles,
                    'porcentaje_ocupacion' => round($ocupacion, 1),
                    'precio_por_hora' => $est->precio_por_hora,
                    'precio_mensual' => $est->precio_mensual,
                    'total_reservas' => $est->total_reservas,
                    'ingresos_totales' => round($est->ingresos_totales, 2)
                ];
            }

            return [
                'success' => true,
                'data' => $resultado,
                'message' => 'Estadísticas por estacionamiento obtenidas exitosamente'
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas de estacionamientos: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }
}
