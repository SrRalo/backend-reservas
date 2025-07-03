<?php


namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Repositories\Interfaces\VehiculoRepositoryInterface;
use App\Models\Vehiculo;

class VehiculoRepositoryTest extends TestCase
{
    private VehiculoRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(VehiculoRepositoryInterface::class);
        
        $this->createTestTablesIfNotExist();
        $this->cleanTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanTestData();
        parent::tearDown();
    }

    private function createTestTablesIfNotExist()
    {
        if (!Schema::hasTable('vehiculos')) {
            Schema::create('vehiculos', function ($table) {
                $table->id();
                $table->foreignId('usuario_id');
                $table->string('placa')->unique();
                $table->string('tipo');
                $table->string('marca');
                $table->string('modelo');
                $table->string('color');
                $table->enum('estado', ['activo', 'inactivo'])->default('activo');
                $table->timestamps();
            });
        }

        // Crear tabla usuario_reserva si no existe para la FK
        if (!Schema::hasTable('usuario_reserva')) {
            Schema::create('usuario_reserva', function ($table) {
                $table->id();
                $table->string('nombre');
                $table->string('email')->unique();
                $table->timestamps();
            });
        }
    }

    private function cleanTestData()
    {
        if (Schema::hasTable('vehiculos')) {
            DB::table('vehiculos')->truncate();
        }
        if (Schema::hasTable('usuario_reserva')) {
            DB::table('usuario_reserva')->truncate();
        }
    }

    public function test_can_create_vehiculo()
    {
        // Crear usuario primero
        $usuarioId = DB::table('usuario_reserva')->insertGetId([
            'nombre' => 'Test User',
            'email' => 'test@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = [
            'usuario_id' => $usuarioId,
            'placa' => 'ABC123',
            'tipo' => 'automovil',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'color' => 'Blanco',
            'estado' => 'activo'
        ];

        $vehiculo = $this->repository->create($data);

        $this->assertInstanceOf(Vehiculo::class, $vehiculo);
        $this->assertEquals('ABC123', $vehiculo->placa);
        $this->assertEquals('Toyota', $vehiculo->marca);
        $this->assertEquals('automovil', $vehiculo->tipo);
    }

    public function test_can_find_vehiculo_by_placa()
    {
        $usuarioId = DB::table('usuario_reserva')->insertGetId([
            'nombre' => 'Test User 2',
            'email' => 'test2@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->repository->create([
            'usuario_id' => $usuarioId,
            'placa' => 'XYZ789',
            'tipo' => 'motocicleta',
            'marca' => 'Honda',
            'modelo' => 'CBR',
            'color' => 'Rojo',
        ]);

        $found = $this->repository->findByPlaca('XYZ789');

        $this->assertNotNull($found);
        $this->assertEquals('XYZ789', $found->placa);
        $this->assertEquals('Honda', $found->marca);
    }

    public function test_can_find_vehiculos_by_usuario()
    {
        $usuarioId = DB::table('usuario_reserva')->insertGetId([
            'nombre' => 'Test User 3',
            'email' => 'test3@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear varios vehículos para el mismo usuario
        $this->repository->create([
            'usuario_id' => $usuarioId,
            'placa' => 'CAR001',
            'tipo' => 'automovil',
            'marca' => 'Ford',
            'modelo' => 'Focus',
            'color' => 'Azul',
        ]);

        $this->repository->create([
            'usuario_id' => $usuarioId,
            'placa' => 'MOTO01',
            'tipo' => 'motocicleta',
            'marca' => 'Yamaha',
            'modelo' => 'R1',
            'color' => 'Negro',
        ]);

        $vehiculos = $this->repository->findByUsuario($usuarioId);

        $this->assertCount(2, $vehiculos);
        $vehiculos->each(function ($vehiculo) use ($usuarioId) {
            $this->assertEquals($usuarioId, $vehiculo->usuario_id);
        });
    }
}