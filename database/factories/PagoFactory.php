<?php


namespace Database\Factories;

use App\Models\Models\Pago;
use App\Models\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class PagoFactory extends Factory
{
    protected $model = Pago::class;

    public function definition(): array
    {
        $estado = $this->faker->randomElement(['pendiente', 'pagado', 'cancelado']);
        
        return [
            'ticket_id' => Ticket::factory(),
            'monto' => $this->faker->randomFloat(2, 5, 100),
            'estado' => $estado,
            'fecha_pago' => $estado === 'pagado' ? $this->faker->dateTimeThisMonth() : null,
            'metodo_pago' => $this->faker->randomElement(['efectivo', 'tarjeta', 'transferencia']),
        ];
    }

    public function pagado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'pagado',
            'fecha_pago' => $this->faker->dateTimeThisMonth(),
        ]);
    }

    public function pendiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'pendiente',
            'fecha_pago' => null,
        ]);
    }
}