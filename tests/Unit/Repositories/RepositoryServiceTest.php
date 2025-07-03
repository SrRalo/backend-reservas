<?php


namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\Interfaces\UsuarioReservaRepositoryInterface;

class RepositoryServiceTest extends TestCase
{
    public function test_can_resolve_usuario_reserva_repository()
    {
        $repository = app(UsuarioReservaRepositoryInterface::class);
        
        $this->assertNotNull($repository);
        $this->assertInstanceOf(UsuarioReservaRepositoryInterface::class, $repository);
    }
    
    public function test_repository_implements_base_methods()
    {
        $repository = app(UsuarioReservaRepositoryInterface::class);
        
        // Verificar que tiene los métodos base
        $this->assertTrue(method_exists($repository, 'all'));
        $this->assertTrue(method_exists($repository, 'find'));
        $this->assertTrue(method_exists($repository, 'create'));
        $this->assertTrue(method_exists($repository, 'update'));
        $this->assertTrue(method_exists($repository, 'delete'));
        $this->assertTrue(method_exists($repository, 'findByEmail'));
    }
}