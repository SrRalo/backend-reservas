<?php


namespace Database\Factories;

use App\Models\Ticket;
use App\Models\UsuarioReserva;
use App\Models\EstacionamientoAdmin;
use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        $horaEntrada = $this->faker->dateTimeBetween('-1 week', 'now');
        $horaSalida = $this->faker->boolean(70) ? 
            $this->faker->dateTimeBetween($horaEntrada, '+4 hours') : null;

        return [
            'usuario_id' => UsuarioReserva::factory(),
            'estacionamiento_id' => EstacionamientoAdmin::factory(),
            'vehiculo_id' => Vehiculo::factory(),
            'hora_entrada' => $horaEntrada,
            'hora_salida' => $horaSalida,
            'estado' => $horaSalida ? 'finalizado' : 'activo',
        ];
    }

    public function activo(): static
    {
        return $this->state(fn (array $attributes) => [
            'hora_salida' => null,
            'estado' => 'activo',
        ]);
    }

    public function finalizado(): static
    {
        return $this->state(function (array $attributes) {
            $horaEntrada = Carbon::parse($attributes['hora_entrada']);
            return [
                'hora_salida' => $horaEntrada->addHours($this->faker->numberBetween(1, 8)),
                'estado' => 'finalizado',
            ];
        });
    }
}