<?php

namespace App\Services;

use App\Repositories\Interfaces\PagoRepositoryInterface;
use App\Repositories\Interfaces\TicketRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PagoService
{
    private PagoRepositoryInterface $pagoRepository;
    private TicketRepositoryInterface $ticketRepository;

    public function __construct(
        PagoRepositoryInterface $pagoRepository,
        TicketRepositoryInterface $ticketRepository
    ) {
        $this->pagoRepository = $pagoRepository;
        $this->ticketRepository = $ticketRepository;
    }

    /**
     * Procesar un pago para un ticket
     */
    public function procesarPago(int $ticketId, array $datosPago): array
    {
        try {
            // 1. Validar que el ticket existe y puede ser pagado
            $ticket = $this->ticketRepository->find($ticketId);
            if (!$ticket) {
                return [
                    'success' => false,
                    'message' => 'Ticket no encontrado',
                    'code' => 'TICKET_NOT_FOUND'
                ];
            }

            if ($ticket->estado !== 'activo' && $ticket->estado !== 'finalizado') {
                return [
                    'success' => false,
                    'message' => 'El ticket no está en estado válido para pago',
                    'code' => 'INVALID_TICKET_STATE'
                ];
            }

            // 2. Validar que no haya un pago exitoso previo
            $pagosExistentes = $this->pagoRepository->findByTicket($ticketId);
            $pagosExitosos = $pagosExistentes->filter(function($pago) {
                return $pago->estado === 'exitoso';
            });

            if ($pagosExitosos->count() > 0) {
                return [
                    'success' => false,
                    'message' => 'Este ticket ya tiene un pago exitoso',
                    'code' => 'ALREADY_PAID'
                ];
            }

            // 3. Calcular monto total a pagar
            $montoTotal = $this->calcularMontoTotal($ticket);

            // 4. Crear registro de pago
            $pagoData = [
                'ticket_id' => $ticketId,
                'usuario_id' => $ticket->usuario_id,
                'monto' => $montoTotal,
                'metodo_pago' => $datosPago['metodo_pago'] ?? 'tarjeta',
                'estado' => 'pendiente',
                'codigo_transaccion' => $this->generarCodigoTransaccion(),
                'fecha_pago' => Carbon::now(),
                'datos_pago' => json_encode([
                    'numero_tarjeta_masked' => $this->enmascarrarTarjeta($datosPago['datos_pago']['numero_tarjeta'] ?? ''),
                    'tipo_tarjeta' => $this->detectarTipoTarjeta($datosPago['datos_pago']['numero_tarjeta'] ?? ''),
                    'timestamp' => Carbon::now()->toISOString()
                ])
            ];

            $pago = $this->pagoRepository->create($pagoData);

            // 5. Simular procesamiento del pago (aquí iría integración con gateway real)
            $resultadoPago = $this->simularProcesamentoPago($datosPago);

            // 6. Actualizar estado del pago
            $this->pagoRepository->update($pago->id, [
                'estado' => $resultadoPago['exitoso'] ? 'exitoso' : 'fallido',
                'codigo_autorizacion' => $resultadoPago['codigo_autorizacion'] ?? null,
                'mensaje_error' => $resultadoPago['mensaje_error'] ?? null,
                'updated_at' => Carbon::now()
            ]);

            // 7. Si el pago fue exitoso, actualizar el ticket
            if ($resultadoPago['exitoso']) {
                $this->ticketRepository->update($ticketId, [
                    'estado' => 'pagado',
                    'precio_total' => $montoTotal,
                    'fecha_pago' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }

            $pagoActualizado = $this->pagoRepository->find($pago->id);

            Log::info('Pago procesado', [
                'pago_id' => $pago->id,
                'ticket_id' => $ticketId,
                'estado' => $pagoActualizado->estado,
                'monto' => $montoTotal
            ]);

            return [
                'success' => $resultadoPago['exitoso'],
                'data' => [
                    'pago' => $pagoActualizado,
                    'monto_total' => $montoTotal,
                    'codigo_transaccion' => $pagoData['codigo_transaccion']
                ],
                'message' => $resultadoPago['exitoso'] ? 'Pago procesado exitosamente' : 'Error al procesar el pago',
                'code' => $resultadoPago['exitoso'] ? 'PAYMENT_SUCCESS' : 'PAYMENT_FAILED'
            ];

        } catch (\Exception $e) {
            Log::error('Error procesando pago: ' . $e->getMessage(), [
                'ticket_id' => $ticketId,
                'datos_pago' => $datosPago,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error interno al procesar el pago',
                'code' => 'INTERNAL_ERROR',
                'details' => app()->environment('local') ? $e->getMessage() : null
            ];
        }
    }

    /**
     * Calcular el monto total a pagar para un ticket
     */
    private function calcularMontoTotal($ticket): float
    {
        $montoBase = $ticket->precio_total ?? $ticket->monto ?? 0;
        
        // Agregar penalizaciones si las hay
        $penalizaciones = $ticket->penalizaciones ?? collect();
        $montoPenalizaciones = $penalizaciones->sum('monto');

        return $montoBase + $montoPenalizaciones;
    }

    /**
     * Generar código único de transacción
     */
    private function generarCodigoTransaccion(): string
    {
        return 'TXN-' . strtoupper(Str::random(8)) . '-' . Carbon::now()->format('YmdHis');
    }

    /**
     * Enmascarar número de tarjeta para almacenamiento seguro
     */
    private function enmascarrarTarjeta(string $numeroTarjeta): string
    {
        if (strlen($numeroTarjeta) < 8) {
            return '****';
        }
        
        return '****-****-****-' . substr($numeroTarjeta, -4);
    }

    /**
     * Detectar tipo de tarjeta basado en el número
     */
    private function detectarTipoTarjeta(string $numeroTarjeta): string
    {
        $numero = preg_replace('/\D/', '', $numeroTarjeta);
        
        if (preg_match('/^4/', $numero)) {
            return 'Visa';
        } elseif (preg_match('/^5[1-5]/', $numero)) {
            return 'MasterCard';
        } elseif (preg_match('/^3[47]/', $numero)) {
            return 'American Express';
        }
        
        return 'Desconocido';
    }

    /**
     * Simular procesamiento de pago (aquí iría la integración real)
     */
    private function simularProcesamentoPago(array $datosPago): array
    {
        // Simular éxito/fallo basado en algunos criterios
        $numeroTarjeta = $datosPago['datos_pago']['numero_tarjeta'] ?? '';
        
        // Tarjetas que simulan fallo
        $tarjetasFallo = ['4000000000000002', '4000000000000341'];
        
        if (in_array($numeroTarjeta, $tarjetasFallo)) {
            return [
                'exitoso' => false,
                'mensaje_error' => 'Tarjeta declinada',
                'codigo_error' => 'CARD_DECLINED'
            ];
        }

        // Simular éxito por defecto
        return [
            'exitoso' => true,
            'codigo_autorizacion' => 'AUTH-' . strtoupper(Str::random(6)),
            'timestamp' => Carbon::now()->toISOString()
        ];
    }

    /**
     * Obtener historial de pagos de un usuario
     */
    public function obtenerHistorialPagos(int $usuarioId, array $filtros = []): array
    {
        try {
            $pagos = $this->pagoRepository->findByUsuario($usuarioId);

            // Aplicar filtros adicionales si se proporcionan
            if (!empty($filtros['fecha_desde'])) {
                $pagos = $pagos->filter(function($pago) use ($filtros) {
                    return $pago->fecha_pago >= $filtros['fecha_desde'];
                });
            }

            if (!empty($filtros['fecha_hasta'])) {
                $pagos = $pagos->filter(function($pago) use ($filtros) {
                    return $pago->fecha_pago <= $filtros['fecha_hasta'];
                });
            }

            if (!empty($filtros['estado'])) {
                $pagos = $pagos->filter(function($pago) use ($filtros) {
                    return $pago->estado === $filtros['estado'];
                });
            }

            // Cargar relaciones necesarias
            $pagos->each(function($pago) {
                $pago->ticket = $this->ticketRepository->find($pago->ticket_id);
            });

            return [
                'success' => true,
                'data' => [
                    'pagos' => $pagos->values(),
                    'total_pagos' => $pagos->count(),
                    'monto_total' => $pagos->sum('monto')
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo historial de pagos: ' . $e->getMessage(), [
                'usuario_id' => $usuarioId,
                'filtros' => $filtros,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error al obtener historial de pagos',
                'code' => 'INTERNAL_ERROR'
            ];
        }
    }

    /**
     * Reembolsar un pago
     */
    public function reembolsarPago(int $pagoId, string $motivo = ''): array
    {
        try {
            $pago = $this->pagoRepository->find($pagoId);
            
            if (!$pago) {
                return [
                    'success' => false,
                    'message' => 'Pago no encontrado',
                    'code' => 'PAYMENT_NOT_FOUND'
                ];
            }

            if ($pago->estado !== 'exitoso') {
                return [
                    'success' => false,
                    'message' => 'Solo se pueden reembolsar pagos exitosos',
                    'code' => 'INVALID_PAYMENT_STATE'
                ];
            }

            // Crear registro de reembolso
            $reembolsoData = [
                'pago_id' => $pagoId,
                'monto' => $pago->monto,
                'motivo' => $motivo,
                'estado' => 'procesado',
                'fecha_reembolso' => Carbon::now(),
                'codigo_reembolso' => 'REF-' . strtoupper(Str::random(8))
            ];

            // Actualizar estado del pago
            $this->pagoRepository->update($pagoId, [
                'estado' => 'reembolsado',
                'fecha_reembolso' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            Log::info('Reembolso procesado', [
                'pago_id' => $pagoId,
                'monto' => $pago->monto,
                'motivo' => $motivo
            ]);

            return [
                'success' => true,
                'data' => [
                    'reembolso' => $reembolsoData,
                    'pago_actualizado' => $this->pagoRepository->find($pagoId)
                ],
                'message' => 'Reembolso procesado exitosamente'
            ];

        } catch (\Exception $e) {
            Log::error('Error procesando reembolso: ' . $e->getMessage(), [
                'pago_id' => $pagoId,
                'motivo' => $motivo,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => 'Error interno al procesar el reembolso',
                'code' => 'INTERNAL_ERROR'
            ];
        }
    }
}
