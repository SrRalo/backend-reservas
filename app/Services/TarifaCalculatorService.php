<?php

namespace App\Services;

class TarifaCalculatorService
{
    /**
     * Calcular tarifa por horas
     */
    public function calcularTarifaPorHoras(float $tarifaHora, float $horasUsadas): float
    {
        // Redondear hacia arriba para cobrar horas completas
        $horasACobrar = ceil($horasUsadas);
        
        // Aplicar descuentos por volumen
        $descuento = $this->calcularDescuentoPorVolumen($horasACobrar);
        
        $tarifaBase = $tarifaHora * $horasACobrar;
        $tarifaFinal = $tarifaBase * (1 - $descuento);
        
        return round($tarifaFinal, 2);
    }

    /**
     * Calcular tarifa mensual con descuentos
     */
    public function calcularTarifaMensual(float $tarifaMensual, int $diasUsados = 30): float
    {
        if ($diasUsados >= 30) {
            return $tarifaMensual;
        }
        
        // Tarifa prorrateada por días
        $tarifaDiaria = $tarifaMensual / 30;
        return round($tarifaDiaria * $diasUsados, 2);
    }

    /**
     * Calcular penalización por tiempo excedido
     */
    public function calcularPenalizacionTiempo(
        float $tarifaHora, 
        float $horasExcedidas, 
        float $multiplicadorPenalizacion = 1.5
    ): float {
        $horasACobrar = ceil($horasExcedidas);
        $penalizacion = $tarifaHora * $horasACobrar * $multiplicadorPenalizacion;
        
        return round($penalizacion, 2);
    }

    /**
     * Calcular descuento por volumen de horas
     */
    private function calcularDescuentoPorVolumen(float $horas): float
    {
        if ($horas >= 24) {
            return 0.20; // 20% descuento por día completo o más
        } elseif ($horas >= 12) {
            return 0.10; // 10% descuento por medio día
        } elseif ($horas >= 6) {
            return 0.05; // 5% descuento por 6+ horas
        }
        
        return 0; // Sin descuento
    }

    /**
     * Calcular precio estimado para una reserva
     */
    public function calcularPrecioEstimado(array $reservaData, array $estacionamientoData): array
    {
        $tipoReserva = $reservaData['tipo_reserva'];
        $precioEstimado = 0;
        $detalles = [];

        if ($tipoReserva === 'mensual') {
            $diasUsados = $reservaData['dias_estimados'] ?? 30;
            $precioEstimado = $this->calcularTarifaMensual(
                $estacionamientoData['precio_mensual'], 
                $diasUsados
            );
            
            $detalles = [
                'tipo' => 'mensual',
                'dias_estimados' => $diasUsados,
                'precio_mensual_base' => $estacionamientoData['precio_mensual'],
                'precio_calculado' => $precioEstimado
            ];
        } else {
            $horasEstimadas = $reservaData['horas_estimadas'] ?? 2;
            $precioEstimado = $this->calcularTarifaPorHoras(
                $estacionamientoData['precio_por_hora'], 
                $horasEstimadas
            );
            
            $descuento = $this->calcularDescuentoPorVolumen($horasEstimadas);
            
            $detalles = [
                'tipo' => 'por_horas',
                'horas_estimadas' => $horasEstimadas,
                'precio_hora_base' => $estacionamientoData['precio_por_hora'],
                'descuento_aplicado' => $descuento * 100 . '%',
                'precio_calculado' => $precioEstimado
            ];
        }

        return [
            'precio_estimado' => $precioEstimado,
            'detalles' => $detalles
        ];
    }

    /**
     * Verificar si una tarifa es válida
     */
    public function validarTarifa(float $tarifa): bool
    {
        return $tarifa >= 0 && $tarifa <= 999999.99;
    }

    /**
     * Formatear precio para mostrar
     */
    public function formatearPrecio(float $precio): string
    {
        return '$' . number_format($precio, 2);
    }
}
