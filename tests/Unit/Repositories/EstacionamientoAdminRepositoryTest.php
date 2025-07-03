<?php


namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Repositories\Interfaces\EstacionamientoAdminRepositoryInterface;
use App\Models\EstacionamientoAdmin;

class EstacionamientoAdminRepositoryTest extends TestCase
{
    private EstacionamientoAdminRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(EstacionamientoAdminRepositoryInterface::class);
        
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
        if (!Schema::hasTable('estacionamientoadmin')) {
            Schema::create('estacionamientoadmin', function ($table) {
                $table->id();
                $table->string('nombre', 100);
                $table->string('correo', 100);
                $table->string('ubicacion', 255)->nullable();
                $table->integer('plazas_totales');
                $table->decimal('precio_hora', 10, 2);
                $table->decimal('precio_mes', 10, 2);
                $table->timestamps();
            });
        }
    }

    private function cleanTestData()
    {
        if (Schema::hasTable('estacionamientoadmin')) {
            DB::table('estacionamientoadmin')->truncate();
        }
    }

    public function test_can_create_estacionamiento()
    {
        $data = [
            'nombre' => 'Parking Central',
            'correo' => 'central@parking.com',
            'ubicacion' => 'Calle 123 #45-67',
            'plazas_totales' => 100,
            'precio_hora' => 5.00,
            'precio_mes' => 120.00
        ];

        $estacionamiento = $this->repository->create($data);

        $this->assertInstanceOf(EstacionamientoAdmin::class, $estacionamiento);
        $this->assertEquals('Parking Central', $estacionamiento->nombre);
        $this->assertEquals('central@parking.com', $estacionamiento->correo);
        $this->assertEquals(100, $estacionamiento->plazas_totales);
    }

    public function test_can_find_estacionamiento_by_email()
    {
        $this->repository->create([
            'nombre' => 'Parking Norte',
            'correo' => 'norte@parking.com',
            'ubicacion' => 'Norte 123',
            'plazas_totales' => 80,
            'precio_hora' => 4.00,
            'precio_mes' => 100.00
        ]);

        $found = $this->repository->findByEmail('norte@parking.com');

        $this->assertNotNull($found);
        $this->assertEquals('Parking Norte', $found->nombre);
        $this->assertEquals('norte@parking.com', $found->email);
    }

    public function test_can_update_espacios_disponibles()
    {
        $estacionamiento = $this->repository->create([
            'nombre' => 'Parking Sur',
            'direccion' => 'Sur 123',
            'email' => 'sur@parking.com',
            'espacios_totales' => 60,
            'espacios_disponibles' => 30,
            'precio_por_hora' => 3.50,
            'precio_mensual' => 90.00,
        ]);

        $updated = $this->repository->updateEspaciosDisponibles($estacionamiento->id, 25);

        $this->assertTrue($updated);
        
        $estacionamiento->refresh();
        $this->assertEquals(25, $estacionamiento->espacios_disponibles);
    }

    public function test_can_increment_reservas()
    {
        $estacionamiento = $this->repository->create([
            'nombre' => 'Parking Este',
            'direccion' => 'Este 123',
            'email' => 'este@parking.com',
            'espacios_totales' => 40,
            'espacios_disponibles' => 20,
            'precio_por_hora' => 6.00,
            'precio_mensual' => 150.00,
            'total_reservas' => 0
        ]);

        $this->assertEquals(0, $estacionamiento->total_reservas);

        $updated = $this->repository->incrementarReservas($estacionamiento->id);

        $this->assertTrue($updated);
        
        $estacionamiento->refresh();
        $this->assertEquals(1, $estacionamiento->total_reservas);
    }

    public function test_can_get_estacionamientos_con_espacios()
    {
        // Crear estacionamientos con y sin espacios
        $this->repository->create([
            'nombre' => 'Con Espacios 1',
            'direccion' => 'Test 1',
            'email' => 'espacios1@test.com',
            'espacios_totales' => 50,
            'espacios_disponibles' => 10,
            'precio_por_hora' => 5.00,
            'precio_mensual' => 120.00,
        ]);

        $this->repository->create([
            'nombre' => 'Con Espacios 2',
            'direccion' => 'Test 2',
            'email' => 'espacios2@test.com',
            'espacios_totales' => 30,
            'espacios_disponibles' => 5,
            'precio_por_hora' => 4.00,
            'precio_mensual' => 100.00,
        ]);

        $this->repository->create([
            'nombre' => 'Sin Espacios',
            'direccion' => 'Test 3',
            'email' => 'sinespacios@test.com',
            'espacios_totales' => 20,
            'espacios_disponibles' => 0,
            'precio_por_hora' => 3.00,
            'precio_mensual' => 80.00,
        ]);

        $conEspacios = $this->repository->getEstacionamientosConEspacios();

        $this->assertCount(2, $conEspacios);
        $conEspacios->each(function ($estacionamiento) {
            $this->assertGreaterThan(0, $estacionamiento->espacios_disponibles);
        });
    }
}