<?php

namespace App\Services;

use App\Repositories\Interfaces\EstacionamientoAdminRepositoryInterface;
use Illuminate\Support\Facades\Log;

class PrecioService
{
    private EstacionamientoAdminRepositoryInterface $estacionamientoRepository;

    public function __construct(EstacionamientoAdminRepositoryInterface $estacionamientoRepository)
    {
        $this->estacionamientoRepository = $estacionamientoRepository;
    }

    /**
     * Calcular precio estimado para una reserva
     */
    public function calcularPrecioEstimado(int $estacionamientoId, string $tipoReserva, ?float $horas = null, ?int $dias = null): array
    {
        try {
            $estacionamiento = $this->estacionamientoRepository->find($estacionamientoId);
            
            if (!$estacionamiento) {
                return [
                    'success' => false,
                    'message' => 'Estacionamiento no encontrado'
                ];
            }

            $precioEstimado = $this->calculatePrice($estacionamiento, $tipoReserva, $horas, $dias);

            return [
                'success' => true,
                'data' => [
                    'precio_estimado' => $precioEstimado,
                    'tipo_reserva' => $tipoReserva,
                    'duracion' => $tipoReserva === 'por_horas' ? ($horas ?? 1) : ($dias ?? 1),
                    'estacionamiento_id' => $estacionamientoId,
                    'detalles' => [
                        'precio_por_hora' => $estacionamiento->precio_por_hora,
                        'precio_mensual' => $estacionamiento->precio_mensual
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error calculando precio estimado: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Calcular precio según tipo de reserva
     */
    private function calculatePrice($estacionamiento, string $tipoReserva, ?float $horas = null, ?int $dias = null): float
    {
        if ($tipoReserva === 'por_horas') {
            $horasCalculadas = $horas ?? 1;
            return $horasCalculadas * $estacionamiento->precio_por_hora;
        } elseif ($tipoReserva === 'mensual') {
            return $estacionamiento->precio_mensual;
        }

        return 0.0;
    }
}
