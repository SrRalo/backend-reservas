<?php

namespace Database\Factories;

use App\Models\Penalizacion;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class PenalizacionFactory extends Factory
{
    protected $model = Penalizacion::class;

    public function definition(): array
    {
        $motivos = [
            'Exceso de tiempo',
            'Estacionamiento indebido',
            'No pago a tiempo',
            'Bloqueo de salida',
            'Uso de espacio reservado'
        ];

        return [
            'ticket_id' => Ticket::factory(),
            'motivo' => $this->faker->randomElement($motivos),
            'monto' => $this->faker->randomFloat(2, 10, 50),
            'estado' => $this->faker->randomElement(['activa', 'resuelta', 'cancelada']),
            'fecha_penalizacion' => $this->faker->dateTimeThisMonth(),
        ];
    }

    public function activa(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'activa',
        ]);
    }

    public function resuelta(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'resuelta',
        ]);
    }
}