<?php


namespace Database\Factories;

use App\Models\Vehiculo;
use App\Models\UsuarioReserva;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehiculoFactory extends Factory
{
    protected $model = Vehiculo::class;

    public function definition(): array
    {
        $marcas = ['Toyota', 'Honda', 'Ford', 'Chevrolet', 'Nissan', 'Hyundai', 'Kia', 'Mazda'];
        $tipos = ['sedan', 'suv', 'hatchback', 'pickup', 'coupe'];
        $colores = ['Blanco', 'Negro', 'Gris', 'Azul', 'Rojo', 'Plata'];

        return [
            'usuario_id' => UsuarioReserva::factory(),
            'placa' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'marca' => $this->faker->randomElement($marcas),
            'modelo' => $this->faker->word,
            'color' => $this->faker->randomElement($colores),
            'tipo' => $this->faker->randomElement($tipos),
            'estado' => 'activo',
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'inactivo',
        ]);
    }
}