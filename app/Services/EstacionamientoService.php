<?php

namespace App\Services;

use App\Repositories\Interfaces\EstacionamientoAdminRepositoryInterface;
use Illuminate\Support\Facades\Log;

class EstacionamientoService
{
    private EstacionamientoAdminRepositoryInterface $estacionamientoRepository;

    public function __construct(EstacionamientoAdminRepositoryInterface $estacionamientoRepository)
    {
        $this->estacionamientoRepository = $estacionamientoRepository;
    }

    /**
     * Verificar disponibilidad de espacios
     */
    public function verificarDisponibilidad(int $estacionamientoId, int $espaciosRequeridos = 1): bool
    {
        $estacionamiento = $this->estacionamientoRepository->find($estacionamientoId);
        
        if (!$estacionamiento || $estacionamiento->estado !== 'activo') {
            return false;
        }

        return $estacionamiento->espacios_disponibles >= $espaciosRequeridos;
    }

    /**
     * Reducir espacios disponibles
     */
    public function reducirEspaciosDisponibles(int $estacionamientoId, int $espacios = 1): bool
    {
        try {
            $estacionamiento = $this->estacionamientoRepository->find($estacionamientoId);
            
            if (!$estacionamiento) {
                Log::error('Estacionamiento no encontrado al reducir espacios', ['id' => $estacionamientoId]);
                return false;
            }

            $nuevosEspacios = max(0, $estacionamiento->espacios_disponibles - $espacios);
            
            return $this->estacionamientoRepository->updateEspaciosDisponibles($estacionamientoId, $nuevosEspacios);
            
        } catch (\Exception $e) {
            Log::error('Error reduciendo espacios disponibles: ' . $e->getMessage(), [
                'estacionamiento_id' => $estacionamientoId,
                'espacios' => $espacios
            ]);
            return false;
        }
    }

    /**
     * Liberar espacio (incrementar espacios disponibles)
     */
    public function liberarEspacio(int $estacionamientoId, int $espacios = 1): bool
    {
        try {
            $estacionamiento = $this->estacionamientoRepository->find($estacionamientoId);
            
            if (!$estacionamiento) {
                Log::error('Estacionamiento no encontrado al liberar espacios', ['id' => $estacionamientoId]);
                return false;
            }

            $nuevosEspacios = min(
                $estacionamiento->espacios_totales, 
                $estacionamiento->espacios_disponibles + $espacios
            );
            
            return $this->estacionamientoRepository->updateEspaciosDisponibles($estacionamientoId, $nuevosEspacios);
            
        } catch (\Exception $e) {
            Log::error('Error liberando espacios: ' . $e->getMessage(), [
                'estacionamiento_id' => $estacionamientoId,
                'espacios' => $espacios
            ]);
            return false;
        }
    }

    /**
     * Obtener ocupación actual del estacionamiento
     */
    public function getOcupacionActual(int $estacionamientoId): array
    {
        $estacionamiento = $this->estacionamientoRepository->find($estacionamientoId);
        
        if (!$estacionamiento) {
            return [
                'error' => 'Estacionamiento no encontrado'
            ];
        }

        $espaciosOcupados = $estacionamiento->espacios_totales - $estacionamiento->espacios_disponibles;
        $porcentajeOcupacion = ($espaciosOcupados / $estacionamiento->espacios_totales) * 100;

        return [
            'espacios_totales' => $estacionamiento->espacios_totales,
            'espacios_disponibles' => $estacionamiento->espacios_disponibles,
            'espacios_ocupados' => $espaciosOcupados,
            'porcentaje_ocupacion' => round($porcentajeOcupacion, 2),
            'estado' => $this->determinarEstadoOcupacion($porcentajeOcupacion)
        ];
    }

    /**
     * Buscar estacionamientos disponibles en un área
     */
    public function buscarDisponiblesEnArea(array $filtros = []): array
    {
        try {
            $estacionamientos = $this->estacionamientoRepository->getEstacionamientosConEspacios();
            
            $resultados = [];
            foreach ($estacionamientos as $estacionamiento) {
                $ocupacion = $this->getOcupacionActual($estacionamiento->id);
                
                $disponible = [
                    'id' => $estacionamiento->id,
                    'nombre' => $estacionamiento->nombre,
                    'direccion' => $estacionamiento->direccion,
                    'precio_por_hora' => $estacionamiento->precio_por_hora,
                    'precio_mensual' => $estacionamiento->precio_mensual,
                    'espacios_disponibles' => $estacionamiento->espacios_disponibles,
                    'ocupacion' => $ocupacion,
                    'estado' => $estacionamiento->estado
                ];

                // Aplicar filtros si se proporcionan
                if (!empty($filtros)) {
                    if (isset($filtros['precio_max_hora']) && 
                        $estacionamiento->precio_por_hora > $filtros['precio_max_hora']) {
                        continue;
                    }
                    
                    if (isset($filtros['espacios_min']) && 
                        $estacionamiento->espacios_disponibles < $filtros['espacios_min']) {
                        continue;
                    }
                }

                $resultados[] = $disponible;
            }

            // Ordenar por disponibilidad y precio
            usort($resultados, function($a, $b) {
                if ($a['espacios_disponibles'] === $b['espacios_disponibles']) {
                    return $a['precio_por_hora'] <=> $b['precio_por_hora'];
                }
                return $b['espacios_disponibles'] <=> $a['espacios_disponibles'];
            });

            return $resultados;

        } catch (\Exception $e) {
            Log::error('Error buscando estacionamientos disponibles: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generar reporte de ocupación
     */
    public function generarReporteOcupacion(int $estacionamientoId): array
    {
        try {
            $estacionamiento = $this->estacionamientoRepository->find($estacionamientoId);
            
            if (!$estacionamiento) {
                return ['error' => 'Estacionamiento no encontrado'];
            }

            $ocupacion = $this->getOcupacionActual($estacionamientoId);
            
            return [
                'estacionamiento' => [
                    'id' => $estacionamiento->id,
                    'nombre' => $estacionamiento->nombre,
                    'direccion' => $estacionamiento->direccion
                ],
                'ocupacion_actual' => $ocupacion,
                'total_reservas' => $estacionamiento->total_reservas,
                'tarifas' => [
                    'precio_por_hora' => $estacionamiento->precio_por_hora,
                    'precio_mensual' => $estacionamiento->precio_mensual
                ],
                'fecha_reporte' => now()->format('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            Log::error('Error generando reporte de ocupación: ' . $e->getMessage());
            return ['error' => 'Error interno del servidor'];
        }
    }

    /**
     * Determinar estado de ocupación basado en porcentaje
     */
    private function determinarEstadoOcupacion(float $porcentaje): string
    {
        if ($porcentaje >= 90) {
            return 'lleno';
        } elseif ($porcentaje >= 70) {
            return 'casi_lleno';
        } elseif ($porcentaje >= 30) {
            return 'moderado';
        } else {
            return 'disponible';
        }
    }

    /**
     * Validar capacidad del estacionamiento
     */
    public function validarCapacidad(int $estacionamientoId): bool
    {
        $estacionamiento = $this->estacionamientoRepository->find($estacionamientoId);
        
        if (!$estacionamiento) {
            return false;
        }

        return $estacionamiento->espacios_disponibles <= $estacionamiento->espacios_totales;
    }
}
