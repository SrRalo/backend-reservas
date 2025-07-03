<?php

namespace App\Services;

use App\Repositories\Interfaces\TicketRepositoryInterface;
use App\Repositories\Interfaces\EstacionamientoAdminRepositoryInterface;
use App\Services\TarifaCalculatorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PenalizacionService
{
    private TicketRepositoryInterface $ticketRepository;
    private EstacionamientoAdminRepositoryInterface $estacionamientoRepository;
    private TarifaCalculatorService $tarifaCalculator;

    public function __construct(
        TicketRepositoryInterface $ticketRepository,
        EstacionamientoAdminRepositoryInterface $estacionamientoRepository,
        TarifaCalculatorService $tarifaCalculator
    ) {
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
     * Aplicar penalización por daño a la propiedad
     */
    public function aplicarPenalizacionDano(int $ticketId, string $descripcion, float $monto = 100.00): array
    {
        try {
            $ticket = $this->ticketRepository->find($ticketId);
            if (!$ticket) {
                return [
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ];
            }

            $penalizacion = [
                'ticket_id' => $ticketId,
                'tipo' => 'dano_propiedad',
                'monto' => $monto,
                'descripcion' => $descripcion,
                'fecha_aplicacion' => now(),
                'estado' => 'pendiente'
            ];

            Log::info('Penalización por daño aplicada', $penalizacion);

            return [
                'success' => true,
                'message' => 'Penalización aplicada por daño a la propiedad',
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
