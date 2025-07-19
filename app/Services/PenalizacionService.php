<?php

namespace App\Services;

use App\Models\Penalizacion;
use App\Repositories\Interfaces\PenalizacionRepositoryInterface;
use App\Repositories\Interfaces\TicketRepositoryInterface;
use App\Repositories\Interfaces\EstacionamientoAdminRepositoryInterface;
use App\Services\TarifaCalculatorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class PenalizacionService extends BaseService
{
    private TicketRepositoryInterface $ticketRepository;
    private EstacionamientoAdminRepositoryInterface $estacionamientoRepository;
    private TarifaCalculatorService $tarifaCalculator;

    public function __construct(
        PenalizacionRepositoryInterface $penalizacionRepository,
        TicketRepositoryInterface $ticketRepository,
        EstacionamientoAdminRepositoryInterface $estacionamientoRepository,
        TarifaCalculatorService $tarifaCalculator
    ) {
        parent::__construct($penalizacionRepository);
        $this->ticketRepository = $ticketRepository;
        $this->estacionamientoRepository = $estacionamientoRepository;
        $this->tarifaCalculator = $tarifaCalculator;
    }

    /**
     * Aplicar penalización por tiempo excedido
     */
    public function aplicarPenalizacionTiempo(int $ticketId, ?Carbon $fechaSalidaReal = null): array
    {
        try {
            $ticket = $this->ticketRepository->find($ticketId);
            if (!$ticket) {
                return [
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ];
            }

            $fechaSalidaReal = $fechaSalidaReal ?? now();
            $fechaEntrada = Carbon::parse($ticket->fecha_entrada);
            
            // Calcular tiempo excedido
            $tiempoTotal = $fechaEntrada->diffInMinutes($fechaSalidaReal);
            $tiempoPermitido = $this->calcularTiempoPermitido($ticket);
            
            if ($tiempoTotal <= $tiempoPermitido) {
                return [
                    'success' => true,
                    'message' => 'No hay tiempo excedido',
                    'penalizacion_aplicada' => false
                ];
            }

            $minutosExcedidos = $tiempoTotal - $tiempoPermitido;
            $horasExcedidas = $minutosExcedidos / 60;

            // Obtener tarifa del estacionamiento
            $estacionamiento = $this->estacionamientoRepository->find($ticket->estacionamiento_id);
            
            // Calcular penalización
            $montoPenalizacion = $this->tarifaCalculator->calcularPenalizacionTiempo(
                $estacionamiento->precio_por_hora,
                $horasExcedidas,
                1.5 // 50% extra por penalización
            );

            $penalizacion = [
                'ticket_id' => $ticketId,
                'tipo' => 'tiempo_excedido',
                'minutos_excedidos' => $minutosExcedidos,
                'horas_excedidas' => round($horasExcedidas, 2),
                'monto' => $montoPenalizacion,
                'descripcion' => "Tiempo excedido: {$minutosExcedidos} minutos",
                'fecha_aplicacion' => now(),
                'estado' => 'pendiente'
            ];

            Log::info('Penalización por tiempo aplicada', $penalizacion);

            return [
                'success' => true,
                'message' => 'Penalización aplicada por tiempo excedido',
                'penalizacion_aplicada' => true,
                'penalizacion' => $penalizacion
            ];

        } catch (\Exception $e) {
            Log::error('Error aplicando penalización por tiempo: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Obtener todas las penalizaciones
     */
    public function getAllPenalizaciones(): array
    {
        try {
            $penalizaciones = $this->penalizacionRepository->all();
            return [
                'success' => true,
                'data' => $penalizaciones,
                'message' => 'Penalizaciones obtenidas exitosamente'
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo penalizaciones: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Get paginated penalizaciones with advanced sorting and filtering
     */
    public function getPaginatedPenalizaciones(
        int $page = 1, 
        int $perPage = 10, 
        array $filters = [],
        ?string $sortBy = null,
        string $sortOrder = 'desc'
    ): \Illuminate\Pagination\LengthAwarePaginator {
        try {
            return $this->penalizacionRepository->getPaginated($page, $perPage, $filters, $sortBy, $sortOrder);
        } catch (\Exception $e) {
            throw new \Exception('Error al obtener penalizaciones paginadas: ' . $e->getMessage());
        }
    }

    /**
     * Obtener penalizaciones con filtros
     */
    public function getPenalizacionesWithFilters(array $filters, ?string $search = null, int $perPage = 15): array
    {
        try {
            $result = $this->penalizacionRepository->getWithFilters($filters, $search, $perPage);
            return [
                'success' => true,
                'data' => $result['data'],
                'pagination' => [
                    'total' => $result['total'],
                    'per_page' => $result['per_page'],
                    'current_page' => $result['current_page'],
                    'last_page' => $result['last_page'],
                    'from' => $result['from'],
                    'to' => $result['to']
                ],
                'filters_applied' => $filters,
                'search' => $search,
                'message' => 'Penalizaciones filtradas obtenidas exitosamente'
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo penalizaciones con filtros: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Crear nueva penalización
     */
    public function createPenalizacion(array $data): array
    {
        try {
            // Validar que el ticket existe
            $ticket = $this->ticketRepository->find($data['ticket_id']);
            if (!$ticket) {
                return [
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ];
            }

            // Usar el cálculo de monto si no se especifica
            if (!isset($data['monto'])) {
                $data['monto'] = $this->calculatePenaltyAmount($data['tipo_penalizacion']);
            }

            // Asegurar estado por defecto
            $data['estado'] = $data['estado'] ?? 'pendiente';
            $data['fecha'] = $data['fecha'] ?? now();

            $penalizacion = $this->penalizacionRepository->create($data);

            Log::info('Penalización creada exitosamente', [
                'penalizacion_id' => $penalizacion->id,
                'ticket_id' => $data['ticket_id'],
                'tipo' => $data['tipo_penalizacion'],
                'monto' => $data['monto']
            ]);

            return [
                'success' => true,
                'data' => $penalizacion,
                'message' => 'Penalización creada exitosamente'
            ];

        } catch (\Exception $e) {
            Log::error('Error creando penalización: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Apply penalty to a ticket
     */
    public function applyPenalty(array $data): Penalizacion
    {
        try {
            // Verificar que el ticket existe
            $ticket = $this->ticketRepository->find($data['ticket_id']);
            if (!$ticket) {
                throw new \Exception('Ticket no encontrado');
            }

            // Calcular el monto de la penalización si no se proporciona
            if (!isset($data['monto'])) {
                $data['monto'] = $this->calculatePenaltyAmount($data['tipo_penalizacion'], $ticket);
            }

            // Crear la penalización
            $penalizacionData = [
                'ticket_id' => $data['ticket_id'],
                'usuario_id' => $ticket->usuario_id,
                'tipo_penalizacion' => $data['tipo_penalizacion'],
                'descripcion' => $data['descripcion'],
                'monto' => $data['monto'],
                'estado' => 'pendiente',
                'fecha_penalizacion' => now(),
                'razon_mal_estacionamiento' => $data['razon_mal_estacionamiento'] ?? null
            ];

            return $this->penalizacionRepository->create($penalizacionData);
        } catch (\Exception $e) {
            throw new \Exception('Error al aplicar penalización: ' . $e->getMessage());
        }
    }

    /**
     * Mark penalty as paid
     */
    public function markAsPaid(int $id): ?Penalizacion
    {
        try {
            $penalizacion = $this->penalizacionRepository->find($id);
            if (!$penalizacion) {
                return null;
            }

            return $this->penalizacionRepository->markAsPaid($id);
        } catch (\Exception $e) {
            throw new \Exception('Error al marcar penalización como pagada: ' . $e->getMessage());
        }
    }

    /**
     * Obtener penalización por ID
     */
    public function getPenalizacionById(int $id): array
    {
        try {
            $penalizacion = $this->penalizacionRepository->find($id);
            
            if (!$penalizacion) {
                return [
                    'success' => false,
                    'message' => 'Penalización no encontrada'
                ];
            }

            return [
                'success' => true,
                'data' => $penalizacion,
                'message' => 'Penalización obtenida exitosamente'
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo penalización: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Actualizar penalización
     */
    public function updatePenalizacion(int $id, array $data): ?Penalizacion
    {
        try {
            $penalizacion = $this->penalizacionRepository->find($id);
            
            if (!$penalizacion) {
                return null;
            }

            return $this->penalizacionRepository->update($id, $data);
        } catch (\Exception $e) {
            throw new \Exception('Error al actualizar penalización: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar penalización
     */
    public function deletePenalizacion(int $id): bool
    {
        try {
            $penalizacion = $this->penalizacionRepository->find($id);
            
            if (!$penalizacion) {
                return false;
            }

            return $this->penalizacionRepository->delete($id);
        } catch (\Exception $e) {
            throw new \Exception('Error al eliminar penalización: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas de penalizaciones
     */
    public function getStatistics(): array
    {
        try {
            $stats = $this->penalizacionRepository->getStatistics();
            return [
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas obtenidas exitosamente'
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Calcular monto de penalización por tipo
     */
    public function calculatePenaltyAmount(string $tipo): float
    {
        $montos = [
            'tiempo_excedido' => 15.00,
            'dano_propiedad' => 100.00,
            'mal_estacionamiento' => 25.00
        ];

        return $montos[$tipo] ?? 20.00;
    }

    /**
     * Aplicar penalización por daño a la propiedad
     */
    public function aplicarPenalizacionDano(int $ticketId, string $descripcion, ?float $monto = null): array
    {
        try {
            $ticket = $this->ticketRepository->find($ticketId);
            if (!$ticket) {
                return [
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ];
            }

            // Usar monto calculado si no se especifica
            $montoFinal = $monto ?? $this->calculatePenaltyAmount('dano_propiedad');

            $penalizacion = [
                'ticket_id' => $ticketId,
                'tipo' => 'dano_propiedad',
                'monto' => $montoFinal,
                'descripcion' => $descripcion,
                'fecha_aplicacion' => now(),
                'estado' => 'pendiente'
            ];

            Log::info('Penalización por daño aplicada', $penalizacion);

            return [
                'success' => true,
                'message' => 'Penalización aplicada por daño a la propiedad',
                'penalizacion_aplicada' => true,
                'penalizacion' => $penalizacion
            ];

        } catch (\Exception $e) {
            Log::error('Error aplicando penalización por daño: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Aplicar penalización por mal estacionamiento
     */
    public function aplicarPenalizacionMalEstacionamiento(int $ticketId, string $razon): array
    {
        try {
            $montosPorRazon = [
                'doble_fila' => 25.00,
                'espacio_discapacitados' => 50.00,
                'bloqueo_salida' => 35.00,
                'fuera_de_lineas' => 15.00,
                'zona_prohibida' => 30.00
            ];

            $monto = $montosPorRazon[$razon] ?? 20.00;

            $ticket = $this->ticketRepository->find($ticketId);
            if (!$ticket) {
                return [
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ];
            }

            $penalizacion = [
                'ticket_id' => $ticketId,
                'tipo' => 'mal_estacionamiento',
                'razon' => $razon,
                'monto' => $monto,
                'descripcion' => "Infracción de estacionamiento: " . str_replace('_', ' ', $razon),
                'fecha_aplicacion' => now(),
                'estado' => 'pendiente'
            ];

            Log::info('Penalización por mal estacionamiento aplicada', $penalizacion);

            return [
                'success' => true,
                'message' => 'Penalización aplicada por mal estacionamiento',
                'penalizacion_aplicada' => true,
                'penalizacion' => $penalizacion
            ];

        } catch (\Exception $e) {
            Log::error('Error aplicando penalización por mal estacionamiento: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Calcular tiempo permitido basado en el tipo de reserva
     */
    private function calcularTiempoPermitido($ticket): int
    {
        if ($ticket->tipo_reserva === 'mensual') {
            return 30 * 24 * 60; // 30 días en minutos
        } else {
            // Para reservas por horas, permitir 15 minutos de gracia adicional
            $tiempoEstimado = 2 * 60; // 2 horas por defecto en minutos
            return $tiempoEstimado + 15; // + 15 minutos de gracia
        }
    }

    /**
     * Procesar pago de penalización
     */
    public function procesarPagoPenalizacion(array $penalizacionData, string $metodoPago): array
    {
        try {
            // Aquí se integraría con el sistema de pagos
            $pagoExitoso = true; // Simulado por ahora

            if ($pagoExitoso) {
                $penalizacionData['estado'] = 'pagada';
                $penalizacionData['fecha_pago'] = now();
                $penalizacionData['metodo_pago'] = $metodoPago;

                Log::info('Pago de penalización procesado', $penalizacionData);

                return [
                    'success' => true,
                    'message' => 'Penalización pagada exitosamente',
                    'penalizacion' => $penalizacionData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al procesar el pago'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error procesando pago de penalización: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Obtener resumen de penalizaciones por usuario
     */
    public function getResumenPenalizacionesUsuario(int $usuarioId): array
    {
        try {
            $tickets = $this->ticketRepository->findByUsuario($usuarioId);
            
            // Aquí se buscarían las penalizaciones reales de la base de datos
            // Por ahora retornamos un resumen simulado
            
            return [
                'success' => true,
                'data' => [
                    'total_penalizaciones' => 0,
                    'penalizaciones_pendientes' => 0,
                    'penalizaciones_pagadas' => 0,
                    'monto_total_pendiente' => 0.00,
                    'monto_total_pagado' => 0.00
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo resumen de penalizaciones: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Validar si se puede aplicar una penalización
     */
    public function validarPenalizacion(int $ticketId, string $tipo): bool
    {
        $ticket = $this->ticketRepository->find($ticketId);
        
        if (!$ticket) {
            return false;
        }

        // No se pueden aplicar penalizaciones a tickets cancelados
        if ($ticket->estado === 'cancelado') {
            return false;
        }

        return true;
    }
}
