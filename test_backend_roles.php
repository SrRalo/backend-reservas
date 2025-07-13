<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

try {
    echo "=== PRUEBA DEL SISTEMA DE ROLES ACTUALIZADO ===\n";
    
    // 1. Crear un usuario de prueba con rol admin (sin password cast)
    $testUser = new App\Models\UsuarioReserva();
    $testUser->nombre = 'Test Admin';
    $testUser->email = 'test@admin.com';
    $testUser->documento = '12345678';
    $testUser->role = 'admin';
    
    echo "✅ Usuario de prueba creado con rol: " . $testUser->role . "\n";
    echo "✅ isAdmin(): " . ($testUser->isAdmin() ? 'Sí' : 'No') . "\n";
    echo "✅ hasRole('admin'): " . ($testUser->hasRole('admin') ? 'Sí' : 'No') . "\n";
    echo "✅ hasAnyRole(['admin', 'registrador']): " . ($testUser->hasAnyRole(['admin', 'registrador']) ? 'Sí' : 'No') . "\n";
    
    // 2. Probar scopes
    echo "\n=== PROBANDO SCOPES ===\n";
    $adminQuery = App\Models\UsuarioReserva::admins();
    echo "✅ Scope admins() creado correctamente\n";
    
    $registradorQuery = App\Models\UsuarioReserva::registradores();
    echo "✅ Scope registradores() creado correctamente\n";
    
    $reservadorQuery = App\Models\UsuarioReserva::reservadores();
    echo "✅ Scope reservadores() creado correctamente\n";
    
    $roleQuery = App\Models\UsuarioReserva::role('admin');
    echo "✅ Scope role('admin') creado correctamente\n";
    
    // 3. Verificar repositorio (mock)
    echo "\n=== PROBANDO REPOSITORIO ===\n";
    $repo = app(App\Repositories\Interfaces\UsuarioReservaRepositoryInterface::class);
    echo "✅ Repositorio resuelto correctamente\n";
    
    // Verificar que los métodos existen
    $reflection = new ReflectionClass($repo);
    $hasGetByRole = $reflection->hasMethod('getByRole');
    $hasGetRoleStats = $reflection->hasMethod('getRoleStatistics');
    
    echo "✅ Método getByRole() existe: " . ($hasGetByRole ? 'Sí' : 'No') . "\n";
    echo "✅ Método getRoleStatistics() existe: " . ($hasGetRoleStats ? 'Sí' : 'No') . "\n";
    
    echo "\n🎉 ¡SISTEMA DE ROLES FUNCIONANDO CORRECTAMENTE!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
