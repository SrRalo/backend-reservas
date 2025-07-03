<?php


namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

abstract class RepositoryTestCase extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ejecutar migraciones en cada test
        $this->artisan('migrate:fresh');
    }

    /**
     * Crear factory data helpers
     */
    protected function createUsuarioReserva(array $attributes = [])
    {
        return \App\Models\UsuarioReserva::factory()->create($attributes);
    }

    protected function createEstacionamientoAdmin(array $attributes = [])
    {
        return \App\Models\EstacionamientoAdmin::factory()->create($attributes);
    }

    protected function createVehiculo(array $attributes = [])
    {
        return \App\Models\Vehiculo::factory()->create($attributes);
    }

    protected function createTicket(array $attributes = [])
    {
        return \App\Models\Ticket::factory()->create($attributes);
    }
}