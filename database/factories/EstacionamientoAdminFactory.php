<?php


namespace Database\Factories;

use App\Models\EstacionamientoAdmin;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstacionamientoAdminFactory extends Factory
{
    protected $model = EstacionamientoAdmin::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->company . ' Parking',
            'direccion' => $this->faker->address,
            'email' => $this->faker->unique()->companyEmail,
            'telefono' => $this->faker->phoneNumber,
            'espacios_totales' => $this->faker->numberBetween(50, 200),
            'espacios_disponibles' => function (array $attributes) {
                return $this->faker->numberBetween(0, $attributes['espacios_totales']);
            },
            'precio_por_hora' => $this->faker->randomFloat(2, 2, 10),
            'precio_mensual' => $this->faker->randomFloat(2, 80, 200),
            'estado' => 'activo',
            'total_reservas' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function sinEspacios(): static
    {
        return $this->state(fn (array $attributes) => [
            'espacios_disponibles' => 0,
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'inactivo',
        ]);
    }
}