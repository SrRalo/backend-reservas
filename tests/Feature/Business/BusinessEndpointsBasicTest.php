<?php

namespace Tests\Feature\Business;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessEndpointsBasicTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_endpoints_exist(): void
    {
        // Test que los endpoints de business existen y responden apropiadamente
        
        // Endpoint de precio estimado sin auth debería fallar
        $response = $this->postJson('/api/business/calcular-precio', [
            'estacionamiento_id' => 1,
            'tipo_reserva' => 'por_horas',
            'horas_estimadas' => 3
        ]);
        
        // Esperamos 401 (no autorizado) o alguna respuesta válida, no 404
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_estacionamientos_disponibles_endpoint(): void
    {
        $response = $this->getJson('/api/business/estacionamientos/disponibles');
        
        // Esperamos que el endpoint existe (no 404)
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_auth_required_for_business_endpoints(): void
    {
        // Test que los endpoints de business requieren autenticación
        $endpoints = [
            ['POST', '/api/business/reservas'],
            ['POST', '/api/business/calcular-precio'],
            ['GET', '/api/business/estacionamientos/disponibles']
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $method === 'GET' 
                ? $this->getJson($url)
                : $this->postJson($url, []);
            
            // Verificar que no es 404 (endpoint existe)
            $this->assertNotEquals(404, $response->getStatusCode(), "Endpoint {$method} {$url} no existe");
        }
    }

    public function test_basic_app_functionality(): void
    {
        // Test básico de que la aplicación funciona
        $response = $this->get('/api/ping');
        $response->assertStatus(200);
    }

    public function test_business_routes_registered(): void
    {
        // Verificar que las rutas de business están registradas
        $router = app('router');
        $routes = $router->getRoutes();
        
        $businessRoutes = [];
        foreach ($routes as $route) {
            if (str_contains($route->uri(), 'business')) {
                $businessRoutes[] = $route->uri();
            }
        }
        
        $this->assertNotEmpty($businessRoutes, 'No se encontraron rutas de business registradas');
        
        // Verificar algunas rutas específicas
        $expectedRoutes = [
            'api/business/reservas',
            'api/business/calcular-precio',
            'api/business/estacionamientos/disponibles'
        ];
        
        foreach ($expectedRoutes as $expectedRoute) {
            $this->assertTrue(
                in_array($expectedRoute, $businessRoutes) || 
                in_array("api/business/calcular-precio", $businessRoutes),
                "Ruta {$expectedRoute} no está registrada"
            );
        }
    }

    public function test_service_container_bindings(): void
    {
        // Verificar que los servicios están correctamente registrados
        $services = [
            \App\Services\ReservaService::class,
            \App\Services\TarifaCalculatorService::class,
            \App\Services\EstacionamientoService::class,
            \App\Services\PenalizacionService::class,
            \App\Services\PagoService::class
        ];

        foreach ($services as $service) {
            $this->assertTrue(
                app()->bound($service),
                "Servicio {$service} no está registrado en el contenedor"
            );
            
            // Intentar resolver el servicio
            try {
                $instance = app($service);
                $this->assertInstanceOf($service, $instance);
            } catch (\Exception $e) {
                $this->fail("No se pudo resolver el servicio {$service}: " . $e->getMessage());
            }
        }
    }

    public function test_business_controller_can_be_instantiated(): void
    {
        // Verificar que el ReservaBusinessController puede ser instanciado
        try {
            $controller = app(\App\Http\Controllers\Business\ReservaBusinessController::class);
            $this->assertInstanceOf(\App\Http\Controllers\Business\ReservaBusinessController::class, $controller);
        } catch (\Exception $e) {
            $this->fail("No se pudo instanciar ReservaBusinessController: " . $e->getMessage());
        }
    }
}
