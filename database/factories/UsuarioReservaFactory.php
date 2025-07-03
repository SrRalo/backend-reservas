<?php


namespace Database\Factories;

use App\Models\UsuarioReserva;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsuarioReservaFactory extends Factory
{
    protected $model = UsuarioReserva::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->firstName,
            'apellido' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'documento' => $this->faker->unique()->numerify('########'),
            'telefono' => $this->faker->phoneNumber,
            'estado' => 'activo',
            'ultimo_acceso' => null,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'inactivo',
        ]);
    }
}