<?php

namespace App\Services;

use App\Repositories\Interfaces\TicketRepositoryInterface;
use App\Repositories\Interfaces\EstacionamientoAdminRepositoryInterface;
use App\Repositories\Interfaces\UsuarioReservaRepositoryInterface;
use App\Repositories\Interfaces\VehiculoRepositoryInterface;
use App\Services\TarifaCalculatorService;
use App\Services\EstacionamientoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReservaService
{
    private TicketRepositoryInterface $ticketRepository;
    private EstacionamientoAdminRepositoryInterface $estacionamientoRepository;
    private UsuarioReservaRepositoryInterface $usuarioRepository;
    private VehiculoRepositoryInterface $vehiculoRepository;
    private TarifaCalculatorService $tarifaCalculator;
    private EstacionamientoService $estacionamientoService;

    public function __construct(
        TicketRepositoryInterface $ticketRepository,
        EstacionamientoAdminRepositoryInterface $estacionamientoRepository,
        UsuarioReservaRepositoryInterface $usuarioRepository,
        VehiculoRepositoryInterface $vehiculoRepository,
        TarifaCalculatorService $tarifaCalculator,
        EstacionamientoService $estacionamientoService
    ) {
        $this->ticketRepository = $ticketRepository;
        $this->estacionamientoRepository = $estacionamientoRepository;
        $this->usuarioRepository = $usuarioRepository;
        $this->vehiculoRepository = $vehiculoRepository;
        $this->tarifaCalculator = $tarifaCalculator;
        $this->estacionamientoService = $estacionamientoService;
    }

    /**
     * Crear una nueva reserva
     */
    public function crearReserva(array $reservaData): array
    {
        try {
            Log::info('=== INICIANDO CREACIÓN DE RESERVA ===', $reservaData);
            Log::info('Fecha entrada recibida:', ['fecha_entrada' => $reservaData['fecha_entrada'] ?? 'No especificada']);
            Log::info('Fecha salida estimada recibida:', ['fecha_salida_estimada' => $reservaData['fecha_salida_estimada'] ?? 'No especificada']);
            
            // 1. Validar que el usuario existe y está activo
            $usuario = $this->usuarioRepository->find($reservaData['usuario_id']);
            if (!$usuario || $usuario->estado !== 'activo') {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado o inactivo',
                    'code' => 'USER_NOT_ACTIVE'
                ];
            }

            // 2. Validar que el vehículo existe y pertenece al usuario
            $vehiculo = $this->vehiculoRepository->findByPlaca($reservaData['vehiculo_id']);
            if (!$vehiculo || $vehiculo->usuario_id !== $reservaData['usuario_id']) {
                return [
                    'success' => false,
                    'message' => 'Vehículo no encontrado o no pertenece al usuario',
                    'code' => 'VEHICLE_NOT_VALID'
                ];
            }

            // 3. Validar que el estacionamiento existe y tiene espacios disponibles
            $estacionamiento = $this->estacionamientoRepository->find($reservaData['estacionamiento_id']);
            if (!$estacionamiento || $estacionamiento->estado !== 'activo') {
                return [
                    'success' => false,
                    'message' => 'Estacionamiento no disponible',
                    'code' => 'PARKING_NOT_AVAILABLE'
                ];
            }

            // TEMPORALMENTE COMENTADO PARA TESTING
            /*
            if ($estacionamiento->espacios_disponibles <= 0) {
                return [
                    'success' => false,
                    'message' => 'No hay espacios disponibles en este estacionamiento',
                    'code' => 'NO_SPACES_AVAILABLE'
                ];
            }
            */

            // 4. PERMITIR MÚLTIPLES RESERVAS POR VEHÍCULO
            // Comentada completamente la validación restrictiva
            // Un vehículo puede tener múltiples reservas activas
            
            /*
            $ticketsActivos = $this->ticketRepository->findByUsuario($reservaData['usuario_id']);
            foreach ($ticketsActivos as $ticket) {
                if ($ticket->vehiculo_id === $reservaData['vehiculo_id'] && 
                    $ticket->estado === 'activo' && 
                    $ticket->estacionamiento_id === $reservaData['estacionamiento_id']) {
                    
                    // Verificar si hay conflicto temporal
                    $fechaEntradaNueva = $reservaData['fecha_entrada'] ?? now();
                    $fechaEntradaExistente = $ticket->fecha_entrada;
                    
                    // Por seguridad, no permitir múltiples reservas activas en el mismo estacionamiento
                    return [
                        'success' => false,
                        'message' => 'Ya existe una reserva activa para este vehículo en este estacionamiento',
                        'code' => 'ACTIVE_RESERVATION_EXISTS_SAME_PARKING'
                    ];
                }
            }
            */

            // 5. Generar código único para el ticket
            $codigoTicket = $this->generarCodigoTicket();

            // 6. Calcular tarifa estimada (si es necesario)
            $tarifaEstimada = null;
            if ($reservaData['tipo_reserva'] === 'mensual') {
                $tarifaEstimada = $estacionamiento->precio_mensual;
            } elseif (isset($reservaData['duracion_estimada_horas'])) {
                $tarifaEstimada = $this->tarifaCalculator->calcularTarifaPorHoras(
                    $estacionamiento->precio_por_hora,
                    $reservaData['duracion_estimada_horas']
                );
            }

            // 7. Crear el ticket
            $ticketData = [
                'usuario_id' => $reservaData['usuario_id'],
                'vehiculo_id' => $reservaData['vehiculo_id'],
                'estacionamiento_id' => $reservaData['estacionamiento_id'],
                'codigo_ticket' => $codigoTicket,
                'fecha_entrada' => $reservaData['fecha_entrada'] ?? now(),
                'fecha_salida' => $reservaData['fecha_salida_estimada'] ?? null, // ✅ Usar fecha de salida estimada
                'tipo_reserva' => $reservaData['tipo_reserva'],
                'estado' => 'activo',
                'monto' => $tarifaEstimada
            ];

            $ticket = $this->ticketRepository->create($ticketData);

            // 8. TEMPORALMENTE COMENTADO PARA TESTING
            // $this->estacionamientoService->reducirEspaciosDisponibles($reservaData['estacionamiento_id']);

            // 9. Incrementar contador de reservas
            $this->estacionamientoRepository->incrementarReservas($reservaData['estacionamiento_id']);

            Log::info('Reserva creada exitosamente', [
                'ticket_id' => $ticket->id,
                'codigo_ticket' => $codigoTicket,
                'usuario_id' => $reservaData['usuario_id']
            ]);

            return [
                'success' => true,
                'data' => [
                    'ticket' => $ticket,
                    'precio_total' => $ticket->precio_total,
                    'fecha_entrada' => $ticket->fecha_entrada,
                    'hora_entrada' => $ticket->fecha_entrada->format('H:i:s')
                ],
                'message' => 'Reserva creada exitosamente',
                'codigo_ticket' => $codigoTicket
            ];

        } catch (\Exception $e) {
            Log::error('Error creando reserva: ' . $e->getMessage(), [
                'reserva_data' => $reservaData,
                'exception' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error interno del servidor al crear la reserva',
                'code' => 'INTERNAL_ERROR'
            ];
        }
    }

    /**
     * Finalizar una reserva (checkout)
     */
    public function finalizarReserva(int $ticketId, ?float $montoFinal = null): array
    {
        try {
            // 1. Obtener el ticket
            $ticket = $this->ticketRepository->find($ticketId);
            if (!$ticket) {
                return [
                    'success' => false,
                    'message' => 'Ticket no encontrado',
                    'code' => 'TICKET_NOT_FOUND'
                ];
            }

            if ($ticket->estado !== 'activo') {
                return [
                    'success' => false,
                    'message' => 'El ticket no está activo',
                    'code' => 'TICKET_NOT_ACTIVE'
                ];
            }

            // 2. Calcular monto final si no se proporcionó
            if (!$montoFinal) {
                $estacionamiento = $this->estacionamientoRepository->find($ticket->estacionamiento_id);
                $fechaEntrada = Carbon::parse($ticket->fecha_entrada);
                $fechaSalida = now();

                if ($ticket->tipo_reserva === 'mensual') {
                    $montoFinal = $estacionamiento->precio_mensual;
                } else {
                    $horasUsadas = $fechaEntrada->diffInHours($fechaSalida, false);
                    $montoFinal = $this->tarifaCalculator->calcularTarifaPorHoras(
                        $estacionamiento->precio_por_hora,
                        max(1, ceil($horasUsadas)) // Mínimo 1 hora
                    );
                }
            }

            // 3. Finalizar el ticket
            $result = $this->ticketRepository->finalizarTicket($ticketId, $montoFinal);

            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'No se pudo finalizar el ticket',
                    'code' => 'FINALIZATION_FAILED'
                ];
            }

            // 4. Obtener el ticket actualizado
            $ticketActualizado = $this->ticketRepository->find($ticketId);

            // 5. Liberar espacio en el estacionamiento
            $this->estacionamientoService->liberarEspacio($ticket->estacionamiento_id);

            Log::info('Reserva finalizada exitosamente', [
                'ticket_id' => $ticketId,
                'monto_final' => $montoFinal
            ]);

            return [
                'success' => true,
                'data' => [
                    'ticket' => $ticketActualizado,
                    'monto_final' => $montoFinal,
                    'fecha_salida' => now(),
                    'tiempo_total' => [
                        'entrada' => Carbon::parse($ticket->fecha_entrada),
                        'salida' => now(),
                        'horas' => Carbon::parse($ticket->fecha_entrada)->diffInHours(now())
                    ]
                ],
                'message' => 'Reserva finalizada exitosamente'
            ];

        } catch (\Exception $e) {
            Log::error('Error finalizando reserva: ' . $e->getMessage(), [
                'ticket_id' => $ticketId,
                'exception' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error interno del servidor al finalizar la reserva',
                'code' => 'INTERNAL_ERROR'
            ];
        }
    }

    /**
     * Cancelar una reserva
     */
    public function cancelarReserva(int $ticketId, string $motivo = ''): array
    {
        try {
            $ticket = $this->ticketRepository->find($ticketId);
            if (!$ticket) {
                return [
                    'success' => false,
                    'message' => 'Ticket no encontrado',
                    'code' => 'TICKET_NOT_FOUND'
                ];
            }

            if ($ticket->estado !== 'activo') {
                return [
                    'success' => false,
                    'message' => 'Solo se pueden cancelar tickets activos',
                    'code' => 'TICKET_NOT_ACTIVE'
                ];
            }

            // Actualizar estado del ticket
            $this->ticketRepository->update($ticketId, [
                'estado' => 'cancelado',
                'fecha_salida' => now()
            ]);

            // Obtener el ticket actualizado
            $ticketActualizado = $this->ticketRepository->find($ticketId);

            // Liberar espacio en el estacionamiento
            $this->estacionamientoService->liberarEspacio($ticket->estacionamiento_id);

            Log::info('Reserva cancelada', [
                'ticket_id' => $ticketId,
                'motivo' => $motivo
            ]);

            return [
                'success' => true,
                'data' => [
                    'ticket_cancelado' => $ticketActualizado,
                    'motivo_cancelacion' => $motivo,
                    'fecha_cancelacion' => now()
                ],
                'message' => 'Reserva cancelada exitosamente'
            ];

        } catch (\Exception $e) {
            Log::error('Error cancelando reserva: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'code' => 'INTERNAL_ERROR'
            ];
        }
    }

    /**
     * Obtener resumen de reservas por usuario
     */
    public function getResumenUsuario(int $usuarioId): array
    {
        try {
            $tickets = $this->ticketRepository->findByUsuario($usuarioId);
            
            $resumen = [
                'total_reservas' => count($tickets),
                'reservas_activas' => 0,
                'reservas_completadas' => 0,
                'reservas_canceladas' => 0,
                'monto_total_gastado' => 0
            ];

            foreach ($tickets as $ticket) {
                switch ($ticket->estado) {
                    case 'activo':
                        $resumen['reservas_activas']++;
                        break;
                    case 'pagado':
                        $resumen['reservas_completadas']++;
                        $resumen['monto_total_gastado'] += $ticket->monto ?? 0;
                        break;
                    case 'cancelado':
                        $resumen['reservas_canceladas']++;
                        break;
                }
            }

            return [
                'success' => true,
                'data' => $resumen
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo resumen de usuario: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor',
                'code' => 'INTERNAL_ERROR'
            ];
        }
    }

    /**
     * Generar código único para ticket
     */
    private function generarCodigoTicket(): string
    {
        $prefix = 'TKT';
        $timestamp = now()->format('ymdHi');
        $random = strtoupper(Str::random(4));
        
        return $prefix . $timestamp . $random;
    }
}
