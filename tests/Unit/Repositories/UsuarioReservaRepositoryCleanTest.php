<?php


namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Repositories\Interfaces\UsuarioReservaRepositoryInterface;
use App\Models\UsuarioReserva;

class UsuarioReservaRepositoryCleanTest extends TestCase
{
    private UsuarioReservaRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(UsuarioReservaRepositoryInterface::class);
        
        // Crear solo las tablas que necesitamos sin RefreshDatabase
        $this->createTestTablesIfNotExist();
        $this->cleanTestData();
    }

    protected function tearDown(): void
    {
        // Limpiar datos después de cada test
        $this->cleanTestData();
        parent::tearDown();
    }

    private function createTestTablesIfNotExist()
    {
        if (!Schema::hasTable('usuario_reserva')) {
            Schema::create('usuario_reserva', function ($table) {
                $table->id();
                $table->string('nombre');
                $table->string('apellido');
                $table->string('email')->unique();
                $table->string('documento')->unique();
                $table->string('telefono')->nullable();
                $table->enum('estado', ['activo', 'inactivo'])->default('activo');
                $table->timestamp('ultimo_acceso')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function ($table) {
                $table->id();
                $table->foreignId('usuario_id');
                $table->string('estado')->default('activo');
                $table->timestamps();
            });
        }
    }

    private function cleanTestData()
    {
        // Limpiar datos de test
        DB::table('tickets')->truncate();
        DB::table('usuario_reserva')->truncate();
    }

    public function test_can_create_usuario_reserva()
    {
        $data = [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan@test.com',
            'documento' => '12345678',
            'telefono' => '555-1234',
            'estado' => 'activo'
        ];

        $usuario = $this->repository->create($data);

        $this->assertInstanceOf(UsuarioReserva::class, $usuario);
        $this->assertEquals('Juan', $usuario->nombre);
        $this->assertEquals('juan@test.com', $usuario->email);
        $this->assertEquals('12345678', $usuario->documento);
    }

    public function test_can_find_usuario_by_id()
    {
        $created = $this->repository->create([
            'nombre' => 'TestUser',
            'apellido' => 'TestApellido',
            'email' => 'test@example.com',
            'documento' => '11111111',
            'estado' => 'activo'
        ]);

        $found = $this->repository->find($created->id);

        $this->assertNotNull($found);
        $this->assertEquals($created->id, $found->id);
        $this->assertEquals('TestUser', $found->nombre);
    }

    public function test_can_find_usuario_by_email()
    {
        $this->repository->create([
            'nombre' => 'Ana',
            'apellido' => 'García',
            'email' => 'ana@test.com',
            'documento' => '87654321',
            'estado' => 'activo'
        ]);

        $found = $this->repository->findByEmail('ana@test.com');

        $this->assertNotNull($found);
        $this->assertEquals('Ana', $found->nombre);
        $this->assertEquals('ana@test.com', $found->email);
    }

    public function test_returns_null_when_email_not_found()
    {
        $found = $this->repository->findByEmail('nonexistent@example.com');

        $this->assertNull($found);
    }

    public function test_can_find_active_users()
    {
        // Crear usuarios activos e inactivos
        $this->repository->create([
            'nombre' => 'Activo1',
            'apellido' => 'Test',
            'email' => 'activo1@test.com',
            'documento' => '11111111',
            'estado' => 'activo'
        ]);

        $this->repository->create([
            'nombre' => 'Activo2',
            'apellido' => 'Test',
            'email' => 'activo2@test.com',
            'documento' => '22222222',
            'estado' => 'activo'
        ]);

        $this->repository->create([
            'nombre' => 'Inactivo',
            'apellido' => 'Test',
            'email' => 'inactivo@test.com',
            'documento' => '33333333',
            'estado' => 'inactivo'
        ]);

        $activeUsers = $this->repository->findActiveUsers();

        $this->assertGreaterThanOrEqual(2, $activeUsers->count());
    }

    public function test_can_update_usuario()
    {
        $usuario = $this->repository->create([
            'nombre' => 'Original',
            'apellido' => 'Test',
            'email' => 'original@test.com',
            'documento' => '66666666',
            'estado' => 'activo'
        ]);

        $updated = $this->repository->update($usuario->id, [
            'nombre' => 'Actualizado',
            'telefono' => '555-9999'
        ]);

        $this->assertEquals('Actualizado', $updated->nombre);
        $this->assertEquals('555-9999', $updated->telefono);
        $this->assertEquals('original@test.com', $updated->email);
    }

    public function test_can_delete_usuario()
    {
        $usuario = $this->repository->create([
            'nombre' => 'ToDelete',
            'apellido' => 'Test',
            'email' => 'delete@test.com',
            'documento' => '77777777',
            'estado' => 'activo'
        ]);

        $deleted = $this->repository->delete($usuario->id);

        $this->assertTrue($deleted);
        $this->assertNull($this->repository->find($usuario->id));
    }
}