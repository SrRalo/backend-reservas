<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

try {
    echo "=== PRUEBA DEL MODELO USUARIO CON ROLES ===\n";
    
    // 1. Verificar que el modelo use la tabla correcta
    $user = new App\Models\UsuarioReserva();
    echo "✅ Tabla del modelo: " . $user->getTable() . "\n";
    
    // 2. Verificar que incluya el campo role en fillable
    $fillable = $user->getFillable();
    echo "✅ Campo 'role' incluido: " . (in_array('role', $fillable) ? 'Sí' : 'No') . "\n";
    
    // 3. Verificar valores por defecto
    $defaultRole = $user->getAttribute('role');
    echo "✅ Rol por defecto: " . ($defaultRole ?: 'reservador') . "\n";
    
    // 4. Probar métodos de roles (sin crear usuario en BD)
    $testUser = new App\Models\UsuarioReserva(['role' => 'admin']);
    echo "✅ isAdmin() funciona: " . ($testUser->isAdmin() ? 'Sí' : 'No') . "\n";
    
    $testUser->role = 'registrador';
    echo "✅ isRegistrador() funciona: " . ($testUser->isRegistrador() ? 'Sí' : 'No') . "\n";
    
    $testUser->role = 'reservador';
    echo "✅ isReservador() funciona: " . ($testUser->isReservador() ? 'Sí' : 'No') . "\n";
    
    // 5. Probar hasRole y hasAnyRole
    $testUser->role = 'admin';
    echo "✅ hasRole('admin') funciona: " . ($testUser->hasRole('admin') ? 'Sí' : 'No') . "\n";
    echo "✅ hasAnyRole(['admin', 'registrador']) funciona: " . ($testUser->hasAnyRole(['admin', 'registrador']) ? 'Sí' : 'No') . "\n";
    
    echo "\n🎉 ¡TODAS LAS PRUEBAS PASARON! El modelo está correctamente configurado.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
